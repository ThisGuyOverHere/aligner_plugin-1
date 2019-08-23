<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 14/06/2019
 * Time: 17:06
 */

namespace Features\Aligner\Controller;


use Exceptions\ValidationError;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Utils\AlignUtils;

class JobUndoActionController extends JobActionController
{

    public function undoDelete() {

        $id_job   = $this->job->id;
        $matches = $this->params[ 'matches' ];

        $sources = [];
        $targets = [];

        foreach ( $matches as $match ) {
            if ( $match[ 'type' ] == 'target' ) {
                $targets[] = $match[ 'order' ];
            } else {
                $sources[] = $match[ 'order' ];
            }
        }

        $previous_matches = [ 'target' => [], 'source' => [] ];
        $next_matches     = [ 'target' => [], 'source' => [] ];
        $next_orders      = [ 'target' => [], 'source' => [] ];
        $new_matches      = [ 'target' => [], 'source' => [] ];

        foreach ( [ 'target' => $targets, 'source' => $sources ] as $key => $matches ){

            foreach ( $matches as $match ){

                $previous_match = Segments_SegmentMatchDao::getPreviousMatchOfNonExistent( $match, $id_job, $key );
                $next_match     = Segments_SegmentMatchDao::getNextMatchOfNonExistent( $match, $id_job, $key );
                $next_order     = (!empty($next_match)) ? $next_match['order'] : null;

                if (isset($previous_match)) {
                    $previous_match['next'] = $match;
                    $previous_matches[$key][] = $previous_match;
                }

                if (isset($next_match)){
                    $next_matches[$key][] = $next_match;
                }

                $next_orders[$key][] = $next_order;

                $new_match = [
                    'id_job' => $id_job,
                    'type' => $key,
                    'order' => $match,
                    'score' => 100,
                    'segment_id' => null,
                    'next' => $next_order,
                ];

                $new_matches[$key][] = $new_match;

                try {

                    $this->pushOperation( [
                        'type'      => $key,
                        'action'    => ($next_order !== null) ? 'create': 'push',
                        'rif_order' => $match,
                        'data'      => $new_match
                    ] );

                } catch ( ValidationError $e ) {
                    throw new ValidationError( $e->getMessage(), -2 );
                }

            }

        }

        $conn = NewDatabase::obtain()->getConnection();
        try {

            $conn->beginTransaction();

            $matchesDao = new Segments_SegmentMatchDao();

            $matchesDao->updateList($previous_matches['source']);
            $matchesDao->updateList($previous_matches['target']);
            $matchesDao->createList($new_matches['source']);
            $matchesDao->createList($new_matches['target']);

            $conn->commit();

        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    public function undoSwitchAction() {

        //It's just like the switch but it doesn't return operations to undo

        $id_job   = $this->job->id;

        $type   = $this->params[ 'type' ];
        $order1 = $this->params[ 'order1' ];
        $order2 = $this->params[ 'order2' ];

        $segment_1 = Segments_SegmentDao::getFromOrderJobIdAndType( $order1, $id_job, $type );
        if(!is_object($segment_1)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $segment_1 = $segment_1->toArray();

        $segment_2 = Segments_SegmentDao::getFromOrderJobIdAndType( $order2, $id_job, $type );
        if(!is_object($segment_2)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $segment_2 = $segment_2->toArray();

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $segment_2[ 'id' ], 'score' => 100 ], $order1, $id_job, $type );
            Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $segment_1[ 'id' ], 'score' => 100 ], $order2, $id_job, $type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        $segment_1_copy = $segment_1;

        $segment_1[ 'order' ] = $segment_2[ 'order' ];
        $segment_1[ 'next' ]  = $segment_2[ 'next' ];
        $segment_1[ 'score' ] = 100;

        $segment_2[ 'order' ] = $segment_1_copy[ 'order' ];
        $segment_2[ 'next' ]  = $segment_1_copy[ 'next' ];
        $segment_2[ 'score' ] = 100;

        try{
            $this->pushOperation( [
                'type'      => $type,
                'action'    => "update",
                'rif_order' => $segment_2[ 'order' ],
                'data'      => $segment_2
            ] );

            $this->pushOperation( [
                'type'      => $type,
                'action'    => "update",
                'rif_order' => $segment_1[ 'order' ],
                'data'      => $segment_1
            ] );
        } catch ( ValidationError $e ) {
            throw new ValidationError( $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    public function undoSplit(){

        $id_job   = $this->job->id;
        $segments = [];
        $orders   = $this->params[ 'order' ];
        $type     = $this->params[ 'type' ];


        sort( $orders, SORT_NUMERIC);

        foreach ($orders as $order) {
            $segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $id_job, $type);
            if(!is_object($segment)){
                throw new ValidationError("There's no segment with the parameters specified in the input");
            }
            $segments[] =  $segment->toArray();
        }

        $deleted_orders = [];
        $deleted_segments = [];

        foreach ( $segments as $key => $segment ) {
            if ( $key != 0 ) {
                $deleted_orders[] = $segment[ 'order' ];
                $segment['content_raw'] = '';
                $segment['content_clean'] = '';
                $segment['raw_word_count'] = 0;
                $deleted_segments[] = $segment;
            }

        }

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            $first_segment = Segments_SegmentDao::mergeSegments( $segments, '' );
            Segments_SegmentMatchDao::nullifySegmentsInMatches( $deleted_orders, $id_job, $type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage() . " - Merging $orders", -2 );
        }

        try{

            $this->pushOperation( [
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => $first_segment[ 'order' ],
                'data'      => $first_segment
            ] );

            foreach ( $deleted_segments as $segment ) {
                $this->pushOperation( [
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $segment['order'],
                    'data'      => $segment
                ] );
            }

        } catch ( ValidationError $e ) {
            throw new ValidationError( $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    public function undoMerge(){

        //It works like the normal split but it has to consider merges of distant matches (with segments in between)

        $id_job   = $this->job->id;

        $order           = $this->params[ 'order' ];
        $type            = $this->params[ 'type' ];
        $inverse_order   = $this->params[ 'inverse_order' ];
        $inverse_matches = $this->params[ 'inverse_matches' ];
        $inverse_type    = ( $type == 'target' ) ? 'source' : 'target';
        $matches         = $this->params[ 'matches' ];

        if ( empty( $matches ) ) {
            return true;
        }

        //In this section we find all the inverse segments of the previously merged segments

        $unmerged_segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $id_job, $type);
        $unmerged_match   = Segments_SegmentMatchDao::getSegmentMatch($order, $id_job, $type);
        if(!is_object($unmerged_segment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $unmerged_segment = $unmerged_segment->toArray();
        $unmerged_match   = $unmerged_match->toArray();

        $clean_matches  = [];
        $clean_content  = $unmerged_segment[ 'content_clean' ];
        $matches[]      = [ 'position' => mb_strlen( $clean_content, 'UTF-8' ), 'order' => null ];

        foreach ( $matches as $key => $match ) {

            /*
             * There is an unbalance between positions and orders ( because n splits = n + 1 segments )
             * So we use $matches[ $key -1 ]['order'] as the clean match order to indicate that the first split
             * segment should stay in its original place and all the following splits will be positioned in the
             * position they were before the merge operation
             */

            // $start has an hardcoded + 1 to remove all spaces created by merges after the first segment
            $start         = ( $key == 0 ) ? 0 : ($matches[ $key - 1 ]['position'] + 1);
            $match_order   = ( $key == 0 ) ? $order : $matches[ $key - 1 ]['order'];

            $clean_substring = mb_substr( $clean_content, $start, ( $match['position'] ) - $start, 'UTF-8' );
            $clean_matches[] = [ 'order' => $match_order, 'content_clean' => $clean_substring];

        }

        // Create the ids for the sequence match list

        $first_segment = array_shift($clean_matches);
        $first_clean   = $first_segment['content_clean'];
        $first_count   = \CatUtils::segment_raw_word_count( $first_segment, $this->job->{$unmerged_segment['type']} );

        $unmerged_segment[ 'content_clean' ]  = $first_clean;
        $unmerged_segment[ 'raw_word_count' ] = $first_count;
        $unmerged_segment[ 'id_job' ]         = $id_job;

        $unmerged_match[ 'segment_id' ]     = $unmerged_segment['id'];
        $unmerged_match[ 'content_clean' ]  = $first_clean;
        $unmerged_match[ 'raw_word_count' ] = $first_count;

        $matches_amount = count( $clean_matches );

        $new_ids = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, $matches_amount );

        if($matches_amount != count( $new_ids ) || $matches_amount != count( $inverse_matches ) ){
            throw new ValidationError("There are more inverse segments than segments to restore");
        }

        $new_segments             = [];
        $new_matches              = [];
        $null_matches             = [];
        $previous_matches         = [];
        $previous_inverse_matches = [];
        $next_matches             = [];
        $next_inverse_matches     = [];
        $updated_matches          = [];

        // We know for sure that the length of $new_ids, $clean_matches and $inverse_segments are the same
        // so instead of using a foreach loop a good old for loop will do the job better while we work with indexes

        for ($i = 0; $i < $matches_amount; $i++ ){

            $restored_segment = Segments_SegmentDao::getFromOrderJobIdAndType($clean_matches[$i]['order'], $id_job, $type);

            if( empty( $restored_segment ) ) {

                $current_match         = $clean_matches[ $i ];
                $current_inverse_match = $inverse_matches[ $i ];

                $new_segment       = [];
                $new_match         = [];
                $new_inverse_match = [];

                $new_segment[ 'id' ]       = $new_ids[ $i ];
                $new_segment[ 'id_job' ]   = $id_job;
                $new_segment[ 'type' ]     = $type;
                $new_match[ 'segment_id' ] = $new_ids[ $i ];
                $new_match[ 'id_job' ]     = $id_job;
                $new_match[ 'type' ]       = $type;

                $new_segment[ 'content_clean' ]  = $current_match[ 'content_clean' ];
                $new_segment[ 'content_hash' ]   = null;
                $new_segment[ 'raw_word_count' ] = 0;

                // Restore the original segment_match

                $previous_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $current_match[ 'order' ], $id_job, $type );
                $next_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $current_match[ 'order' ], $id_job, $type );

                if( isset( $previous_match ) ){
                    $previous_match           = $previous_match->toArray();
                    $previous_assigned        = false;
                    foreach ($previous_matches as $prev){
                        if($previous_match['order'] == $prev['order']){
                            $previous_assigned = true;
                        }
                    }
                    if(!$previous_assigned){
                        $previous_match[ 'next' ] = $current_match[ 'order' ];
                        $previous_matches[]       = $previous_match;
                    } else {
                        $new_matches[count($new_matches)-1]['next'] = $current_match['order'];
                    }
                }

                $new_match[ 'order' ]          = $current_match[ 'order' ];
                $new_match[ 'content_clean' ]  = $current_match[ 'content_clean' ];
                $new_match[ 'content_hash' ]   = null;
                $new_match[ 'raw_word_count' ] = 0;

                if( isset( $next_match ) ){
                    $next_match           = $next_match->toArray();
                    $new_match[ 'next' ]  = $next_match[ 'order' ];
                } else {
                    $new_match[ 'next' ]  = null;
                }

                $next_matches[] = $next_match;

                $new_matches[] = $new_match;

                // Restore the original null segment before it got deleted during the merge

                $previous_inverse_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $current_inverse_match[ 'order' ], $id_job, $inverse_type );
                $next_inverse_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $current_inverse_match[ 'order' ], $id_job, $inverse_type );

                if( isset( $previous_inverse_match ) ){
                    $previous_inverse_match           = $previous_inverse_match->toArray();
                    $previous_inverse_assigned        = false;
                    foreach ($previous_inverse_matches as $prev){
                        if($previous_inverse_match['order'] == $prev['order']){
                            $previous_inverse_assigned = true;
                        }
                    }
                    if(!$previous_inverse_assigned){
                        $previous_inverse_match[ 'next' ] = $current_inverse_match[ 'order' ];
                        $previous_inverse_matches[]       = $previous_inverse_match;
                    } else {
                        $null_matches[count($null_matches)-1]['next'] = $current_inverse_match['order'];
                    }
                }

                $new_inverse_match[ 'order' ]  = $current_inverse_match[ 'order' ];
                $new_inverse_match[ 'id_job' ] = $id_job;
                $new_inverse_match[ 'type' ]   = $inverse_type;

                if( isset( $next_inverse_match ) ){
                    $next_inverse_match           = $next_inverse_match->toArray();
                    $new_inverse_match[ 'next' ]  = $next_inverse_match[ 'order' ];
                } else {
                    $new_inverse_match[ 'next' ]  = null;
                }

                $new_segments[] = $new_segment;

                $next_inverse_matches[] = $next_inverse_match;

                $new_inverse_match['segment_id'] = null;

                $null_matches[] = $new_inverse_match;

            } else {

                $new_segment = $restored_segment->toArray();
                $new_match   = $restored_segment->toArray();

                $new_segment[ 'id' ]       = $new_ids[ $i ];
                $new_match[ 'segment_id' ] = $new_ids[ $i ];

                $new_match[ 'content_clean' ]  = $clean_matches[ $i ][ 'content_clean' ];
                $new_match[ 'raw_word_count' ] = \CatUtils::segment_raw_word_count( $new_segment[ 'content_raw' ], $this->job->{ $new_segment[ 'type' ] }  );

                $new_segment[ 'content_clean' ]  = $clean_matches[ $i ][ 'content_clean' ];
                $new_segment[ 'content_hash' ]   = null;
                $new_segment[ 'raw_word_count' ] = \CatUtils::segment_raw_word_count( $new_segment[ 'content_raw' ], $this->job->{ $new_segment[ 'type' ] }  );

                $new_segments[]    = $new_segment;
                $updated_matches[] = $new_match;
            }

        }

        try{

            foreach ($new_matches as $key => $match){
                $this->pushOperation([
                    'type'      => $type,
                    'action'    => ( isset( $next_matches[$key] ) ) ? 'create' : 'push',
                    'rif_order' => ( isset( $next_matches[$key] ) ) ? $next_matches[$key]['order'] : null,
                    'data'      => $match,
                ]);
            }

            foreach ($null_matches as $key => $match){
                $this->pushOperation([
                    'type'      => $inverse_type,
                    'action'    => ( isset( $next_inverse_matches[$key] ) ) ? 'create' : 'push',
                    'rif_order' => ( isset( $next_inverse_matches[$key] ) ) ? $next_inverse_matches[$key]['order'] : null,
                    'data'      => $match,
                ]);
            }


            foreach ($previous_matches as $match){
                $this->pushOperation([
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $match['order'],
                    'data'      => $match,
                ]);
            }

            foreach ($previous_inverse_matches as $match){
                $this->pushOperation([
                    'type'      => $inverse_type,
                    'action'    => 'update',
                    'rif_order' => $match['order'],
                    'data'      => $match,
                ]);
            }

            $this->pushOperation([
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => $unmerged_match['order'],
                'data'      => $unmerged_match,
            ]);

            foreach ($updated_matches as $match){
                $this->pushOperation([
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $match['order'],
                    'data'      => $match,
                ]);
            }


        } catch ( ValidationError $e ) {
            throw new ValidationError( $e->getMessage(), -2 );
        }

        $conn = NewDatabase::obtain()->getConnection();
        try {

            $conn->beginTransaction();

            $segmentsDao = new Segments_SegmentDao;
            $segmentsDao->createList( array_merge( $new_segments) );

            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( array_merge( $new_matches, $null_matches ) );
            $segmentsMatchDao->updateList( array_merge( [ $unmerged_match ], $previous_matches, $previous_inverse_matches, $updated_matches ) );

            Segments_SegmentDao::updateSegmentContent( $unmerged_segment[ 'id' ], [ $first_clean, $first_count ] );

            $conn->commit();

        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    protected function undoMoveFull(){

        $id_job   = $this->job->id;

        $order                     = $this->params[ 'order1' ];
        $moved_order               = $this->params[ 'order2' ];  // The destination order after the original match was moved
        $type                      = $this->params[ 'type' ];
        $inverse_type              = ( $type == 'target' ) ? 'source' : 'target';
        $destination_order         = $this->params[ 'destination' ];
        $inverse_destination_order = $this->params[ 'inverse_destination' ];

        if($order == $destination_order){ return $this->getOperations();}

        $referenceMatch = Segments_SegmentDao::getFromOrderJobIdAndType( $destination_order, $id_job, $type );
        if(is_object($referenceMatch)){
            $referenceMatch = $referenceMatch->toArray();
        }

        $movingSegment = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type );
        if(!is_object($movingSegment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $movingSegment = $movingSegment->toArray();

        $restoredSegment = Segments_SegmentDao::getFromOrderJobIdAndType( $moved_order, $id_job, $type );
        if(!is_object($restoredSegment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $restoredSegment = $restoredSegment->toArray();

        if($referenceMatch) {

            $destination_match = $referenceMatch;

            $destination_match['segment_id'] = $movingSegment['id'];
            $destination_match['content_raw'] = null;
            $destination_match['content_clean'] = $movingSegment['content_clean'];
            $destination_match['raw_word_count'] = $movingSegment['raw_word_count'];

            $this->pushOperation([
                'type' => $type,
                'action' => 'update',
                'rif_order' => $destination_order,
                'data' => $destination_match
            ]);

            $starting_match                   = $restoredSegment;
            $starting_match['segment_id']     = null;
            $starting_match['content_raw']    = null;
            $starting_match['content_clean']  = null;
            $starting_match['raw_word_count'] = null;

            $this->pushOperation([
                'type' => $type,
                'action' => 'update',
                'rif_order' => $moved_order,
                'data' => $starting_match
            ]);


            $new_match_destination = $movingSegment;

            $new_match_destination['score']          = 100;
            $new_match_destination['segment_id']     = $restoredSegment['id'];
            $new_match_destination['type']           = $type;
            $new_match_destination['id_job']         = $id_job;
            $new_match_destination['content_raw']    = $restoredSegment['content_raw'];
            $new_match_destination['content_clean']  = $restoredSegment['content_clean'];
            $new_match_destination['raw_word_count'] = $restoredSegment['raw_word_count'];

            $this->pushOperation([
                'type' => $type,
                'action' => 'update',
                'rif_order' => $new_match_destination['order'],
                'data' => $new_match_destination
            ]);

        } else {

            $new_matches     = [];
            $updated_matches = [];

            // The original operation row had an empty inverse match before the move. This means we have to recreate the row

            $previous_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $destination_order, $id_job, $type );

            $next_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $destination_order, $id_job, $type );
            if(!empty($next_match)){
                $next_match = $next_match->toArray();
                $next_order = $next_match['order'];
            } else {
                $next_order = null;
            }

            $destination_match[ 'order' ]        = $destination_order;
            $destination_match[ 'next' ]         = $next_order;
            $destination_match[ 'id_job' ]       = $id_job;
            $destination_match[ 'type' ]         = $type;
            $destination_match['segment_id']     = $movingSegment['id'];
            $destination_match['content_raw']    = null;
            $destination_match['content_clean']  = $movingSegment['content_clean'];
            $destination_match['raw_word_count'] = $movingSegment['raw_word_count'];
            $destination_match['next']           = $next_order;

            $new_matches[] = $destination_match;

            if( !empty( $previous_match ) ){
                $previous_match         = $previous_match->toArray();
                $previous_match['next'] = $destination_order;
                $this->pushOperation( [
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $previous_match['order'],
                    'data'      => $previous_match
                ] );

                $updated_matches[] = $previous_match;
            }

            $this->pushOperation( [
                'type'      => $type,
                'action'    => (isset($next_order)) ? 'create' : 'push',
                'rif_order' => (isset($next_order)) ? $next_order : null,
                'data'      => $destination_match
            ] );


            $starting_match                   = $restoredSegment;
            $starting_match['segment_id']     = null;
            $starting_match['content_raw']    = null;
            $starting_match['content_clean']  = null;
            $starting_match['raw_word_count'] = null;

            $this->pushOperation([
                'type' => $type,
                'action' => 'update',
                'rif_order' => $moved_order,
                'data' => $starting_match
            ]);

            $updated_matches[] = $restoredSegment;

            $new_match_destination = $movingSegment;

            $new_match_destination['score']          = 100;
            $new_match_destination['segment_id']     = $restoredSegment['id'];
            $new_match_destination['type']           = $type;
            $new_match_destination['id_job']         = $id_job;
            $new_match_destination['content_raw']    = $restoredSegment['content_raw'];
            $new_match_destination['content_clean']  = $restoredSegment['content_clean'];
            $new_match_destination['raw_word_count'] = $restoredSegment['raw_word_count'];

            $this->pushOperation([
                'type' => $type,
                'action' => 'update',
                'rif_order' => $new_match_destination['order'],
                'data' => $new_match_destination
            ]);

            $updated_matches[] = $new_match_destination;

            $previous_inverse_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $inverse_destination_order, $id_job, $inverse_type );

            $next_inverse_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $inverse_destination_order, $id_job, $inverse_type );

            if(!empty($next_inverse_match)){
                $next_inverse_match = $next_inverse_match->toArray();
                $next_inverse_order = $next_inverse_match['order'];
            } else {
                $next_inverse_order = null;
            }

            $inverse_destination_match[ 'order' ]          = $inverse_destination_order;
            $inverse_destination_match[ 'next' ]           = $next_inverse_order;
            $inverse_destination_match[ 'id_job' ]         = $id_job;
            $inverse_destination_match[ 'type' ]           = $inverse_type;
            $inverse_destination_match[ 'segment_id' ]     = null;
            $inverse_destination_match[ 'content_raw' ]    = null;
            $inverse_destination_match[ 'content_clean' ]  = null;
            $inverse_destination_match[ 'raw_word_count' ] = null;

            $this->pushOperation( [
                'type'      => $inverse_type,
                'action'    => (isset($next_order)) ? 'create' : 'push',
                'rif_order' => (isset($next_order)) ? $next_order : null,
                'data'      => $inverse_destination_match
            ] );

            $new_matches[] = $inverse_destination_match;

            if( !empty($previous_inverse_match) ){
                $previous_inverse_match         = $previous_inverse_match->toArray();
                $previous_inverse_match['next'] = $inverse_destination_order;
                $this->pushOperation( [
                    'type'      => $inverse_type,
                    'action'    => 'update',
                    'rif_order' => $previous_inverse_match['order'],
                    'data'      => $previous_inverse_match
                ] );

                $updated_matches[] = $previous_inverse_match;
            }


        }
        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            $segmentsMatchDao = new Segments_SegmentMatchDao;
            Segments_SegmentMatchDao::nullifySegmentsInMatches( [ $order ], $id_job, $type );
            $match_dao = new Segments_SegmentMatchDao();
            if(!empty($referenceMatch)){
                $match_dao->updateList([$destination_match, $starting_match, $new_match_destination]);
            } else {
                $match_dao->createList($new_matches);
                $match_dao->updateList($updated_matches);
            }
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment Move - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();


    }

    protected function undoMoveEmpty(){

        $id_job   = $this->job->id;

        $order                     = $this->params[ 'order' ];
        $type                      = $this->params[ 'type' ];
        $inverse_type              = ( $type == 'target' ) ? 'source' : 'target';
        $destination_order         = $this->params[ 'destination' ];
        $inverse_destination_order = $this->params[ 'inverse_destination' ];

        if($order == $destination_order){ return $this->getOperations();}

        $referenceMatch = Segments_SegmentDao::getFromOrderJobIdAndType( $destination_order, $id_job, $type );
        if(is_object($referenceMatch)){
            $referenceMatch = $referenceMatch->toArray();
        }

        $movingSegment = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type );
        if(!is_object($movingSegment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $movingSegment = $movingSegment->toArray();

        if( !empty ( $referenceMatch ) ){

            // The original operation row had a full inverse match, we just need to put the segment_id back.

            $destination_match                     = $referenceMatch;
            $destination_match[ 'segment_id' ]     = $movingSegment[ 'id' ];
            $destination_match[ 'content_raw' ]    = null;
            $destination_match[ 'content_clean' ]  = $movingSegment[ 'content_clean' ];
            $destination_match[ 'raw_word_count' ] = $movingSegment[ 'raw_word_count' ];

            $this->pushOperation( [
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => $destination_order,
                'data'      => $destination_match
            ] );

            $starting_match                     = $movingSegment;
            $starting_match[ 'segment_id' ]     = null;
            $starting_match[ 'content_raw' ]    = null;
            $starting_match[ 'content_clean' ]  = null;
            $starting_match[ 'raw_word_count' ] = null;

            $this->pushOperation( [
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => $order,
                'data'      => $starting_match
            ] );

        } else {

            $new_matches     = [];
            $updated_matches = [];

            // The original operation row had an empty inverse match before the move. This means we have to recreate the row

            $previous_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $destination_order, $id_job, $type );

            $next_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $destination_order, $id_job, $type );
            if(!empty($next_match)){
                $next_match = $next_match->toArray();
                $next_order = $next_match['order'];
            } else {
                $next_order = null;
            }

            $destination_match[ 'order' ]          = $destination_order;
            $destination_match[ 'next' ]           = $next_order;
            $destination_match[ 'id_job' ]         = $id_job;
            $destination_match[ 'type' ]           = $type;
            $destination_match[ 'segment_id' ]     = $movingSegment[ 'id' ];
            $destination_match[ 'content_raw' ]    = null;
            $destination_match[ 'content_clean' ]  = $movingSegment[ 'content_clean' ];
            $destination_match[ 'raw_word_count' ] = $movingSegment[ 'raw_word_count' ];

            $this->pushOperation( [
                'type'      => $type,
                'action'    => (isset($next_order)) ? 'create' : 'push',
                'rif_order' => (isset($next_order)) ? $next_order : null,
                'data'      => $destination_match
            ] );

            $new_matches[] = $destination_match;

            if( !empty( $previous_match ) ){
                $previous_match         = $previous_match->toArray();
                $previous_match['next'] = $destination_order;
                $this->pushOperation( [
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $previous_match['order'],
                    'data'      => $previous_match
                ] );

                $updated_matches[] = $previous_match;
            }

            $previous_inverse_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $inverse_destination_order, $id_job, $inverse_type );

            $next_inverse_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $inverse_destination_order, $id_job, $inverse_type );
            if(!empty($next_inverse_match)){
                $next_inverse_match = $next_inverse_match->toArray();
                $next_inverse_order = $next_inverse_match['order'];
            } else {
                $next_inverse_order = null;
            }

            $inverse_destination_match[ 'order' ]          = $inverse_destination_order;
            $inverse_destination_match[ 'next' ]           = $next_inverse_order;
            $inverse_destination_match[ 'id_job' ]         = $id_job;
            $inverse_destination_match[ 'type' ]           = $inverse_type;
            $inverse_destination_match[ 'segment_id' ]     = null;
            $inverse_destination_match[ 'content_raw' ]    = null;
            $inverse_destination_match[ 'content_clean' ]  = null;
            $inverse_destination_match[ 'raw_word_count' ] = null;

            $this->pushOperation( [
                'type'      => $inverse_type,
                'action'    => (isset($next_order)) ? 'create' : 'push',
                'rif_order' => (isset($next_order)) ? $next_order : null,
                'data'      => $inverse_destination_match
            ] );

            $new_matches[] = $inverse_destination_match;

            if( !empty($previous_inverse_match) ){
                $previous_inverse_match         = $previous_inverse_match->toArray();
                $previous_inverse_match['next'] = $inverse_destination_order;
                $this->pushOperation( [
                    'type'      => $inverse_type,
                    'action'    => 'update',
                    'rif_order' => $previous_inverse_match['order'],
                    'data'      => $previous_inverse_match
                ] );

                $updated_matches[] = $previous_inverse_match;
            }

        }

        $starting_match                     = $movingSegment;
        $starting_match[ 'segment_id' ]     = null;
        $starting_match[ 'content_raw' ]    = null;
        $starting_match[ 'content_clean' ]  = null;
        $starting_match[ 'raw_word_count' ] = null;

        $updated_matches[] = $starting_match;

        $this->pushOperation( [
            'type'      => $type,
            'action'    => 'update',
            'rif_order' => $order,
            'data'      => $starting_match
        ] );

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            Segments_SegmentMatchDao::nullifySegmentsInMatches( [ $order ], $id_job, $type );
            if(!empty($referenceMatch)){
                Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $movingSegment[ 'id' ] ], $destination_order, $id_job, $type );
            } else {
                $match_dao = new Segments_SegmentMatchDao();
                $match_dao->createList($new_matches);
                $match_dao->updateList($updated_matches);
            }
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment Move - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    public function undoMove(){
        $operationType = $this->params['move_type'];
        if($operationType == 'empty'){
            return $this->undoMoveEmpty();
        } elseif ($operationType == 'full'){
            return $this->undoMoveFull();
        } else {
            throw new ValidationError('This request is not valid');
        }
    }

    public function undoMergeAndAlign(){
        $id_job   = $this->job->id;

        /* With the $inverses variable we refer to $inverses[n]['source'] when we refer to the inverse of the nth
        source element, this means that $inverses[n]['source'] will be a target despite using source as key */

        $matches                   = $this->params[ 'matches' ];
        $inverses                  = $this->params[ 'inverses' ];

        $type                      = 'target'; // In merge-align operations the target is the one that always gets aligned to the source
        $inverse_type              = 'source';
        $inverse_order             = $this->params[ 'inverse_order' ]; // The source order containing the merged segments
        $order                     = $this->params[ 'order1' ]; // Ex destination order
        $moved_order               = $this->params[ 'order2' ]; // New order of the original destination match after the segment moved
        $destination_order         = $this->params[ 'destination' ];
        $inverse_destination_order = $this->params[ 'inverse_destination' ];

        if ($order != $destination_order) {

            // We start by moving the merged segment to the original position

            $referenceMatch = Segments_SegmentDao::getFromOrderJobIdAndType($destination_order, $id_job, $type);
            if (is_object($referenceMatch)) {
                $referenceMatch = $referenceMatch->toArray();
            }

            $movingSegment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $id_job, $type);
            if (!is_object($movingSegment)) {
                throw new ValidationError("There's no segment with the parameters specified in the input");
            }
            $movingSegment = $movingSegment->toArray();

            $restoredSegment = Segments_SegmentDao::getFromOrderJobIdAndType($moved_order, $id_job, $type);
            if (is_object($restoredSegment)) {
                $restoredSegment = $restoredSegment->toArray();
            }

            if ($restoredSegment) {

                if ($referenceMatch) {

                    $destination_match = $referenceMatch;

                    $destination_match['segment_id'] = $movingSegment['id'];
                    $destination_match['content_raw'] = null;
                    $destination_match['content_clean'] = $movingSegment['content_clean'];
                    $destination_match['raw_word_count'] = $movingSegment['raw_word_count'];

                    $this->pushOperation([
                        'type' => $type,
                        'action' => 'update',
                        'rif_order' => $destination_order,
                        'data' => $destination_match
                    ]);

                    $starting_match                   = $restoredSegment;
                    $starting_match['segment_id']     = null;
                    $starting_match['content_raw']    = null;
                    $starting_match['content_clean']  = null;
                    $starting_match['raw_word_count'] = null;

                    $this->pushOperation([
                        'type' => $type,
                        'action' => 'update',
                        'rif_order' => $moved_order,
                        'data' => $starting_match
                    ]);


                    $new_match_destination = $movingSegment;

                    $new_match_destination['score']          = 100;
                    $new_match_destination['segment_id']     = $restoredSegment['id'];
                    $new_match_destination['type']           = $type;
                    $new_match_destination['id_job']         = $id_job;
                    $new_match_destination['content_raw']    = $restoredSegment['content_raw'];
                    $new_match_destination['content_clean']  = $restoredSegment['content_clean'];
                    $new_match_destination['raw_word_count'] = $restoredSegment['raw_word_count'];

                    $this->pushOperation([
                        'type' => $type,
                        'action' => 'update',
                        'rif_order' => $new_match_destination['order'],
                        'data' => $new_match_destination
                    ]);

                } else {

                    $new_matches     = [];
                    $updated_matches = [];

                    // The original operation row had an empty inverse match before the move. This means we have to recreate the row

                    $previous_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $destination_order, $id_job, $type );

                    $next_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $destination_order, $id_job, $type );
                    if(!empty($next_match)){
                        $next_match = $next_match->toArray();
                        $next_order = $next_match['order'];
                    } else {
                        $next_order = null;
                    }

                    $destination_match[ 'order' ]        = $destination_order;
                    $destination_match[ 'next' ]         = $next_order;
                    $destination_match[ 'id_job' ]       = $id_job;
                    $destination_match[ 'type' ]         = $type;
                    $destination_match['segment_id']     = $movingSegment['id'];
                    $destination_match['content_raw']    = null;
                    $destination_match['content_clean']  = $movingSegment['content_clean'];
                    $destination_match['raw_word_count'] = $movingSegment['raw_word_count'];
                    $destination_match['next']           = $next_order;

                    $new_matches[] = $destination_match;

                    if( !empty( $previous_match ) ){
                        $previous_match         = $previous_match->toArray();
                        $previous_match['next'] = $destination_order;
                        $this->pushOperation( [
                            'type'      => $type,
                            'action'    => 'update',
                            'rif_order' => $previous_match['order'],
                            'data'      => $previous_match
                        ] );

                        $updated_matches[] = $previous_match;
                    }

                    $this->pushOperation( [
                        'type'      => $type,
                        'action'    => (isset($next_order)) ? 'create' : 'push',
                        'rif_order' => (isset($next_order)) ? $next_order : null,
                        'data'      => $destination_match
                    ] );


                    $starting_match                   = $restoredSegment;
                    $starting_match['segment_id']     = null;
                    $starting_match['content_raw']    = null;
                    $starting_match['content_clean']  = null;
                    $starting_match['raw_word_count'] = null;

                    $this->pushOperation([
                        'type' => $type,
                        'action' => 'update',
                        'rif_order' => $moved_order,
                        'data' => $starting_match
                    ]);

                    $updated_matches[] = $restoredSegment;

                    $new_match_destination = $movingSegment;

                    $new_match_destination['score']          = 100;
                    $new_match_destination['segment_id']     = $restoredSegment['id'];
                    $new_match_destination['type']           = $type;
                    $new_match_destination['id_job']         = $id_job;
                    $new_match_destination['content_raw']    = $restoredSegment['content_raw'];
                    $new_match_destination['content_clean']  = $restoredSegment['content_clean'];
                    $new_match_destination['raw_word_count'] = $restoredSegment['raw_word_count'];

                    $this->pushOperation([
                        'type' => $type,
                        'action' => 'update',
                        'rif_order' => $new_match_destination['order'],
                        'data' => $new_match_destination
                    ]);

                    $updated_matches[] = $new_match_destination;

                    $previous_inverse_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $inverse_destination_order, $id_job, $inverse_type );

                    $next_inverse_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $inverse_destination_order, $id_job, $inverse_type );

                    if(!empty($next_inverse_match)){
                        $next_inverse_match = $next_inverse_match->toArray();
                        $next_inverse_order = $next_inverse_match['order'];
                    } else {
                        $next_inverse_order = null;
                    }

                    $inverse_destination_match[ 'order' ]          = $inverse_destination_order;
                    $inverse_destination_match[ 'next' ]           = $next_inverse_order;
                    $inverse_destination_match[ 'id_job' ]         = $id_job;
                    $inverse_destination_match[ 'type' ]           = $inverse_type;
                    $inverse_destination_match[ 'segment_id' ]     = null;
                    $inverse_destination_match[ 'content_raw' ]    = null;
                    $inverse_destination_match[ 'content_clean' ]  = null;
                    $inverse_destination_match[ 'raw_word_count' ] = null;

                    $this->pushOperation( [
                        'type'      => $inverse_type,
                        'action'    => (isset($next_order)) ? 'create' : 'push',
                        'rif_order' => (isset($next_order)) ? $next_order : null,
                        'data'      => $inverse_destination_match
                    ] );

                    $new_matches[] = $inverse_destination_match;

                    if( !empty($previous_inverse_match) ){
                        $previous_inverse_match         = $previous_inverse_match->toArray();
                        $previous_inverse_match['next'] = $inverse_destination_order;
                        $this->pushOperation( [
                            'type'      => $inverse_type,
                            'action'    => 'update',
                            'rif_order' => $previous_inverse_match['order'],
                            'data'      => $previous_inverse_match
                        ] );

                        $updated_matches[] = $previous_inverse_match;
                    }

                }

                $conn = NewDatabase::obtain()->getConnection();
                try {
                    $conn->beginTransaction();
                    $segmentsMatchDao = new Segments_SegmentMatchDao;
                    Segments_SegmentMatchDao::nullifySegmentsInMatches([$order], $id_job, $type);
                    $match_dao = new Segments_SegmentMatchDao();
                    if (!empty($referenceMatch)) {
                        $match_dao->updateList([$destination_match, $starting_match, $new_match_destination]);
                    } else {
                        $match_dao->createList($new_matches);
                        $match_dao->updateList($updated_matches);
                    }
                    $conn->commit();
                } catch (\PDOException $e) {
                    $conn->rollBack();
                    throw new \PDOException("Segment Move - DB Error: " . $e->getMessage(), -2);
                }
            } else {
                if (!empty ($referenceMatch)) {

                    // The original operation row had a full inverse match, we just need to put the segment_id back.

                    $destination_match = $referenceMatch;
                    $destination_match['segment_id'] = $movingSegment['id'];
                    $destination_match['content_raw'] = null;
                    $destination_match['content_clean'] = $movingSegment['content_clean'];
                    $destination_match['raw_word_count'] = $movingSegment['raw_word_count'];

                    $this->pushOperation([
                        'type' => $type,
                        'action' => 'update',
                        'rif_order' => $destination_order,
                        'data' => $destination_match
                    ]);

                } else {

                    $new_matches = [];
                    $updated_matches = [];

                    // The original operation row had an empty inverse match before the move. This means we have to recreate the row

                    $previous_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType($destination_order, $id_job, $type);

                    $next_match = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType($destination_order, $id_job, $type);
                    if (!empty($next_match)) {
                        $next_match = $next_match->toArray();
                        $next_order = $next_match['order'];
                    } else {
                        $next_order = null;
                    }

                    $destination_match['order'] = $destination_order;
                    $destination_match['next'] = $next_order;
                    $destination_match['id_job'] = $id_job;
                    $destination_match['type'] = $type;
                    $destination_match['segment_id'] = $movingSegment['id'];
                    $destination_match['content_raw'] = null;
                    $destination_match['content_clean'] = $movingSegment['content_clean'];
                    $destination_match['raw_word_count'] = $movingSegment['raw_word_count'];

                    $this->pushOperation([
                        'type' => $type,
                        'action' => (isset($next_order)) ? 'create' : 'push',
                        'rif_order' => (isset($next_order)) ? $next_order : null,
                        'data' => $destination_match
                    ]);

                    $new_matches[] = $destination_match;

                    if (!empty($previous_match)) {
                        $previous_match = $previous_match->toArray();
                        $previous_match['next'] = $destination_order;
                        $this->pushOperation([
                            'type' => $type,
                            'action' => 'update',
                            'rif_order' => $previous_match['order'],
                            'data' => $previous_match
                        ]);

                        $updated_matches[] = $previous_match;
                    }

                    $previous_inverse_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType($inverse_destination_order, $id_job, $inverse_type);

                    $next_inverse_match = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType($inverse_destination_order, $id_job, $inverse_type);
                    if (!empty($next_inverse_match)) {
                        $next_inverse_match = $next_inverse_match->toArray();
                        $next_inverse_order = $next_inverse_match['order'];
                    } else {
                        $next_inverse_order = null;
                    }

                    $inverse_destination_match['order'] = $inverse_destination_order;
                    $inverse_destination_match['next'] = $next_inverse_order;
                    $inverse_destination_match['id_job'] = $id_job;
                    $inverse_destination_match['type'] = $inverse_type;
                    $inverse_destination_match['segment_id'] = null;
                    $inverse_destination_match['content_raw'] = null;
                    $inverse_destination_match['content_clean'] = null;
                    $inverse_destination_match['raw_word_count'] = null;

                    $this->pushOperation([
                        'type' => $inverse_type,
                        'action' => (isset($next_order)) ? 'create' : 'push',
                        'rif_order' => (isset($next_order)) ? $next_order : null,
                        'data' => $inverse_destination_match
                    ]);

                    $new_matches[] = $inverse_destination_match;

                    if (!empty($previous_inverse_match)) {
                        $previous_inverse_match = $previous_inverse_match->toArray();
                        $previous_inverse_match['next'] = $inverse_destination_order;
                        $this->pushOperation([
                            'type' => $inverse_type,
                            'action' => 'update',
                            'rif_order' => $previous_inverse_match['order'],
                            'data' => $previous_inverse_match
                        ]);

                        $updated_matches[] = $previous_inverse_match;
                    }

                }

                $starting_match = $movingSegment;
                $starting_match['segment_id'] = null;
                $starting_match['content_raw'] = null;
                $starting_match['content_clean'] = null;
                $starting_match['raw_word_count'] = null;

                $updated_matches[] = $starting_match;

                $this->pushOperation([
                    'type' => $type,
                    'action' => 'update',
                    'rif_order' => $order,
                    'data' => $starting_match
                ]);

                $conn = NewDatabase::obtain()->getConnection();
                try {
                    $conn->beginTransaction();
                    Segments_SegmentMatchDao::nullifySegmentsInMatches([$order], $id_job, $type);
                    if (!empty($referenceMatch)) {
                        Segments_SegmentMatchDao::updateFields(['segment_id' => $movingSegment['id']], $destination_order, $id_job, $type);
                    } else {
                        $match_dao = new Segments_SegmentMatchDao();
                        $match_dao->createList($new_matches);
                        $match_dao->updateList($updated_matches);
                    }
                    $conn->commit();
                } catch (\PDOException $e) {
                    $conn->rollBack();
                    throw new \PDOException("Segment Move - DB Error: " . $e->getMessage(), -2);
                }
            }

        }


        // Now let's unmerge the segments

        $source_matches  = [];
        $source_inverses = [];
        $target_matches  = [];
        $target_inverses = [];

        /* In the direct action we fill the matches array after the orders to merge are
        sorted, so that we don't need to sort them again in the undo action.*/

        // TODO Add check on match orders (needs to have ascending orders)

        function sortByOrder($a, $b) {
            return (int)$a['order'] - (int)$b['order'];
        }

        foreach ($matches as $match) {
            if ( $match[ 'type' ] == 'target' ){
                $target_matches[] = $match;
            } else {
                $source_matches[] = $match;
            }
        }

        array_multisort(array_column($target_matches, 'order'), SORT_ASC, $target_matches);
        array_multisort(array_column($source_matches, 'order'), SORT_ASC, $source_matches);
        //usort($source_matches, "sortByOrder");

        foreach ($inverses as $inverse) {
            if ( $inverse[ 'type' ] == 'source' ){
                $target_inverses[] = $inverse;
            } else {
                $source_inverses[] = $inverse;
            }
        }

        array_multisort(array_column($target_inverses, 'order'), SORT_ASC, $target_inverses);
        array_multisort(array_column($source_inverses, 'order'), SORT_ASC, $source_inverses);

        //usort($target_inverses, "sortByOrder");
        //usort($source_inverses, "sortByOrder");

        $unmerge_structure = [
            [
                'order'        => $inverse_order,
                'type'         => 'source',
                'inverse_type' => 'target',
                'matches'      => $source_matches,
                'inverses'     => $source_inverses
            ],
            [
                'order'        => $destination_order,
                'type'         => 'target',
                'inverse_type' => 'source',
                'matches'      => $target_matches,
                'inverses'     => $target_inverses
            ]
        ];

        foreach ( $unmerge_structure as $structure ){

            $order           = $structure[ 'order' ];
            $type            = $structure[ 'type' ];
            $type            = $structure[ 'type' ];
            $inverse_matches = $structure[ 'inverses' ];
            $inverse_type    = ( $type == 'target' ) ? 'source' : 'target';
            $matches         = $structure[ 'matches' ];

            if ( empty( $matches ) ) {
                continue;
            }

            //In this section we find all the inverse segments of the previously merged segments

            $unmerged_segment = Segments_SegmentDao::getFromOrderJobIdAndType($order, $id_job, $type);
            $unmerged_match   = Segments_SegmentMatchDao::getSegmentMatch($order, $id_job, $type);
            if(!is_object($unmerged_segment)){
                throw new ValidationError("There's no segment with the parameters specified in the input");
            }
            $unmerged_segment = $unmerged_segment->toArray();
            $unmerged_match   = $unmerged_match->toArray();

            $clean_matches  = [];
            $clean_content  = $unmerged_segment[ 'content_clean' ];
            $matches[]      = [ 'position' => mb_strlen( $clean_content, 'UTF-8' ), 'order' => null ];

            foreach ( $matches as $key => $match ) {

                /*
                 * There is an unbalance between positions and orders ( because n splits = n + 1 segments )
                 * So we use $matches[ $key -1 ]['order'] as the clean match order to indicate that the first split
                 * segment should stay in its original place and all the following splits will be positioned in the
                 * position they were before the merge operation
                 */

                // $start has an hardcoded + 1 to remove all spaces created by merges after the first segment
                $start       = ( $key == 0 ) ? 0 : ($matches[ $key - 1 ]['position'] + 1);
                $match_order = ( $key == 0 ) ? $order : $matches[ $key - 1 ]['order'];

                $clean_substring = mb_substr( $clean_content, $start, ( $match['position'] ) - $start, 'UTF-8' );
                $clean_matches[] = [ 'order' => $match_order, 'content_clean' => $clean_substring];

            }

            // Create the ids for the sequence match list

            $first_segment = array_shift($clean_matches);
            $first_inverse = array_shift($inverse_matches);
            $first_clean   = $first_segment['content_clean'];
            $first_count   = \CatUtils::segment_raw_word_count( $first_segment, $this->job->{$unmerged_segment['type']} );

            $unmerged_segment[ 'content_clean' ]  = $first_clean;
            $unmerged_segment[ 'raw_word_count' ] = $first_count;
            $unmerged_segment[ 'id_job' ]         = $id_job;

            $unmerged_match[ 'segment_id' ]     = $unmerged_segment['id'];
            $unmerged_match[ 'content_clean' ]  = $first_clean;
            $unmerged_match[ 'raw_word_count' ] = $first_count;

            $matches_amount = count( $clean_matches );

            $new_ids = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, $matches_amount );

            if($matches_amount != count( $new_ids ) || $matches_amount != count( $inverse_matches ) ){
                throw new ValidationError("There are more inverse segments than segments to restore");
            }

            $new_segments             = [];
            $new_matches              = [];
            $null_matches             = [];
            $previous_matches         = [];
            $previous_inverse_matches = [];
            $next_matches             = [];
            $next_inverse_matches     = [];
            $updated_matches          = [];

            // We know for sure that the length of $new_ids, $clean_matches and $inverse_segments are the same
            // so instead of using a foreach loop a good old for loop will do the job better while we work with indexes

            for ($i = 0; $i < $matches_amount; $i++ ){

                $restored_segment = Segments_SegmentDao::getFromOrderJobIdAndType($clean_matches[$i]['order'], $id_job, $type);

                if( empty( $restored_segment ) ) {

                    $current_match         = $clean_matches[ $i ];
                    $current_inverse_match = $inverse_matches[ $i ];

                    $new_segment       = [];
                    $new_match         = [];
                    $new_inverse_match = [];

                    $new_segment[ 'id' ]       = $new_ids[ $i ];
                    $new_segment[ 'id_job' ]   = $id_job;
                    $new_segment[ 'type' ]     = $type;
                    $new_match[ 'segment_id' ] = $new_ids[ $i ];
                    $new_match[ 'id_job' ]     = $id_job;
                    $new_match[ 'type' ]       = $type;

                    $new_segment[ 'content_clean' ]  = $current_match[ 'content_clean' ];
                    $new_segment[ 'content_hash' ]   = null;
                    $new_segment[ 'raw_word_count' ] = 0;

                    // Restore the original segment_match

                    $previous_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $current_match[ 'order' ], $id_job, $type );
                    $next_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $current_match[ 'order' ], $id_job, $type );

                    if( isset( $previous_match ) ){
                        $previous_match           = $previous_match->toArray();
                        $previous_assigned        = false;
                        foreach ($previous_matches as $prev){
                            if($previous_match['order'] == $prev['order']){
                                $previous_assigned = true;
                            }
                        }
                        if(!$previous_assigned){
                            $previous_match[ 'next' ] = $current_match[ 'order' ];
                            $previous_matches[]       = $previous_match;
                        } else {
                            $new_matches[count($new_matches)-1]['next'] = $current_match['order'];
                        }
                    }

                    $new_match[ 'order' ]          = $current_match[ 'order' ];
                    $new_match[ 'content_clean' ]  = $current_match[ 'content_clean' ];
                    $new_match[ 'content_hash' ]   = null;
                    $new_match[ 'raw_word_count' ] = 0;

                    if( isset( $next_match ) ){
                        $next_match           = $next_match->toArray();
                        $new_match[ 'next' ]  = $next_match[ 'order' ];
                    } else {
                        $new_match[ 'next' ]  = null;
                    }

                    $next_matches[] = $next_match;

                    $new_matches[] = $new_match;

                    // Restore the original null segment before it got deleted during the merge

                    $previous_inverse_match = Segments_SegmentDao::getPreviousFromNonExistentOrderJobIdAndType( $current_inverse_match[ 'order' ], $id_job, $inverse_type );
                    $next_inverse_match     = Segments_SegmentDao::getNextFromNonExistentOrderJobIdAndType( $current_inverse_match[ 'order' ], $id_job, $inverse_type );

                    if( isset( $previous_inverse_match ) ){
                        $previous_inverse_match           = $previous_inverse_match->toArray();
                        $previous_inverse_assigned        = false;
                        foreach ($previous_inverse_matches as $prev){
                            if($previous_inverse_match['order'] == $prev['order']){
                                $previous_inverse_assigned = true;
                            }
                        }
                        if(!$previous_inverse_assigned){
                            $previous_inverse_match[ 'next' ] = $current_inverse_match[ 'order' ];
                            $previous_inverse_matches[]       = $previous_inverse_match;
                        } else {
                            $null_matches[count($null_matches)-1]['next'] = $current_inverse_match['order'];
                        }
                    }

                    $new_inverse_match[ 'order' ]  = $current_inverse_match[ 'order' ];
                    $new_inverse_match[ 'id_job' ] = $id_job;
                    $new_inverse_match[ 'type' ]   = $inverse_type;

                    if( isset( $next_inverse_match ) ){
                        $next_inverse_match           = $next_inverse_match->toArray();
                        $new_inverse_match[ 'next' ]  = $next_inverse_match[ 'order' ];
                    } else {
                        $new_inverse_match[ 'next' ]  = null;
                    }

                    $new_segments[] = $new_segment;

                    $next_inverse_matches[] = $next_inverse_match;

                    $new_inverse_match['segment_id'] = null;

                    $null_matches[] = $new_inverse_match;

                } else {

                    $new_segment = $restored_segment->toArray();
                    $new_match   = $restored_segment->toArray();

                    $new_segment[ 'id' ]       = $new_ids[ $i ];
                    $new_match[ 'segment_id' ] = $new_ids[ $i ];

                    $new_match[ 'content_clean' ]  = $clean_matches[ $i ][ 'content_clean' ];
                    $new_match[ 'raw_word_count' ] = \CatUtils::segment_raw_word_count( $new_segment[ 'content_raw' ], $this->job->{ $new_segment[ 'type' ] }  );

                    $new_segment[ 'content_clean' ]  = $clean_matches[ $i ][ 'content_clean' ];
                    $new_segment[ 'content_hash' ]   = null;
                    $new_segment[ 'raw_word_count' ] = \CatUtils::segment_raw_word_count( $new_segment[ 'content_raw' ], $this->job->{ $new_segment[ 'type' ] }  );

                    $new_segments[]    = $new_segment;
                    $updated_matches[] = $new_match;
                }

            }

            try{

                foreach ($new_matches as $key => $match){
                    $this->pushOperation([
                        'type'      => $type,
                        'action'    => ( isset( $next_matches[$key] ) ) ? 'create' : 'push',
                        'rif_order' => ( isset( $next_matches[$key] ) ) ? $next_matches[$key]['order'] : null,
                        'data'      => $match,
                    ]);
                }

                foreach ($null_matches as $key => $match){
                    $this->pushOperation([
                        'type'      => $inverse_type,
                        'action'    => ( isset( $next_inverse_matches[$key] ) ) ? 'create' : 'push',
                        'rif_order' => ( isset( $next_inverse_matches[$key] ) ) ? $next_inverse_matches[$key]['order'] : null,
                        'data'      => $match,
                    ]);
                }


                foreach ($previous_matches as $match){
                    $this->pushOperation([
                        'type'      => $type,
                        'action'    => 'update',
                        'rif_order' => $match['order'],
                        'data'      => $match,
                    ]);
                }

                foreach ($previous_inverse_matches as $match){
                    $this->pushOperation([
                        'type'      => $inverse_type,
                        'action'    => 'update',
                        'rif_order' => $match['order'],
                        'data'      => $match,
                    ]);
                }

                $this->pushOperation([
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $unmerged_match['order'],
                    'data'      => $unmerged_match,
                ]);

                foreach ($updated_matches as $match){
                    $this->pushOperation([
                        'type'      => $type,
                        'action'    => 'update',
                        'rif_order' => $match['order'],
                        'data'      => $match,
                    ]);
                }


            } catch ( ValidationError $e ) {
                throw new ValidationError( $e->getMessage(), -2 );
            }

            $conn = NewDatabase::obtain()->getConnection();
            try {

                $conn->beginTransaction();

                $segmentsDao = new Segments_SegmentDao;
                $segmentsDao->createList( array_merge( $new_segments) );

                $segmentsMatchDao = new Segments_SegmentMatchDao;
                $segmentsMatchDao->createList( array_merge( $new_matches, $null_matches ) );
                $segmentsMatchDao->updateList( array_merge( [ $unmerged_match ], $previous_matches, $previous_inverse_matches, $updated_matches ) );

                Segments_SegmentDao::updateSegmentContent( $unmerged_segment[ 'id' ], [ $first_clean, $first_count ] );

                $conn->commit();

            } catch ( \PDOException $e ) {
                $conn->rollBack();
                throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
            }

        }

        return $this->getOperations();
        
    }

    public function undoShow() {

        $id_job = $this->job->id;

        $matches = $this->params[ 'matches' ];

        $conn = NewDatabase::obtain()->getConnection();

        try {
            $conn->beginTransaction();
            foreach ( $matches as $match ) {

                Segments_SegmentMatchDao::hideByOrderAndType( $match[ 'source' ], $id_job, "source" );
                Segments_SegmentMatchDao::hideByOrderAndType( $match[ 'target' ], $id_job, "target" );

                $source = Segments_SegmentDao::getFromOrderJobIdAndType(
                    $match [ 'source' ],
                    $id_job , 'source' );

                $target = Segments_SegmentDao::getFromOrderJobIdAndType(
                    $match [ 'target' ],
                    $id_job , 'target' );

                $this->pushOperation( [
                    'type'      => 'source',
                    'action'    => 'update',
                    'rif_order' => $match[ 'source' ],
                    'data'      => $source->toArray()
                ] );

                $this->pushOperation( [
                    'type'      => 'target',
                    'action'    => 'update',
                    'rif_order' => $match[ 'target' ],
                    'data'      => $target->toArray()
                ] );

            }

            $conn->commit();

        } catch ( \PDOException $e){
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage() . " - Hide  ", -2 );
        } catch (ValidationError $e ){
            throw new ValidationError( "Segment update - DB Error: " . $e->getMessage() . " - Hide  ", -2 );
        }

        return $this->getOperations();

    }

    protected function undoHideBoth($id_job, $match){

        Segments_SegmentMatchDao::showByOrderAndType($match['source'], $id_job, "source");
        Segments_SegmentMatchDao::showByOrderAndType($match['target'], $id_job, "target");

        $source = Segments_SegmentDao::getFromOrderJobIdAndType(
            $match ['source'],
            $id_job, 'source');

        $target = Segments_SegmentDao::getFromOrderJobIdAndType(
            $match ['target'],
            $id_job, 'target');

        $this->pushOperation([
            'type' => 'source',
            'action' => 'update',
            'rif_order' => $match['source'],
            'data' => $source->toArray()
        ]);

        $this->pushOperation([
            'type' => 'target',
            'action' => 'update',
            'rif_order' => $match['target'],
            'data' => $target->toArray()
        ]);

    }

    protected function undoHideSingle($id_job, $match){
        $old_match    = $match['old_match'];
        $new_match    = $match['new_match'];
        $type         = $match['type'];
        $inverse_type = ( $type == 'target' ) ? 'source' : 'target';

        Segments_SegmentMatchDao::showByOrderAndType($old_match["source"], $id_job, "source");
        Segments_SegmentMatchDao::showByOrderAndType($new_match["source"], $id_job, "source");
        Segments_SegmentMatchDao::showByOrderAndType($old_match["target"], $id_job, "target");
        Segments_SegmentMatchDao::showByOrderAndType($new_match["target"], $id_job, "target");

        $order             = $new_match[$type];
        $destination_order = $old_match[$type];
        $inverse_order     = $old_match[$inverse_type];

        $movingSegment      = Segments_SegmentDao::getFromOrderJobIdAndType($order, $id_job, $type);
        $destinationSegment = Segments_SegmentDao::getFromOrderJobIdAndType($destination_order, $id_job, $type);
        $inverseSegment     = Segments_SegmentDao::getFromOrderJobIdAndType($inverse_order, $id_job, $inverse_type);

        $movingSegment      = $movingSegment->toArray();
        $destinationSegment = $destinationSegment->toArray();
        $inverseSegment     = $inverseSegment->toArray();

        $destination_match                     = $destinationSegment;
        $destination_match[ 'segment_id' ]     = $movingSegment[ 'id' ];
        $destination_match[ 'content_raw' ]    = null;
        $destination_match[ 'content_clean' ]  = $movingSegment[ 'content_clean' ];
        $destination_match[ 'raw_word_count' ] = $movingSegment[ 'raw_word_count' ];
        $destination_match[ 'hidden' ]         = 0;

        $this->pushOperation( [
            'type'      => $type,
            'action'    => 'update',
            'rif_order' => $destination_order,
            'data'      => $destination_match
        ] );

        $starting_match                     = $movingSegment;
        $starting_match[ 'segment_id' ]     = null;
        $starting_match[ 'content_raw' ]    = null;
        $starting_match[ 'content_clean' ]  = null;
        $starting_match[ 'raw_word_count' ] = null;
        $starting_match[ 'hidden' ]         = 0;

        $this->pushOperation( [
            'type'      => $type,
            'action'    => 'update',
            'rif_order' => $order,
            'data'      => $starting_match
        ] );

        $inverse_match           = $inverseSegment;
        $inverse_match['hidden'] = 0;

        $this->pushOperation( [
            'type'      => $inverse_type,
            'action'    => 'update',
            'rif_order' => $inverse_order,
            'data'      => $inverse_match
        ] );

        Segments_SegmentMatchDao::nullifySegmentsInMatches( [ $order ], $id_job, $type );
        Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $movingSegment[ 'id' ] ], $destination_order, $id_job, $type );
    }

    public function undoHide() {

        $id_job = $this->job->id;
        $matches = $this->params['matches'];

        $conn = NewDatabase::obtain()->getConnection();

        try {
            $conn->beginTransaction();

            foreach ($matches as $match) {

                $hideType = $match['hide_type'];
                if ($hideType == 'both') {
                    $this->undoHideBoth($id_job, $match);
                } elseif ($hideType == 'single') {
                    $this->undoHideSingle($id_job, $match);
                } else {
                    throw new ValidationError('This request is not valid');
                }

            }
            $conn->commit();

        } catch ( \PDOException $e){
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage() . " - Show  ", -2 );
        } catch (ValidationError $e ){
            throw new ValidationError( "Segment update - DB Error: " . $e->getMessage() . " - Show  ", -2 );
        }

        return $this->getOperations();

    }

}