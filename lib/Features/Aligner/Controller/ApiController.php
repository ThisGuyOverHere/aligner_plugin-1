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
        $segments = array();
        $orders = $this->params['order'];
        $type = $this->params['type'];
        $job  = $this->params['id_job'];

        sort($segment_orders);
        foreach ($orders as $order){
            $segments[] = Segments_SegmentDao::getFromOrderJobIdAndType($order, $job, $type)->toArray();
        }


        $first_segment = array_shift($segments);
        $raw_merge = $first_segment['content_raw'];
        $clean_merge = $first_segment['content_clean'];
        $merge_count = $first_segment['raw_word_count'];

        foreach ($segments as $segment) {
            $raw_merge .= " ".$segment['content_raw'];
            $clean_merge .= " ".$segment['content_clean'];
            $merge_count += $segment['raw_word_count'];
            $deleted_ids[] = $segment['id'];
            $deleted_orders[] = $segment['order'];
        }

        $hash_merge = md5($raw_merge);

        $segmentQuery = "UPDATE segments
                    SET content_raw = ?,
                    content_clean = ?,
                    content_hash = ?,
                    raw_word_count = ?
                    WHERE id = ?;";
        $segmentParams = array($raw_merge, $clean_merge, $hash_merge, $merge_count, $first_segment['id']);


        $qMarks = str_repeat('?,', count($segments) - 1) . '?';

        $deleteQuery = "DELETE FROM segments WHERE id IN ($qMarks);";


        $matchQuery = "UPDATE segments_match as sm
                    SET sm.segment_id = null
                    WHERE sm.order IN ($qMarks) AND sm.id_job = ? AND sm.type = ?";
        $matchParams = array_merge($deleted_orders, array($job, $type));

        try {
            $conn = NewDatabase::obtain()->getConnection();
            $conn->beginTransaction();
            $stm = $conn->prepare( $segmentQuery );
            $stm->execute( $segmentParams );
            $stm = $conn->prepare( $deleteQuery );
            $stm->execute( $deleted_ids );
            $stm = $conn->prepare( $matchQuery );
            $stm->execute( $matchParams );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
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
        $split_segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $job, $type)->toArray();
        $split_match = Segments_SegmentMatchDao::getSegmentMatch($order, $job, $type)->toArray();
        $other_match = Segments_SegmentMatchDao::getSegmentMatch($other_order, $job, $other_type)->toArray();

        $order_start = $split_match['order'];
        $order_end = $split_match['next'];
        $other_order_start = $split_match['order'];
        $other_order_end = $split_match['next'];

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
            $conn->beginTransaction();
            $stm = $conn->prepare( $firstSegmentQuery );
            $stm->execute( $firstSegmentParams );
            $stm = $conn->prepare( $firstMatchQuery );
            $stm->execute( $firstMatchParams );
            $stm = $conn->prepare( $otherMatchQuery );
            $stm->execute( $otherMatchParams );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - $firstSegmentParams - $firstMatchParams", -2 );
        }

        //Format returned segments

        $source = array();
        $target = array();

        //Check which segments to retrieve for source/target
        $source_start = ($type == 'source') ? $order_start : $other_order_start;
        $source_end   = ($type == 'source') ? $order_end : $other_order_end;
        $target_start = ($type == 'target') ? $order_start : $other_order_start;
        $target_end   = ($type == 'target') ? $order_end : $other_order_end;

        $sourceSegments = Segments_SegmentDao::getFromOrderInterval($source_start, $source_end, $job, 'source');
        $targetSegments = Segments_SegmentDao::getFromOrderInterval($target_start, $target_end, $job, 'target');

        $source[] = array(
            'type' => 'source',
            'action' => 'update',
            'order' => $source_start,
            'data' => array_shift($sourceSegments)
        );
        $source_order = $source_start;

        foreach ($sourceSegments as $sourceSegment) {

            $source_order = $source_order + ($source_end - $source_order)/2;

            $source[] = array(
                'type' => 'source',
                'action' => 'insert',
                'order' => $source_order,
                'data' => $sourceSegment
            );

        }

        $target[] = array(
            'type' => 'target',
            'action' => 'update',
            'order' => $target_start,
            'data' => array_shift($targetSegments)
        );
        $target_order = $target_start;

        foreach ($targetSegments as $targetSegment) {

            $target_order = $target_order + ($target_end - $target_order)/2;

            $target[] = array(
                'type' => 'target',
                'action' => 'insert',
                'order' => $target_order,
                'data' => $targetSegment
            );

        }

        return $this->response->json(array("source" => $source, "target" => $target));

        //return true;

    }

    public function move(){

    }

}