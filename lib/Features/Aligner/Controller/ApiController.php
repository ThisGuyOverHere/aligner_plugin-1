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

        $segments = array();
        $orders = $this->params['order'];
        $type = $this->params['type'];
        $job  = $this->params['id_job'];
        $job_password = $this->params['password'];

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

        $operations = array();
        $operations[] = array(
            'type' => $type,
            'action' => 'update',
            'order' => $first_segment['order'],
            'data' => Segments_SegmentDao::getFromOrderJobIdAndType($first_segment['order'], $job, $type)
        );

        foreach ($deleted_orders as $order){
            $operations[] = array(
                'type' => $type,
                'action' => 'delete',
                'order' => $order
            );
        }

        return $this->response->json($operations);

    }

    public function split(){

        $order = $this->params['order'];
        $job = $this->params['id_job'];
        $type = $this->params['type'];
        $inverse_order = $this->params['inverse_order'];
        $inverse_type =  ($type == 'target') ? 'source' : 'target';
        $positions = $this->params['positions'];

        if(empty($positions)){
            return true;
        }

        //Gets from 0 since they are returned as an array
        $split_segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $job, $type)->toArray();
        $inverse_segment = Segments_SegmentDao::getFromOrderJobIdAndType($inverse_order, $job, $type)->toArray();
        $split_match = Segments_SegmentMatchDao::getSegmentMatch($order, $job, $type)->toArray();
        $inverse_match = Segments_SegmentMatchDao::getSegmentMatch($inverse_order, $job, $inverse_type)->toArray();

        $order_start = $split_match['order'];
        $order_end = $split_match['next'];
        $inverse_order_start = $split_match['order'];
        $inverse_order_end = $split_match['next'];

        $avg_order = AlignUtils::_getNewOrderValue($order_start,$order_end);
        $inverse_avg = AlignUtils::_getNewOrderValue($inverse_order_start,$inverse_order_end);

        $split_segment['next'] = $avg_order;
        $inverse_segment['next'] = $inverse_avg;

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
        $null_match = $inverse_match;

        $firstSegmentQuery = "UPDATE segments
        SET content_raw = ?,
        content_clean = ?,
        content_hash = ?,
        raw_word_count = ?
        WHERE id = ?";
        $firstSegmentParams = array($first_raw, $first_clean, $first_hash, $first_count, $split_segment['id']);

        $split_segment['content_raw'] = $first_raw;
        $split_segment['content_clean'] = $first_count;
        $split_segment['content_hash'] = $first_hash;
        $split_segment['raw_word_count'] = $first_count;

        $firstMatchQuery = "UPDATE segments_match as sm
        SET next = ?
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $firstMatchParams = array($avg_order, $order, $job, $type);

        $inverseMatchQuery = "UPDATE segments_match as sm
        SET next = ?
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $inverseMatchParams = array($inverse_avg, $inverse_order, $job, $inverse_type);


        //New segment creation

        $new_id = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count($raw_contents));
        $new_segments = array();
        $new_matches = array();
        $new_null_matches = array();
        foreach ($new_id as $key => $id) {

            //create new segments
            $new_segment['id'] = $id;
            $new_segment['content_raw'] = array_shift($raw_contents);
            $new_segment['content_clean'] = AlignUtils::_cleanSegment($new_segment['content_raw'],$new_segment['language_code']);
            $new_segment['content_hash'] = md5($new_segment['content_raw']);
            $new_segment['raw_word_count'] = AlignUtils::_countWordsInSegment($new_segment['content_raw'],$new_segment['language_code']);

            //create new matches
            $new_match['segment_id'] = $id;
            $new_match['order'] = $avg_order;
            $new_segment['order'] = $new_match['order'];

            //If we split the last segment we add new next values for the new segments
            $avg_order = ($split_match['next'] != null) ? AlignUtils::_getNewOrderValue($new_match['order'], $split_match['next']) : $avg_order + 1000000000;

            $new_match['next'] = ($key != count($new_id)-1) ? $avg_order : $split_match['next'];
            $new_segment['next'] = $new_match['next'];
            $new_matches[] = $new_match;

            //create new null matches
            $null_match['segment_id'] = null;
            $null_match['order'] = $inverse_avg;

            //If we split the last segment we add new next values for the new segments
            $inverse_avg = ($inverse_match['next'] != null) ? AlignUtils::_getNewOrderValue($null_match['order'],$inverse_match['next']) : $inverse_avg + 1000000000;

            $null_match['next'] = ($key != count($new_id)-1) ? $inverse_avg : $inverse_match['next'];
            $new_null_matches[] = $null_match;

            $new_segments[] = $new_segment;
        }

        $segmentsDao = new Segments_SegmentDao;
        $segmentsDao->createList($new_segments);

        $segmentsMatchDao = new Segments_SegmentMatchDao;
        $segmentsMatchDao->createList( array_merge($new_matches,$new_null_matches) );

        //$this->recursive_split($avg_order, $job, $type, $inverse_avg, $inverse_type, $positions);

        try {
            $conn = NewDatabase::obtain()->getConnection();
            $conn->beginTransaction();
            $stm = $conn->prepare( $firstSegmentQuery );
            $stm->execute( $firstSegmentParams );
            $stm = $conn->prepare( $firstMatchQuery );
            $stm->execute( $firstMatchParams );
            $stm = $conn->prepare( $inverseMatchQuery );
            $stm->execute( $inverseMatchParams );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - $firstSegmentParams - $firstMatchParams", -2 );
        }

        //Format returned segments

        $source = array();
        $target = array();

        //Check which segments to retrieve for source/target
        $source_start = ($type == 'source') ? $order_start : $inverse_order_start;
        $source_end   = ($type == 'source') ? $order_end : $inverse_order_end;
        $target_start = ($type == 'target') ? $order_start : $inverse_order_start;
        $target_end   = ($type == 'target') ? $order_end : $inverse_order_end;

        $segments =  array_merge(array($split_segment),$new_segments);
        $empty_array = array_fill(0,count($segments)-1, null);
        $sourceSegments = ($type == 'source') ? $segments : array_merge(array($inverse_segment),$empty_array);
        $targetSegments = ($type == 'target') ? $segments : array_merge(array($inverse_segment),$empty_array);

        $source[] = array(
            'type' => 'source',
            'action' => 'update',
            'order' => $source_start,
            'data' => array_shift($sourceSegments)
        );
        $source_order = $source_start;

        foreach ($sourceSegments as $sourceSegment) {

            $source_order = AlignUtils::_getNewOrderValue($source_order,$source_end);

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

            $target_order = AlignUtils::_getNewOrderValue($target_order,$target_end);

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

    public function addGap(){
        $order = $this->params['order'];
        $job = $this->params['id_job'];
        $type = $this->params['type'];
        $other_type = ($type == 'target') ? 'source' : 'target';

        $gap_segment = array();
        $balance_segment = array();

        $previous_segment = Segments_SegmentMatchDao::getPreviousSegmentMatch($order, $job, $type);
        $previous_order = (empty($previous_segment)) ? 0 : $previous_segment['order'];

        $gap_segment['order'] = AlignUtils::_getNewOrderValue($previous_order,$order);
        $gap_segment['next'] = $order;
        $gap_segment['segment_id'] = null;
        $gap_segment['score'] = 100;
        $gap_segment['id_job'] = $job;
        $gap_segment['type'] = $type;

        $operations = array();
        $operations[] = array(
            'type' => $type,
            'action' => 'insert',
            'order' => $gap_segment['order'],
            'data' => null
        );

        $last_segment = Segments_SegmentMatchDao::getLastSegmentMatch($job, $other_type);
        $balance_segment['order'] = $last_segment['order'] + 1000000000;
        $balance_segment['next'] = null;
        $balance_segment['segment_id'] = null;
        $balance_segment['score'] = 100;
        $balance_segment['id_job'] = $job;
        $balance_segment['type'] = $other_type;

        $operations[] = array(
            'type' => $other_type,
            'action' => 'insert',
            'order' => $balance_segment['order'],
            'data' => null
        );


        if(!empty($previous_segment)){
            $gapQuery = "UPDATE segments_match as sm
            SET next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
            $gapParams = array($gap_segment['order'], $previous_order, $job, $type);

            $previous_segment['next'] = $gap_segment['order'];

            $operations[] = array(
                'type' => $type,
                'action' => 'update',
                'order' => $previous_segment['order'],
                'data' => $previous_segment
            );

        }

        $balanceQuery = "UPDATE segments_match as sm
            SET next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $balanceParams = array($balance_segment['order'], $last_segment['order'], $job, $other_type);

        $last_segment['next'] = $balance_segment['order'];

        $operations[] = array(
            'type' => $other_type,
            'action' => 'update',
            'order' => $last_segment['order'],
            'data' => $last_segment
        );

        $segmentsMatchDao = new Segments_SegmentMatchDao;
        $segmentsMatchDao->createList( array($gap_segment,$balance_segment) );

        try {
            $conn = NewDatabase::obtain()->getConnection();
            $conn->beginTransaction();
            if(!empty($previous_segment)){
                $stm = $conn->prepare( $gapQuery );
                $stm->execute( $gapParams );
            }
            $stm = $conn->prepare( $balanceQuery );
            $stm->execute( $balanceParams );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - Order no. $order ", -2 );
        }

        return $this->response->json($operations);
    }
}