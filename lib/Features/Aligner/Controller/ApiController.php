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
use Features\Aligner\Utils\Constants;

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

        $deleted_orders = [];

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

        $conn = NewDatabase::obtain()->getConnection();
        try {
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
        $id_job = $this->params['id_job'];
        $type = $this->params['type'];
        $inverse_order = $this->params['inverse_order'];
        $inverse_type = ($type == 'target') ? 'source' : 'target';
        $positions = $this->params['positions'];

        if(empty($positions)){
            return true;
        }

        //Gets from 0 since they are returned as an array
        $split_segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $id_job, $type)->toArray();
        $inverse_segment = Segments_SegmentDao::getFromOrderJobIdAndType($inverse_order, $id_job, $inverse_type)->toArray();
        $split_match = Segments_SegmentMatchDao::getSegmentMatch($order, $id_job, $type)->toArray();
        $inverse_match = Segments_SegmentMatchDao::getSegmentMatch($inverse_order, $id_job, $inverse_type)->toArray();

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
        $full_raw = AlignUtils::_mark_xliff_tags($split_segment['content_raw']);
        $positions[] = strlen(($full_raw));
        foreach ($positions as $key => $position) {
            $start = ($key == 0) ? 0 : $positions[$key-1] + 1;
            $raw_substring = substr($full_raw, $start, ($position + 1) - $start);
            $raw_contents[] = AlignUtils::_restore_xliff_tags($raw_substring);
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
        $split_segment['content_clean'] = $first_clean;
        $split_segment['content_hash'] = $first_hash;
        $split_segment['raw_word_count'] = $first_count;

        $firstMatchQuery = "UPDATE segments_match as sm
        SET next = ?
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $firstMatchParams = array($avg_order, $order, $id_job, $type);

        $inverseMatchQuery = "UPDATE segments_match as sm
        SET next = ?
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $inverseMatchParams = array($inverse_avg, $inverse_order, $id_job, $inverse_type);


        //New segment creation
        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();

            $new_ids = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count($raw_contents));
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

            foreach ($new_ids as $key => $id) {


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
                $avg_order = ($split_match['next'] != null) ? AlignUtils::_getNewOrderValue($new_match['order'], $split_match['next']) : $avg_order + Constants::DISTANCE_INT_BETWEEN_MATCHES;

                $new_match['next'] = ($key != count($new_ids)-1) ? $avg_order : (int)$split_match['next'];
                $new_segment['next'] = (int) $new_match['next'];
                $new_matches[] = $new_match;

                //create new null matches
                $null_match['segment_id'] = null;
                $null_match['order'] = $inverse_avg;
                $null_segment['order'] = $inverse_avg;

                //If we split the last segment we add new next values for the new segments
                $inverse_avg = ($inverse_match['next'] != null) ? AlignUtils::_getNewOrderValue($null_match['order'],$inverse_match['next']) : $inverse_avg + Constants::DISTANCE_INT_BETWEEN_MATCHES;

                $null_match['next'] = ($key != count($new_ids)-1) ? $inverse_avg : (int) $inverse_match['next'];
                $null_segment['next'] = $null_match['next'];
                $new_null_matches[] = $null_match;

                $new_segments[] = $new_segment;
                $null_segments[] = $null_segment;
            }

            $segmentsDao = new Segments_SegmentDao;
            $segmentsDao->createList($new_segments);

            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( array_merge($new_matches,$new_null_matches) );

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

        foreach ($sourceSegments as $key => $segment){
            $sourceSegments[$key]['content_raw'] = AlignUtils::_mark_xliff_tags($segment['content_raw']);
        }

        foreach ($targetSegments as $key => $segment){
            $targetSegments[$key]['content_raw'] = AlignUtils::_mark_xliff_tags($segment['content_raw']);
        }

        $source[] = array(
            'type' => 'source',
            'action' => 'update',
            'rif_order' => (int) $source_start,
            'data' => array_shift($sourceSegments)
        );

        foreach ($sourceSegments as $sourceSegment) {

            $source[] = array(
                'type' => 'source',
                'action' => 'create',
                'rif_order' => (int) $source_end,
                'data' => $sourceSegment
            );

        }

        $target[] = array(
            'type' => 'target',
            'action' => 'update',
            'rif_order' => (int) $target_start,
            'data' => array_shift($targetSegments)
        );

        foreach ($targetSegments as $targetSegment) {

            $target[] = array(
                'type' => 'target',
                'action' => 'create',
                'rif_order' => (int)$target_end,
                'data' => $targetSegment
            );


        }

        return $this->response->json(array("source" => $source, "target" => $target));
    }

    public function move(){

        $order = $this->params['order'];
        $job = $this->params['id_job'];
        $type = $this->params['type'];
        $inverse_type = ($type == 'target') ? 'source' : 'target';
        $destination = $this->params['destination'];
        $inverse_destination = $this->params['inverse_destination'];

        $movingSegment = Segments_SegmentDao::getFromOrderJobIdAndType($order,$job,$type)->toArray();
        $movingSegment['order'] = (int) $movingSegment['order'];
        $movingSegment['next'] = (int) $movingSegment['next'];

        $operations = array();

        //$destinationSegment = Segments_SegmentDao::getFromOrderJobIdAndType($destination, $job, $type);
        //$oppositeSegment = Segments_SegmentDao::getFromOrderJobIdAndType($opposite_row, $job, $type);

        //Create new match between destination and destination->prev with starting segment
        $referenceMatch = Segments_SegmentMatchDao::getPreviousSegmentMatch($destination, $job, $type);
        if(!empty($referenceMatch)){
            $referenceMatch = $referenceMatch->toArray();
        }
        $reference_order = (!empty($referenceMatch)) ? $referenceMatch['order'] : 0;
        $new_order = AlignUtils::_getNewOrderValue($reference_order,$destination);
        $new_match = $referenceMatch;
        $new_match['order'] = (int)$new_order;
        $new_match['next'] = (int) $destination;
        $new_match['score'] = 100;
        $new_match['segment_id'] = $movingSegment['id'];
        $new_match['type'] = $type;
        $new_match['id_job'] = $job;
        $new_match['content_raw'] = null;
        $new_match['content_clean'] = null;
        $new_match['raw_word_count'] = null;

        $operations[] = array(
            'type' => $type,
            'action' => 'create',
            'rif_order' => (int) $destination,
            'data' => $new_match
        );

        //Create a new empty match on the opposite side of the row
        $inverseReference = Segments_SegmentMatchDao::getSegmentMatch($inverse_destination, $job, $inverse_type)->toArray();
        $new_inverse_order = AlignUtils::_getNewOrderValue($inverseReference['order'], $inverseReference['next']);
        $new_gap = $inverseReference;
        $new_gap['order'] = (int) $new_inverse_order;
        $new_gap['next'] = (int) $inverseReference['next'];
        $new_gap['score'] = 100;
        $new_gap['segment_id'] = null;
        $new_gap['type'] = $inverse_type;
        $new_gap['id_job'] = $job;
        $new_gap['content_raw'] = null;
        $new_gap['content_clean'] = null;
        $new_gap['raw_word_count'] = null;

        $operations[] = array(
            'type' => $type,
            'action' => 'create',
            'rif_order' => (int) $inverseReference['next'],
            'data' => $new_gap
        );

        //Set original match to empty and edit old next positions

        $moveUpdateQuery = "UPDATE segments_match as sm
            SET sm.segment_id = NULL
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $moveParams = array($order, $job, $type);

        $movingSegment['id'] = null;

        $operations[] = array(
            'type' => $type,
            'action' => 'update',
            'rif_order' => (int) $order,
            'data' => $movingSegment
        );

        if($reference_order != 0) {
            $nextUpdateQuery = "UPDATE segments_match as sm
            SET sm.next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
            $nextParams = array($new_order, $referenceMatch['order'], $job, $type);

            $referenceSegment = Segments_SegmentDao::getFromOrderJobIdAndType($referenceMatch['order'], $job, $type)->toArray();
            $referenceSegment['order'] = (int) $referenceSegment['order'];
            $referenceSegment['next'] = $new_order;

            $operations[] = array(
                'type' => $type,
                'action' => 'update',
                'rif_order' => (int)$referenceMatch['order'],
                'data' => $referenceSegment
            );
        }

        $gapUpdateQuery = "UPDATE segments_match as sm
            SET sm.next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $gapParams = array($new_inverse_order, $inverseReference['order'], $job, $inverse_type);

        $inverseSegment = Segments_SegmentDao::getFromOrderJobIdAndType($inverse_destination, $job, $inverse_type)->toArray();
        $inverseSegment['order'] = (int) $inverseSegment['order'];
        $inverseSegment['next'] = (int) $inverseSegment['next'];

        $operations[] = array(
            'type' => $type,
            'action' => 'update',
            'rif_order' => (int) $inverseSegment['order'],
            'data' => $inverseSegment
        );

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( array( $new_match,$new_gap ) );
            $stm = $conn->prepare( $moveUpdateQuery );
            $stm->execute( $moveParams );
            if($reference_order != 0){
                $stm = $conn->prepare( $nextUpdateQuery );
                $stm->execute( $nextParams );
            }
            $stm = $conn->prepare( $gapUpdateQuery );
            $stm->execute( $gapParams );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - $moveParams ", -2 );
        }

        return $this->response->json($operations);

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
        $gap_match['content_raw'] = null;
        $gap_match['content_clean'] = null;
        $gap_match['raw_word_count'] = null;

        $operations = array();
        $operations[] = array(
            'type' => $type,
            'action' => 'create',
            'rif_order' => (int) $order,
            'data' => $gap_match
        );

        $last_match = Segments_SegmentMatchDao::getLastSegmentMatch($job, $other_type);
        $last_match['order'] = (int) $last_match['order'];
        $balance_match['order'] = $last_match['order'] + Constants::DISTANCE_INT_BETWEEN_MATCHES;
        $balance_match['next'] = null;
        $balance_match['segment_id'] = null;
        $balance_match['score'] = 100;
        $balance_match['id_job'] = $job;
        $balance_match['type'] = $other_type;
        $balance_match['content_raw'] = null;
        $balance_match['content_clean'] = null;
        $balance_match['raw_word_count'] = null;

        $operations[] = array(
            'type' => $other_type,
            'action' => 'push',
            'data' => $balance_match
        );

        if(!empty($previous_match)){
            $previous_match['order'] = (int) $previous_match['order'];
            $gapQuery = "UPDATE segments_match as sm
            SET next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
            $gapParams = array($gap_match['order'], $previous_order, $job, $type);

            $previous_match['next'] = (int) $gap_match['order'];

            $previous_segment = Segments_SegmentDao::getFromOrderJobIdAndType($previous_order, $job, $type)->toArray();
            $previous_segment['order'] = (int) $previous_segment['order'];
            $previous_segment['next'] = (int) $gap_match['order'];

            $operations[] = array(
                'type' => $type,
                'action' => 'update',
                'rif_order' => (int) $previous_match['order'],
                'data' => $previous_segment
            );

        }

        $balanceQuery = "UPDATE segments_match as sm
            SET next = ?
            WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $balanceParams = array($balance_match['order'], $last_match['order'], $job, $other_type);

        $last_match['next'] = (int) $balance_match['order'];

        $last_segment = Segments_SegmentDao::getFromOrderJobIdAndType($last_match['order'], $job, $type);
        if(!empty($last_segment)){
            $last_segment = $last_segment->toArray();
        }
        $last_segment['type'] = $other_type;
        $last_segment['content_raw'] = (!empty($last_segment['content_raw'])) ? $last_segment['content_raw'] : null;
        $last_segment['content_clean'] = (!empty($last_segment['content_clean'])) ? $last_segment['content_clean'] : null;
        $last_segment['raw_word_count'] = (!empty($last_segment['raw_word_count'])) ? $last_segment['raw_word_count'] : null;
        $last_segment['order'] = (int) $last_segment['order'];
        $last_segment['next'] = (int) $last_match['next'];

        $operations[] = array(
            'type' => $other_type,
            'action' => 'update',
            'rif_order' => (int) $last_match['order'],
            'data' => $last_segment
        );



        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            
            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( array($gap_match, $balance_match) );

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

    public function delete(){

        $matches= $this->params['matches'];
        $job = $this->params['id_job'];

        $sources = array();
        $targets = array();

        foreach ($matches as $match){
            if($match['type'] == 'target'){
                $targets[] = $match['order'];
            } else {
                $sources[] = $match['order'];
            }
        }

        if(count($targets) != count($sources)){
            throw new \Exception( "There is a different amount of source matches and target matches, Deletion cancelled", -2 );
        }

        $sourceMatches = Segments_SegmentMatchDao::getMatchesFromOrderArray($sources, $job, 'source');
        $targetMatches = Segments_SegmentMatchDao::getMatchesFromOrderArray($sources, $job, 'source');

        foreach ($sourceMatches as $sourceMatch) {
            if($sourceMatch['segment_id']!=null){
                throw new \Exception( "Segment Matches contain reference to existing segments, Deletion cancelled", -2 );
            }
        }

        foreach ($targetMatches as $targetMatch) {
            if($targetMatch['segment_id']!=null){
                throw new \Exception( "Segment Matches contain reference to existing segments, Deletion cancelled", -2 );
            }
        }

        if(!empty($sources)){

            $qMarksSource = str_repeat('?,', count($sources) - 1) . '?';

            $updateSourceQuery = "UPDATE segments_match AS sm1, segments_match AS sm2
            SET sm1.next = sm2.next
            WHERE sm1.next IN ($qMarksSource) AND sm2.order IN ($qMarksSource)
            AND sm1.type = 'source' AND sm2.type = 'source'
            AND sm1.id_job = ? AND sm2.id_job = ?;";
            $updateSourceParams = array_merge($sources, $sources, array($job, $job));

            $deleteSourceQuery = "DELETE FROM segments_match
            USING segments_match
            WHERE segments_match.order IN ($qMarksSource)
            AND segments_match.type = 'source'
            AND segments_match.id_job = ?;";
            $deleteSourceParams = array_merge($sources, array($job));

        }

        if(!empty($targets)){

            $qMarksTarget = str_repeat('?,', count($targets) - 1) . '?';

            $updateTargetQuery = "UPDATE segments_match AS sm1, segments_match AS sm2
            SET sm1.next = sm2.next
            WHERE sm1.next IN ($qMarksTarget) AND sm2.order IN ($qMarksTarget)
            AND sm1.type = 'target' AND sm2.type = 'target'
            AND sm1.id_job = ? AND sm2.id_job = ?;";
            $updateTargetParams = array_merge($targets, $targets, array($job, $job));

            $deleteTargetQuery = "DELETE FROM segments_match 
            WHERE segments_match.order IN ($qMarksTarget)
            AND segments_match.type = 'target'
            AND segments_match.id_job = ?;";
            $deleteTargetParams = array_merge($targets, array($job));

        }

        $conn = NewDatabase::obtain()->getConnection();
        try {

            $conn->beginTransaction();
            if(!empty($sources)){
                $stm = $conn->prepare( $updateSourceQuery );
                $stm->execute( $updateSourceParams );
                $stm = $conn->prepare( $deleteSourceQuery );
                $stm->execute( $deleteSourceParams );
            }
            if(!empty($targets)){
                $stm = $conn->prepare( $updateTargetQuery );
                $stm->execute( $updateTargetParams );
                $stm = $conn->prepare( $deleteTargetQuery );
                $stm->execute( $deleteTargetParams );
            }
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() , -2 );
        }

    }

}