<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 11/09/18
 * Time: 14:33
 */

namespace Features\Aligner\Controller;


use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Utils\AlignUtils;

class ApiController extends AlignerController {

    public function merge(){

        //TODO UPDATE WITH SEGMENTS FROM AND TO
        $data = array();

        $segments = array();
        $segment_orders = array();
        $type = $data[0]['type'];
        $job_id = $data[0]['job'];
        foreach ($data as $item){
            $segments[] = Segments_SegmentDao::getFromOrderJobIdAndType($item['order'], $item['job'], $item['type']);
            $segment_orders[] = $item['order'];
        }

        sort($segment_orders);
        $order1 = $segment_orders[0];
        $order2 = array_slice($segment_orders,-1);

        $raw_merge = "";
        $clean_merge = "";
        $merge_count = 0;

        $segment_ids = array();
        foreach ($segments as $segment) {
            $raw_merge .= " ".$segment['content_raw'];
            $clean_merge .= " ".$segment['content_clean'];
            $merge_count += $segment['raw_word_count'];
            $segment_ids[] = $segment['id'];
        }

        sort($segment_ids);
        $id1 = $segment_ids;

        $hash_merge = md5($raw_merge);

        $segmentQuery = "UPDATE segments
                    SET content_raw = ?,
                    content_clean = ?,
                    content_hash = ?,
                    raw_word_count = ?
                    WHERE id = ?;";
        $segmentParams = array($raw_merge, $clean_merge, $hash_merge, $merge_count, $id1);

        $matchQuery = "UPDATE segments_match as sm1,
                    segments_match as sm2
                    SET sm1.next = sm2.next
                    WHERE (sm1.order = ? AND sm1.id_job = ? AND sm1.type = ?) AND 
                    (sm2.order = ? AND sm2.id_job = ? AND sm2.type = ?)";
        $matchParams = array($order1, $job_id, $type, $order2, $job_id, $type);

        $qMarks = str_repeat('?,', count($segments) - 2) . '?';

        $deleted_ids = array_slice($segment_ids, 1, count($segments)-1);
        $deleted_orders = array_slice($segment_ids, 1, count($segments)-1);

        $deleteQueries = "DELETE FROM segments WHERE id IN ($qMarks);
        DELETE FROM segment_match WHERE order IN ($qMarks) AND id_job = ? AND type = ? ;";

        //TODO avoid using this method, make more connections and put each operation in transactions.
        $query = $segmentQuery . $matchQuery . $deleteQueries;
        $query_params = array_merge( $segmentParams, $matchParams, $deleted_ids, $deleted_orders, array($job_id, $type) );

        try {
            $conn = NewDatabase::obtain()->getConnection();
            $stm = $conn->prepare( $query );
            $stm->execute( $query_params );
        } catch ( \PDOException $e ) {
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - $query_params", -2 );
        }
    }

    public function split(){
        $order = $this->params['order'];
        $job = $this->params['id_job'];
        $type = $this->params['type'];
        $other_order = $this->params['other_order'];
        $other_type =  ($type == 'target') ? 'source' : 'target';
        $positions = $this->params['positions'];

        if(empty($positions)){
            return true;
        }

        //Gets from 0 since they are returned as an array
        $split_segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $job, $type)[0]->toArray();
        $split_match = Segments_SegmentMatchDao::getSegmentMatch($order, $job, $type)[0]->toArray();
        $other_match = Segments_SegmentMatchDao::getSegmentMatch($other_order, $job, $other_type)[0]->toArray();

        $avg_order = $split_match['order'] + ($split_match['next'] - $split_match['order'])/2;
        $other_avg = $other_match['order'] + ($other_match['next'] - $other_match['order'])/2;

        $raw_contents = array();
        $full_raw = $split_segment['content_raw'];
        $positions[] = strlen(($full_raw));
        foreach ($positions as $key => $position) {
            $start = ($key == 0) ? 0 : $positions[$key-1];
            $raw_contents[] = substr($full_raw, $start, $position - $start);
        }

        $first_raw = array_shift($raw_contents);
        $first_hash = md5($first_raw);
        $first_clean = AlignUtils::_cleanSegment($first_raw, $split_segment['language_code']);
        $first_count = AlignUtils::_countWordsInSegment($first_raw, $split_segment['language_code']);

        $new_segment = $split_segment;
        $new_match = $split_match;
        $null_match = $other_match;

        $firstSegmentQuery = "UPDATE segments
        SET content_raw = ?,
        content_clean = ?,
        content_hash = ?,
        raw_word_count = ?
        WHERE id = ?";
        $firstSegmentParams = array($first_raw, $first_clean, $first_hash, $first_count, $split_segment['id']);

        $firstMatchQuery = "UPDATE segments_match as sm
        SET next = ?
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $firstMatchParams = array($avg_order, $order, $job, $type);

        $otherMatchQuery = "UPDATE segments_match as sm
        SET next = ?
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $otherMatchParams = array($other_avg, $other_order, $job, $other_type);


        //New segment creation

        $new_id = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count($raw_contents));
        $new_segments = array();
        $new_matches = array();
        $new_null_matches = array();
        foreach ($new_id as $key => $id) {

            //create new segments
            $new_segment['id'] = $id;
            $new_segment['content_raw'] = array_shift($raw_contents);
            $new_segment['content_clean'] = AlignUtils::_cleanSegment($new_segment['content_raw']);
            $new_segment['content_hash'] = md5($new_segment['content_raw']);
            $new_segment['raw_word_count'] = AlignUtils::_countWordsInSegment($new_segment['content_raw']);
            $new_segments[] = $new_segment;

            //create new matches
            $new_match['segment_id'] = $id;
            $new_match['order'] = $avg_order;
            $avg_order = $new_match['order'] + ($split_match['next'] -  $new_match['order'])/2;
            $new_match['next'] = ($key != count($new_id)-1) ? $avg_order : $split_match['next'];
            $new_matches[] = $new_match;

            //create new null matches
            $null_match['segment_id'] = null;
            $null_match['order'] = $other_avg;
            $other_avg = $null_match['order'] + ($other_match['next'] - $null_match['order'])/2;
            $null_match['next'] = ($key != count($new_id)-1) ? $other_avg : $other_match['next'];
            $new_null_matches[] = $null_match;

        }

        $segmentsDao = new Segments_SegmentDao;
        $segmentsDao->createList($new_segments);

        $segmentsMatchDao = new Segments_SegmentMatchDao;
        $segmentsMatchDao->createList( array_merge($new_matches,$new_null_matches) );

        //$this->recursive_split($avg_order, $job, $type, $other_avg, $other_type, $positions);

        try {
            $conn = NewDatabase::obtain()->getConnection();
            $stm = $conn->prepare( $firstSegmentQuery );
            $stm->execute( $firstSegmentParams );
            $stm = $conn->prepare( $firstMatchQuery );
            $stm->execute( $firstMatchParams );
            $stm = $conn->prepare( $otherMatchQuery );
            $stm->execute( $otherMatchParams );
        } catch ( \PDOException $e ) {
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - $firstSegmentParams - $firstMatchParams", -2 );
        }
        return true;

    }

    public function move(){

    }

}