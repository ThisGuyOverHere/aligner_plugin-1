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

        foreach ( $segments as $key => $segment ) {
            if ( $key != 0 ) {
                $deleted_orders[] = $segment[ 'order' ];
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

            foreach ( $deleted_orders as $order ) {
                $this->pushOperation( [
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $order
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

            $start       = ( $key == 0 ) ? 0 : $matches[ $key - 1 ]['position'];
            $match_order = ( $key == 0 ) ? $order : $matches[ $key - 1 ]['order'];

            $clean_substring = mb_substr( $clean_content, $start, ( $match['position'] ) - $start, 'UTF-8' );
            $clean_matches[] = [ 'order' => $match_order, 'content_clean' => $clean_substring];

        }

        // Create the ids for the sequence match list

        //TODO: Update the first segment
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

}