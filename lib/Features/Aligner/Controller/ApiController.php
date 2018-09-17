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

        foreach ($segments as $key => $segment) {
            $raw_merge .= " ".$segment['content_raw'];
            $clean_merge .= " ".$segment['content_clean'];
            $merge_count += $segment['raw_word_count'];
            $deleted_ids[] = $segment['id'];
            $deleted_orders[] = $segment['order'];
            $segments[$key]['order'] = (int) $segments[$key]['order'];
            $segments[$key]['next'] = (int) $segments[$key]['next'];
        }

        $first_segment['content_raw'] = $raw_merge;
        $first_segment['content_clean'] = $clean_merge;
        $first_segment['raw_word_count'] = $merge_count;
        $first_segment['order'] = (int) $first_segment['order'];
        $first_segment['next'] = (int) $first_segment['next'];

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
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - $segmentParams", -2 );
        }

        $operations = array();
        $operations[] = array(
            'type' => $type,
            'action' => 'update',
            'rif_order' => $first_segment['order'],
            'data' => $first_segment
        );

        foreach ($deleted_orders as $order){
            $operations[] = array(
                'type' => $type,
                'action' => 'delete',
                'rif_order' => (int) $order
            );
        }

        return $this->response->json($operations);

    }

    public function split(){

        $order = $this->params['order'];
        $job = $this->params['id_job'];
        $type = $this->params['type'];
        $inverse_order = $this->params['inverse_order'];
        $inverse_type = ($type == 'target') ? 'source' : 'target';
        $positions = $this->params['positions'];

        if(empty($positions)){
            return true;
        }

        //Gets from 0 since they are returned as an array
        $split_segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $job, $type)->toArray();
        $inverse_segment = Segments_SegmentDao::getFromOrderJobIdAndType($inverse_order, $job, $inverse_type)->toArray();
        $split_match = Segments_SegmentMatchDao::getSegmentMatch($order, $job, $type)->toArray();
        $inverse_match = Segments_SegmentMatchDao::getSegmentMatch($inverse_order, $job, $inverse_type)->toArray();

        $order_start = $split_match['order'];
        $order_end = $split_match['next'];
        $inverse_order_start = $split_match['order'];
        $inverse_order_end = $split_match['next'];

        $avg_order = AlignUtils::_getNewOrderValue($order_start,$order_end);
        $inverse_avg = AlignUtils::_getNewOrderValue($inverse_order_start,$inverse_order_end);

        $split_segment['order'] = (int) $split_segment['order'];
        $inverse_segment['order'] = (int) $split_segment['order'];
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
        $null_segments = array();
        $new_matches = array();
        $new_null_matches = array();

        $null_segment = $inverse_segment;
        $null_segment['id'] = null;
        $null_segment['content_raw'] = null;
        $null_segment['content_clean'] = null;
        $null_segment['content_hash'] = null;
        $null_segment['raw_word_count'] = null;

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
            $new_segment['order'] = (int) $new_match['order'];

            //If we split the last segment we add new next values for the new segments
            $avg_order = ($split_match['next'] != null) ? AlignUtils::_getNewOrderValue($new_match['order'], $split_match['next']) : $avg_order + 1000000000;

            $new_match['next'] = ($key != count($new_id)-1) ? $avg_order : (int)$split_match['next'];
            $new_segment['next'] = (int) $new_match['next'];
            $new_matches[] = $new_match;

            //create new null matches
            $null_match['segment_id'] = null;
            $null_match['order'] = $inverse_avg;
            $null_segment['order'] = $inverse_avg;

            //If we split the last segment we add new next values for the new segments
            $inverse_avg = ($inverse_match['next'] != null) ? AlignUtils::_getNewOrderValue($null_match['order'],$inverse_match['next']) : $inverse_avg + 1000000000;

            $null_match['next'] = ($key != count($new_id)-1) ? $inverse_avg : (int) $inverse_match['next'];
            $null_segment['next'] = $null_match['next'];
            $new_null_matches[] = $null_match;

            $new_segments[] = $new_segment;
            $null_segments[] = $null_segment;
        }

        $segmentsDao = new Segments_SegmentDao;
        $segmentsDao->createList($new_segments);

        $segmentsMatchDao = new Segments_SegmentMatchDao;
        $segmentsMatchDao->createList( array_merge($new_matches,$new_null_matches) );

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
        $sourceSegments = ($type == 'source') ? $segments : array_merge(array($inverse_segment),$null_segments);
        $targetSegments = ($type == 'target') ? $segments : array_merge(array($inverse_segment),$null_segments);

        $source[] = array(
            'type' => 'source',
            'action' => 'update',
            'rif_order' => (int) $source_start,
            'data' => array_shift($sourceSegments)
        );
        $source_order = $source_start;

        foreach ($sourceSegments as $sourceSegment) {

            $source[] = array(
                'type' => 'source',
                'action' => 'create',
                'rif_order' => (int) $source_order,
                'data' => $sourceSegment
            );

            $source_order = AlignUtils::_getNewOrderValue($source_order,$source_end);

        }

        $target[] = array(
            'type' => 'target',
            'action' => 'update',
            'rif_order' => (int) $target_start,
            'data' => array_shift($targetSegments)
        );
        $target_order = $target_start;

        foreach ($targetSegments as $targetSegment) {

            $target[] = array(
                'type' => 'target',
                'action' => 'create',
                'rif_order' => (int)$target_order,
                'data' => $targetSegment
            );

            $target_order = AlignUtils::_getNewOrderValue($target_order,$target_end);

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

        $gap_match = array();
        $balance_match = array();

        $previous_match = Segments_SegmentMatchDao::getPreviousSegmentMatch($order, $job, $type);
        $previous_order = (empty($previous_match)) ? 0 : $previous_match['order'];

        $gap_match['order'] = AlignUtils::_getNewOrderValue($previous_order,$order);
        $gap_match['next'] = $order;
        $gap_match['segment_id'] = null;
        $gap_match['score'] = 100;
        $gap_match['id_job'] = $job;
        $gap_match['type'] = $type;

        $operations = array();
        $operations[] = array(
            'type' => $type,
            'action' => 'create',
            'order' => (int) $previous_order,
            'data' => null
        );

        $last_match = Segments_SegmentMatchDao::getLastSegmentMatch($job, $other_type);
        $last_match['order'] = (int) $last_match['order'];
        $balance_match['order'] = $last_match['order'] + 1000000000;
        $balance_match['next'] = null;
        $balance_match['segment_id'] = null;
        $balance_match['score'] = 100;
        $balance_match['id_job'] = $job;
        $balance_match['type'] = $other_type;

        $operations[] = array(
            'type' => $other_type,
            'action' => 'push',
            'data' => null
        );


        if(!empty($previous_match)){
            $previous_match['order'] = (int) $previous_match['order'];
            $gapQuery = "UPDATE segments_match as sm
            SET next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
            $gapParams = array($gap_match['order'], $previous_order, $job, $type);

            $previous_match['next'] = (int) $gap_match['order'];

            $operations[] = array(
                'type' => $type,
                'action' => 'update',
                'order' => (int) $previous_match['order'],
                'data' => $previous_match
            );

        }

        $balanceQuery = "UPDATE segments_match as sm
            SET next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $balanceParams = array($balance_match['order'], $last_match['order'], $job, $other_type);

        $last_match['next'] = (int) $balance_match['order'];

        $operations[] = array(
            'type' => $other_type,
            'action' => 'update',
            'order' => (int) $last_match['order'],
            'data' => $last_match
        );

        $segmentsMatchDao = new Segments_SegmentMatchDao;
        $segmentsMatchDao->createList( array($gap_match,$balance_match) );

        try {
            $conn = NewDatabase::obtain()->getConnection();
            $conn->beginTransaction();
            if(!empty($previous_match)){
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