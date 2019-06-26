<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 11/09/18
 * Time: 14:33
 */

namespace Features\Aligner\Controller;

use Features\Aligner\Controller\Validators\JobPasswordValidator;
use Exceptions\ValidationError;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Model\Segments_SegmentMatchStruct;
use Features\Aligner\Model\Segments_SegmentStruct;
use Features\Aligner\Utils\AlignUtils;
use Features\Aligner\Utils\Constants;

class JobDirectActionController extends JobActionController {


    public function merge() {

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
            $first_segment = Segments_SegmentDao::mergeSegments( $segments );
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


    public function split() {
        $id_job   = $this->job->id;

        $order         = $this->params[ 'order' ];
        $type          = $this->params[ 'type' ];
        $inverse_order = $this->params[ 'inverse_order' ];
        $inverse_type  = ( $type == 'target' ) ? 'source' : 'target';
        $positions     = $this->params[ 'positions' ];

        if ( empty( $positions ) ) {
            return true;
        }

        //Gets from 0 since they are returned as an array
        $split_segment   = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type );
        if(!is_object($split_segment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $split_segment   = $split_segment->toArray();

        $inverse_segment = Segments_SegmentDao::getFromOrderJobIdAndType( $inverse_order, $id_job, $inverse_type );
        if(!is_object($inverse_segment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $inverse_segment = $inverse_segment->toArray();

        $avg_order   = AlignUtils::_getNewOrderValue( $split_segment[ 'order' ], $split_segment[ 'next' ] );
        $inverse_avg = AlignUtils::_getNewOrderValue( $inverse_segment[ 'order' ], $inverse_segment[ 'next' ] );
      
        $original_next         = $split_segment[ 'next' ];
        $original_inverse_next = $inverse_segment[ 'next' ];

        $split_segment[ 'next' ]    = $avg_order;
        $inverse_segment[ 'next' ]  = $inverse_avg;

        //$raw_contents = [];
        //$full_raw     = AlignUtils::_mark_xliff_tags( $split_segment[ 'content_raw' ] );
        //$positions[]  = mb_strlen( $full_raw, 'UTF-8' );

        $clean_contents  = [];
        $clean_content = $split_segment[ 'content_clean' ];
        $positions[]   = mb_strlen( $clean_content, 'UTF-8' );
        foreach ( $positions as $key => $position ) {

            //$start          = ( $key == 0 ) ? 0 : $positions[ $key - 1 ] + 1;
            //$raw_substring  = mb_substr( $full_raw, $start, ( $position + 1 ) - $start, 'UTF-8' );
            //$raw_contents[] = AlignUtils::_restore_xliff_tags( $raw_substring );

            $start              = ( $key == 0 ) ? 0 : $positions[ $key - 1 ];
            $clean_substring    = mb_substr( $clean_content, $start, ( $position ) - $start, 'UTF-8' );
            $clean_contents[]   = $clean_substring;
        }

        //$first_raw   = array_shift( $raw_contents );
        //$first_clean = AlignUtils::_cleanSegment( $first_raw, $this->job->{$split_segment['type']}  ); //
        //$first_count = \CatUtils::segment_raw_word_count( $first_raw, $this->job->{$split_segment['type']} );

        $first_clean = array_shift( $clean_contents );
        $first_count = \CatUtils::segment_raw_word_count( $first_clean, $this->job->{$split_segment['type']} );

        $new_segment = $split_segment;
        $new_match   = $split_segment;
        $null_match  = $inverse_segment;

        //$split_segment[ 'content_raw' ]  = $first_raw;
        $split_segment[ 'content_raw' ]    = null;
        $split_segment[ 'content_clean' ]  = $first_clean;
        $split_segment[ 'content_hash' ]   = null;
        $split_segment[ 'raw_word_count' ] = $first_count;

        $update_order         = $avg_order;
        $inverse_update_order = $inverse_avg;

        //$new_ids        = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count( $raw_contents ) );
        $new_ids          = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count( $clean_contents ) );
        $new_segments     = [];
        $null_segments    = [];
        $new_matches      = [];
        $new_null_matches = [];

        $null_segment                     = $inverse_segment;
        $null_segment[ 'id' ]             = null;
        $null_segment[ 'content_raw' ]    = null;
        $null_segment[ 'content_clean' ]  = null;
        $null_segment[ 'content_hash' ]   = null;
        $null_segment[ 'raw_word_count' ] = null;

        foreach ( $new_ids as $key => $id ) {

            //create new segments
            $new_segment[ 'id' ]             = $id;

            //$new_segment[ 'content_raw' ]    = array_shift( $raw_contents );
            //$new_segment[ 'content_clean' ]  = AlignUtils::_cleanSegment( $new_segment[ 'content_raw' ], $this->job->{$new_segment[ 'type' ]} );
            
            $new_segment[ 'content_clean' ]  = array_shift( $clean_contents );
            $new_segment[ 'content_hash' ]   = null;
            $new_segment[ 'raw_word_count' ] = \CatUtils::segment_raw_word_count( $new_segment[ 'content_raw' ], $this->job->{$new_segment[ 'type' ]}  );

            //create new matches
            $new_match[ 'segment_id' ] = $id;
            $new_match[ 'order' ]      = $avg_order;
            $new_segment[ 'order' ]    = $new_match[ 'order' ];

            //If we split the last segment we add new next values for the new segments
            $avg_order = ( $split_segment[ 'next' ] != null ) ? AlignUtils::_getNewOrderValue( $new_match[ 'order' ], $original_next ) : $avg_order + Constants::DISTANCE_INT_BETWEEN_MATCHES;

            $new_match[ 'next' ]   = ( $key != count( $new_ids ) - 1 ) ? $avg_order : $original_next;
            $new_segment[ 'next' ] = $new_match[ 'next' ];
            $new_matches[]         = $new_match;

            //create new null matches
            $null_match[ 'segment_id' ] = null;
            $null_match[ 'order' ]      = $inverse_avg;
            $null_segment[ 'order' ]    = $inverse_avg;

            //If we split the last segment we add new next values for the new segments
            $inverse_avg = ( $inverse_segment[ 'next' ] != null ) ? AlignUtils::_getNewOrderValue( $null_match[ 'order' ], $original_inverse_next ) : $inverse_avg + Constants::DISTANCE_INT_BETWEEN_MATCHES;

            $null_match[ 'next' ]   = ( $key != count( $new_ids ) - 1 ) ? $inverse_avg : $original_inverse_next;
            $null_segment[ 'next' ] = $null_match[ 'next' ];
            $new_null_matches[]     = $null_match;

            $new_segments[]  = $new_segment;
            $null_segments[] = $null_segment;
        }


        //New segment creation
        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();

            $segmentsDao = new Segments_SegmentDao;
            $segmentsDao->createList( $new_segments );

            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( array_merge( $new_matches, $new_null_matches ) );

            Segments_SegmentDao::updateSegmentContent( $split_segment [ 'id' ], [ $first_clean, $first_count ] );
            Segments_SegmentMatchDao::updateNextSegmentMatch( $update_order, $order, $id_job, $type );
            Segments_SegmentMatchDao::updateNextSegmentMatch( $inverse_update_order, $inverse_order, $id_job, $inverse_type );

            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        //Check which segments to retrieve for source/target
        $source_start = ( $type == 'source' ) ? $split_segment[ 'order' ] : $inverse_segment[ 'order' ];
        $source_end   = ( $type == 'source' ) ? $original_next : $original_inverse_next;
        $target_start = ( $type == 'target' ) ? $split_segment[ 'order' ] : $inverse_segment[ 'order' ];
        $target_end   = ( $type == 'target' ) ? $original_next : $original_inverse_next;

        $segments       = array_merge( [ $split_segment ], $new_segments );
        $sourceSegments = ( $type == 'source' ) ? $segments : array_merge( [ $inverse_segment ], $null_segments );
        $targetSegments = ( $type == 'target' ) ? $segments : array_merge( [ $inverse_segment ], $null_segments );

        $orders = [];

        foreach ($segments as $segment){
            $orders[] = $segment['order'];
        }

        try{

            $this->setUndoActionsParams([
                'order'     => $orders,
                'type'      => $type,
                'operation' => 'split',
            ]);

            $this->pushOperation( [
                'type'      => 'source',
                'action'    => 'update',
                'rif_order' => $source_start,
                'data'      => array_shift( $sourceSegments )
            ] );

            foreach ( $sourceSegments as $sourceSegment ) {

                $this->pushOperation( [
                    'type'      => 'source',
                    'action'    => ($source_end !== null) ? 'create': 'push',
                    'rif_order' => $source_end,
                    'data'      => $sourceSegment
                ] );

            }

            $this->pushOperation( [
                'type'      => 'target',
                'action'    => 'update',
                'rif_order' => $target_start,
                'data'      => array_shift( $targetSegments )
            ] );

            foreach ( $targetSegments as $targetSegment ) {

                $this->pushOperation( [
                    'type'      => 'target',
                    'action'    => ($target_end !== null) ? 'create' : 'push',
                    'rif_order' => $target_end,
                    'data'      => $targetSegment
                ] );

            }

        } catch ( ValidationError $e ) {
            throw new ValidationError( $e->getMessage(), -2 );
        }

        return $this->getResponse();
    }

    protected function moveInEmpty($referenceMatch){

        $id_job   = $this->job->id;

        $order                     = $this->params[ 'order' ];
        $type                      = $this->params[ 'type' ];
        $destination_order         = $this->params[ 'destination' ];

        if($order == $destination_order){ return $this->getOperations();}

        $movingSegment = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type );
        if(!is_object($movingSegment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $movingSegment = $movingSegment->toArray();

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

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            Segments_SegmentMatchDao::nullifySegmentsInMatches( [ $order ], $id_job, $type );
            Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $movingSegment[ 'id' ] ], $destination_order, $id_job, $type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment Move - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();


    }

    protected function moveInFill($referenceMatch){

        $id_job   = $this->job->id;

        $order                     = $this->params[ 'order' ];
        $type                      = $this->params[ 'type' ];
        $inverse_type              = ( $type == 'target' ) ? 'source' : 'target';
        $destination_order         = $this->params[ 'destination' ];
        $inverse_destination_order = $this->params[ 'inverse_destination' ];

        if($order == $destination_order){ return $this->getOperations();}

        $movingSegment = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type );
        if(!is_object($movingSegment)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $movingSegment = $movingSegment->toArray();

        $new_match_order = AlignUtils::_getNewOrderValue( $destination_order, $referenceMatch['next'] );

        $destination_match                   = $referenceMatch;

        $destination_match['segment_id']     = $movingSegment['id'];
        $destination_match['content_raw']    = null;
        $destination_match['content_clean']  = $movingSegment['content_clean'];
        $destination_match['raw_word_count'] = $movingSegment['raw_word_count'];
        $destination_match['next']           = $new_match_order;

        $this->pushOperation( [
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => $destination_order,
                'data'      => $destination_match
        ] );

        $starting_match                   = $movingSegment;
        $starting_match['segment_id']     = null;
        $starting_match['content_raw']    = null;
        $starting_match['content_clean']  = null;
        $starting_match['raw_word_count'] = null;

        $this->pushOperation( [
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => $order,
                'data'      => $starting_match
        ] );

        $new_match_destination = [];

        $new_match_destination[ 'order' ]          = $new_match_order;
        $new_match_destination[ 'next' ]           = $referenceMatch['next'];
        $new_match_destination[ 'score' ]          = 100;
        $new_match_destination[ 'segment_id' ]     = $referenceMatch[ 'id' ];
        $new_match_destination[ 'type' ]           = $type;
        $new_match_destination[ 'id_job' ]         = $id_job;
        $new_match_destination[ 'content_raw' ]    = null;
        $new_match_destination[ 'content_clean' ]  = $referenceMatch['content_clean'];
        $new_match_destination[ 'raw_word_count' ] = $referenceMatch['raw_word_count'];

        $this->pushOperation( [
                'type'      => $type,
                'action'    => 'create',
                'rif_order' => $destination_order,
                'data'      => $new_match_destination
        ] );

        $inverseReference = Segments_SegmentMatchDao::getSegmentMatch( $inverse_destination_order, $id_job, $inverse_type );
        if(!is_object($inverseReference)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $inverseReference = $inverseReference->toArray();

        $new_match_null = [];
        $new_inverse_order           = AlignUtils::_getNewOrderValue( $inverseReference[ 'order' ], $inverseReference[ 'next' ] );
        $new_match_null[ 'order' ]          = $new_inverse_order;
        $new_match_null[ 'next' ]           = $inverseReference[ 'next' ];
        $new_match_null[ 'score' ]          = 100;
        $new_match_null[ 'segment_id' ]     = null;
        $new_match_null[ 'type' ]           = $inverse_type;
        $new_match_null[ 'id_job' ]         = $id_job;
        $new_match_null[ 'content_raw' ]    = null;
        $new_match_null[ 'content_clean' ]  = null;
        $new_match_null[ 'raw_word_count' ] = null;

        $this->pushOperation( [
                'type'      => $inverse_type,
                'action'    => 'create',
                'rif_order' => $inverseReference[ 'order' ],
                'data'      => $new_match_null
        ] );

        $inverseReference['next'] = $new_inverse_order;
        $this->pushOperation( [
                'type'      => $inverse_type,
                'action'    => 'update',
                'rif_order' => $inverseReference['order'],
                'data'      => $inverseReference
        ] );

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( [ $new_match_destination, $new_match_null ] );
            Segments_SegmentMatchDao::nullifySegmentsInMatches( [ $order ], $id_job, $type );
            Segments_SegmentMatchDao::updateFields( ['segment_id' => $movingSegment['id'], 'next' => $new_match_order], $destination_order, $id_job, $type );
            Segments_SegmentMatchDao::updateFields( ['next' => $new_inverse_order], $inverse_destination_order, $id_job, $inverse_type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment Move - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    public function move() {

        //TODO Insert empty/full move_type value in undo_actions_params

        //TODO Use inverse also for the starting position of the moving match

        $id_job   = $this->job->id;

        $type                = $this->params[ 'type' ];
        $destination_order   = $this->params[ 'destination' ];

        $referenceMatch = Segments_SegmentDao::getFromOrderJobIdAndType( $destination_order, $id_job, $type );
        if(!is_object($referenceMatch)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $referenceMatch = $referenceMatch->toArray();

        if(!empty($referenceMatch['id'])){
            return $this->moveInFill($referenceMatch);
        }
        else {
            return $this->moveInEmpty($referenceMatch);
        }

    }


    public function addGap() {

        $order      = $this->params[ 'order' ];
        $id_job     = $this->params[ 'id_job' ];
        $type       = $this->params[ 'type' ];
        $other_type = ( $type == 'target' ) ? 'source' : 'target';

        $gap_match     = [];
        $balance_match = [];

        $previous_match = Segments_SegmentMatchDao::getPreviousSegmentMatch( $order, $id_job, $type );
        if(!is_object($previous_match)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $previous_match = $previous_match->toArray();
        $previous_order = ( empty( $previous_match ) ) ? 0 : $previous_match[ 'order' ];


        try{

            $gap_match[ 'order' ]          = AlignUtils::_getNewOrderValue( $previous_order, $order );
            $gap_match[ 'next' ]           = $order;
            $gap_match[ 'segment_id' ]     = null;
            $gap_match[ 'score' ]          = 100;
            $gap_match[ 'id_job' ]         = $id_job;
            $gap_match[ 'type' ]           = $type;
            $gap_match[ 'content_raw' ]    = null;
            $gap_match[ 'content_clean' ]  = null;
            $gap_match[ 'raw_word_count' ] = null;

            $this->pushOperation( [
                'type'      => $type,
                'action'    => 'create',
                'rif_order' => $order,
                'data'      => $gap_match
            ] );

            $last_match = Segments_SegmentMatchDao::getLastSegmentMatch( $id_job, $other_type )->toArray();

            $last_match[ 'order' ]             = $last_match[ 'order' ];
            $balance_match[ 'order' ]          = $last_match[ 'order' ] + Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $balance_match[ 'next' ]           = null;
            $balance_match[ 'segment_id' ]     = null;
            $balance_match[ 'score' ]          = 100;
            $balance_match[ 'id_job' ]         = $id_job;
            $balance_match[ 'type' ]           = $other_type;
            $balance_match[ 'content_raw' ]    = null;
            $balance_match[ 'content_clean' ]  = null;
            $balance_match[ 'raw_word_count' ] = null;

            $this->pushOperation( [
                'type'   => $other_type,
                'action' => 'push',
                'data'   => $balance_match
            ] );

            if ( !empty( $previous_match ) ) {

                $previous_match[ 'next' ] = $gap_match[ 'order' ];

                $previous_segment = Segments_SegmentDao::getFromOrderJobIdAndType( $previous_order, $id_job, $type )->toArray();


                $previous_segment[ 'next' ] = $gap_match[ 'order' ];

                $this->pushOperation( [
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => $previous_match[ 'order' ],
                    'data'      => $previous_segment
                ] );

            }

            $last_match[ 'next' ] = $balance_match[ 'order' ];

            $last_segment = Segments_SegmentDao::getFromOrderJobIdAndType( $last_match[ 'order' ], $id_job, $type );
            if ( !empty( $last_segment ) ) {
                $last_segment = $last_segment->toArray();
            }
            $last_segment[ 'type' ]           = $other_type;
            $last_segment[ 'content_raw' ]    = ( !empty( $last_segment[ 'content_raw' ] ) ) ? $last_segment[ 'content_raw' ] : null;
            $last_segment[ 'content_clean' ]  = ( !empty( $last_segment[ 'content_clean' ] ) ) ? $last_segment[ 'content_clean' ] : null;
            $last_segment[ 'raw_word_count' ] = ( !empty( $last_segment[ 'raw_word_count' ] ) ) ? $last_segment[ 'raw_word_count' ] : null;


            $this->pushOperation( [
                'type'      => $other_type,
                'action'    => 'update',
                'rif_order' => $last_match[ 'order' ],
                'data'      => $last_segment
            ] );

        } catch ( ValidationError $e ) {
            throw new ValidationError( $e->getMessage(), -2 );
        }

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();

            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( [ $gap_match, $balance_match ] );

            if ( !empty( $previous_match ) ) {
                Segments_SegmentMatchDao::updateNextSegmentMatch( $gap_match[ 'order' ], $previous_order, $id_job, $type );
            }
            Segments_SegmentMatchDao::updateNextSegmentMatch( $balance_match[ 'order' ], $last_match[ 'order' ], $id_job, $other_type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage() . " - Order no. $order ", -2 );
        }

        return $this->getOperations();
    }

    public function delete() {

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

        if ( count( $targets ) != count( $sources ) ) {
            throw new ValidationError( "There is a different amount of source matches and target matches, Deletion cancelled", -2 );
        }

        $sourceMatches = Segments_SegmentMatchDao::getMatchesFromOrderArray( $sources, $id_job, 'source' );
        $targetMatches = Segments_SegmentMatchDao::getMatchesFromOrderArray( $targets, $id_job, 'target' );

        foreach ( $sourceMatches as $sourceMatch ) {
            if ( $sourceMatch[ 'segment_id' ] != null ) {
                throw new ValidationError( "Segment Matches contain reference to existing segments, Deletion cancelled", -2 );
            }
        }

        foreach ( $targetMatches as $targetMatch ) {
            if ( $targetMatch[ 'segment_id' ] != null ) {
                throw new ValidationError( "Segment Matches contain reference to existing segments, Deletion cancelled", -2 );
            }
        }

        foreach ( $sourceMatches as $sourceMatch ) {
            $this->pushOperation( [
                    'type'      => "source",
                    'action'    => "delete",
                    'rif_order' => $sourceMatch['order'],
            ] );

            //query to take previous match
            $previousSourceMatch = Segments_SegmentMatchDao::getPreviousSegmentMatch($sourceMatch['order'], $id_job, "source")->toArray();
            $previousSourceMatch['next'] = $sourceMatch['next'];

            $this->pushOperation( [
                    'type'      => "source",
                    'action'    => "update",
                    'rif_order' => $previousSourceMatch['order'],
                    'data'      => $previousSourceMatch
            ] );
        }

        foreach ( $targetMatches as $targetMatch ) {
            $this->pushOperation( [
                    'type'      => "target",
                    'action'    => "delete",
                    'rif_order' => $targetMatch['order']
            ] );

            //query to take previous match
            $previousTargetMatch = Segments_SegmentMatchDao::getPreviousSegmentMatch($targetMatch['order'], $id_job, "source")->toArray();
            $previousTargetMatch['next'] = $targetMatch['next'];

            $this->pushOperation( [
                    'type'      => "source",
                    'action'    => "update",
                    'rif_order' => $previousTargetMatch['order'],
                    'data'      => $previousTargetMatch
            ] );
        }

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();

            if ( !empty( $sources ) ) {
                foreach($sources as $source){
                    Segments_SegmentMatchDao::updateMatchBeforeDeletion( $source, $id_job, 'source' );
                    Segments_SegmentMatchDao::deleteMatch( $source, $id_job, 'source' );
                }

            }
            if ( !empty( $targets ) ) {
                foreach($targets as $target){
                    Segments_SegmentMatchDao::updateMatchBeforeDeletion( $target, $id_job, 'target' );
                    Segments_SegmentMatchDao::deleteMatch( $target, $id_job, 'target' );
                }

            }
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    public function switchAction() {

        $id_job   = $this->job->id;

        $type   = $this->params[ 'type' ];
        $order1 = $this->params[ 'order1' ];
        $order2 = $this->params[ 'order2' ];

        try{
            $this->setUndoActionsParams([
                'order1'    => $order2,
                'order2'    => $order1,
                'type'      => $type,
                'operation' => "switch"
            ]);
        } catch (ValidationError $e){
            throw new ValidationError( $e->getMessage(), -2 );
        }

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

        return $this->getResponse();

    }

    public function mergeAndAlign() {

        $id_job   = $this->job->id;

        $matches           = $this->params[ 'matches' ];
        $destination_order = $this->params[ 'destination' ];

        $sources        = [];
        $targets        = [];
        $sourceOrders   = [];
        $targetOrders   = [];

        foreach ( $matches as $match ) {
            if ( $match[ 'type' ] == 'target' ){
                $targetOrders[] = $match['order'];
            } else {
                $sourceOrders[] = $match['order'];
            }
        }

        sort($sourceOrders, SORT_NUMERIC);
        sort($targetOrders, SORT_NUMERIC);

        foreach ( $sourceOrders as $order ) {
            $source = Segments_SegmentDao::getFromOrderJobIdAndType($order, $id_job, 'source');
            if(!is_object($source)){
                throw new ValidationError("There's no segment with the parameters specified in the input");
            }
            $sources[] = $source->toArray();
        }
        foreach ( $targetOrders as $order ) {
            $target = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, 'target' );
            if(!is_object($target)){
                throw new ValidationError("There's no segment with the parameters specified in the input");
            }
            $targets[] = $target->toArray();
        }

        $first_source = $sources[0];
        $first_target = $targets[0];

        if($first_target['order'] != $destination_order){
            $referenceMatch = Segments_SegmentDao::getFromOrderJobIdAndType( $destination_order, $id_job, 'target' );
            if(!is_object($referenceMatch)){
                throw new ValidationError("There's no segment with the parameters specified in the input");
            }
            $referenceMatch = $referenceMatch->toArray();

            $new_match_order = AlignUtils::_getNewOrderValue( $destination_order, $referenceMatch['next'] );

            $starting_match = $first_target;
            $starting_match['segment_id'] = null;
            $starting_match['content_raw'] = null;
            $starting_match['content_clean'] = null;
            $starting_match['raw_word_count'] = null;

            $destination_match = $referenceMatch;
            $destination_match['segment_id'] = $first_target['id'];
            $destination_match['next'] = $new_match_order;

            $new_match_destination = [];
            $new_match_destination[ 'order' ]          = $new_match_order;
            $new_match_destination[ 'next' ]           = $referenceMatch['next'];
            $new_match_destination[ 'score' ]          = 100;
            $new_match_destination[ 'type' ]           = 'target';
            $new_match_destination[ 'id_job' ]         = $id_job;
            
            /* Checks if the destination segment has been already merged
            so that it won't assign a segment id that no longer exists */
            if(!in_array($destination_order, $targetOrders)){
                $new_match_destination[ 'segment_id' ]     = $referenceMatch[ 'id' ];
                $new_match_destination[ 'content_raw' ]    = null;
                $new_match_destination[ 'content_clean' ]  = $referenceMatch['content_clean'];
                $new_match_destination[ 'raw_word_count' ] = $referenceMatch['raw_word_count'];
            }

            $inverseReference  = Segments_SegmentMatchDao::getSegmentMatch( $first_source['order'], $id_job, 'source' );
            if(!is_object($inverseReference)){
                throw new ValidationError("There's no segment with the parameters specified in the input");
            }
            $inverseReference  = $inverseReference->toArray();

            $new_match_null    = [];
            $new_inverse_order = AlignUtils::_getNewOrderValue( $inverseReference[ 'order' ], $inverseReference[ 'next' ] );

            $new_match_null[ 'order' ]          = $new_inverse_order;
            $new_match_null[ 'next' ]           = $inverseReference[ 'next' ];
            $new_match_null[ 'score' ]          = 100;
            $new_match_null[ 'segment_id' ]     = null;
            $new_match_null[ 'type' ]           = 'source';
            $new_match_null[ 'id_job' ]         = $id_job;
            $new_match_null[ 'content_raw' ]    = null;
            $new_match_null[ 'content_clean' ]  = null;
            $new_match_null[ 'raw_word_count' ] = null;

            $inverseReference['next'] = $new_inverse_order;
        }
        array_shift( $sourceOrders );
        array_shift( $targetOrders );
        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            $first_source_segment = ( count($sources) > 1 ) ? Segments_SegmentDao::mergeSegments( $sources ) : $first_source;
            $first_target_segment = ( count($targets) > 1 ) ? Segments_SegmentDao::mergeSegments( $targets ) : $first_target;
            if( !empty($sourceOrders) ){ Segments_SegmentMatchDao::nullifySegmentsInMatches( $sourceOrders, $id_job, 'source' ); }
            if( !empty($targetOrders) ){ Segments_SegmentMatchDao::nullifySegmentsInMatches( $targetOrders, $id_job, 'target' ); }

            if($first_target['order'] != $destination_order ){
                $segmentsMatchDao = new Segments_SegmentMatchDao;
                $segmentsMatchDao->createList( [ $new_match_destination, $new_match_null ] );
                Segments_SegmentMatchDao::nullifySegmentsInMatches( [ $first_target['order'] ], $id_job, 'target' );
                Segments_SegmentMatchDao::updateFields( ['segment_id' => $first_target_segment['id'], 'next' => $new_match_order], $destination_order, $id_job, 'target' );
                Segments_SegmentMatchDao::updateFields( ['next' => $new_inverse_order], $first_source_segment['order'], $id_job, 'source' );
            }

            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage() . " - Merge-align  ", -2 );
        }

        array_shift( $sources );
        array_shift( $targets );
        
        $destination_match['content_raw']    = null;
        $destination_match['content_clean']  = $first_target_segment['content_clean'];
        $destination_match['raw_word_count'] = $first_target_segment['raw_word_count'];

        $inverseReference['content_raw']    = null;
        $inverseReference['content_clean']  = $first_source_segment['content_clean'];
        $inverseReference['raw_word_count'] = $first_source_segment['raw_word_count'];
        
        try{

            $this->pushOperation( [
                'type'      => 'source',
                'action'    => 'update',
                'rif_order' => $first_source_segment['order'],
                'data'      => $first_source_segment
            ] );

            foreach ( $sources as $source ) {
                $source['content_raw'] = null;
                $source['content_clean'] = null;
                $source['raw_word_count'] = null;
                $this->pushOperation( [
                    'type'      => 'source',
                    'action'    => 'update',
                    'rif_order' => $source['order'],
                    'data' => $source
                ] );
            }

            $this->pushOperation( [
                'type'      => 'target',
                'action'    => 'update',
                'rif_order' => $first_target_segment['order'],
                'data'      => $first_target_segment
            ] );

            foreach ( $targets as $target ) {
                $target['content_raw'] = null;
                $target['content_clean'] = null;
                $target['raw_word_count'] = null;
                $this->pushOperation( [
                    'type'      => 'target',
                    'action'    => 'update',
                    'rif_order' => $target['order'],
                    'data' => $target
                ] );
            }

            if($first_target['order'] != $destination_order){
                $this->pushOperation( [
                    'type'      => 'target',
                    'action'    => 'update',
                    'rif_order' => $destination_order,
                    'data'      => $destination_match
                ] );

                $this->pushOperation( [
                    'type'      => 'target',
                    'action'    => 'update',
                    'rif_order' => $first_target['order'],
                    'data'      => $starting_match
                ] );

                $this->pushOperation( [
                    'type'      => 'target',
                    'action'    => 'create',
                    'rif_order' => $referenceMatch['next'],
                    'data'      => $new_match_destination
                ] );

                $this->pushOperation( [
                    'type'      => 'source',
                    'action'    => 'create',
                    'rif_order' => $first_source[ 'next' ],
                    'data'      => $new_match_null
                ] );

                $this->pushOperation( [
                    'type'      => 'source',
                    'action'    => 'update',
                    'rif_order' => $inverseReference['order'],
                    'data'      => $inverseReference
                ] );
            }


        } catch ( ValidationError $e ) {
            throw new ValidationError( $e->getMessage(), -2 );
        }
        return $this->getOperations();
    }


    public function hide() {
        $id_job = $this->job->id;

        $matches = $this->params[ 'matches' ];

        $conn = NewDatabase::obtain()->getConnection();

        try {
            $conn->beginTransaction();
            foreach ( $matches as $match ) {

                if ( $match[ 'to_hide' ] == "both" ) {

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

                } else {

                    $type_hide         = $match['to_hide'];
                    $type_inverse_hide = ( $type_hide == "source" ) ? "target" : "source";
                    $inverse_hide      = Segments_SegmentDao::getFromOrderJobIdAndType(
                        $match[$type_inverse_hide],
                        $id_job , $type_inverse_hide );
                    $inverse_hide = $inverse_hide->toArray();

                    //Gets the segment matches to change their 'next' pointer to the non-hidden match
                    $to_hide = Segments_SegmentDao::getFromOrderJobIdAndType( $match[ $type_hide ], $id_job, $type_hide );
                    $to_hide = $to_hide->toArray();

                    if ( $inverse_hide['id'] != null ) {

                        $prev_inverse_hide = Segments_SegmentDao::getPreviousFromOrderJobIdAndType($match[ $type_inverse_hide ], $id_job, $type_inverse_hide);
                        $prev_to_hide      = Segments_SegmentDao::getPreviousFromOrderJobIdAndType($match[ $type_hide ], $id_job, $type_hide);

                        $prev_inverse_hide = (isset($prev_inverse_hide)) ? $prev_inverse_hide->toArray() : null;
                        $prev_to_hide      = (isset($prev_to_hide)) ? $prev_to_hide->toArray() : null;

                        if($prev_to_hide != null){
                            $new_match_order = AlignUtils::_getNewOrderValue( $prev_to_hide['order'], $prev_to_hide['next'] );
                            $prev_to_hide['next'] = $new_match_order;
                        } else {
                            $new_match_order = AlignUtils::_getNewOrderValue( 0, $to_hide['order'] );
                        }

                        if($prev_inverse_hide != null){
                            $new_match_order_inverse = AlignUtils::_getNewOrderValue( $prev_inverse_hide['order'], $prev_inverse_hide['next'] );
                            $prev_inverse_hide['next'] = $new_match_order_inverse;
                        } else {
                            $new_match_order_inverse = AlignUtils::_getNewOrderValue( 0, $inverse_hide['order'] );
                        }

                        //Creates a new non-hidden match

                        $new_inverse_match               = $inverse_hide;
                        $new_inverse_match['segment_id'] = $inverse_hide['id'];
                        $new_inverse_match['order']      = $new_match_order_inverse;
                        $new_inverse_match['next']       = $inverse_hide['order'];
                        $new_inverse_match['score']      = 100;

                        $inverse_hide[ 'score' ]          = 100;
                        $inverse_hide[ 'segment_id' ]     = null;
                        $inverse_hide[ 'content_raw' ]    = null;
                        $inverse_hide[ 'content_clean' ]  = null;
                        $inverse_hide[ 'raw_word_count' ] = null;

                        $new_match_null = [];
                        $new_match_null[ 'order' ]          = $new_match_order;
                        $new_match_null[ 'next' ]           = $to_hide['order'];
                        $new_match_null[ 'score' ]          = 100;
                        $new_match_null[ 'segment_id' ]     = null;
                        $new_match_null[ 'type' ]           = $type_hide;
                        $new_match_null[ 'id_job' ]         = $id_job;
                        $new_match_null[ 'content_raw' ]    = null;
                        $new_match_null[ 'content_clean' ]  = null;
                        $new_match_null[ 'raw_word_count' ] = null;

                        $this->pushOperation( [
                            'type'      => $type_inverse_hide,
                            'action'    => 'create',
                            'rif_order' => $new_inverse_match['next'],
                            'data'      => $new_inverse_match
                        ] );

                        $this->pushOperation( [
                            'type'      => $type_hide,
                            'action'    => 'create',
                            'rif_order' => $new_match_null['next'],
                            'data'      => $new_match_null
                        ] );

                        if($prev_inverse_hide != null){
                            $this->pushOperation( [
                                'type'      => $type_inverse_hide,
                                'action'    => 'update',
                                'rif_order' => $prev_inverse_hide['order'],
                                'data'      => $prev_inverse_hide
                            ] );
                        }

                        if($prev_to_hide != null){
                            $this->pushOperation( [
                                'type'      => $type_hide,
                                'action'    => 'update',
                                'rif_order' => $prev_to_hide['order'],
                                'data'      => $prev_to_hide
                            ] );
                        }

                        $segmentsMatchDao = new Segments_SegmentMatchDao;
                        $segmentsMatchDao->createList( [ $new_inverse_match, $new_match_null ] );
                        Segments_SegmentMatchDao::nullifySegmentsInMatches( [$inverse_hide['order']], $id_job, $type_inverse_hide );
                        if($prev_to_hide != null){
                            Segments_SegmentMatchDao::updateFields(['next' => $prev_to_hide['next']], $prev_to_hide['order'], $id_job, $type_hide);
                        }
                        if($prev_inverse_hide != null){
                            Segments_SegmentMatchDao::updateFields(['next' => $prev_inverse_hide['next']], $prev_inverse_hide['order'], $id_job, $type_inverse_hide);
                        }

                    }


                    $inverse_hide[ 'hidden' ] = 1;
                    $to_hide['hidden'] = 1;
                    $this->pushOperation( [
                        'type'      => $type_inverse_hide,
                        'action'    => 'update',
                        'rif_order' => $match[$type_inverse_hide],
                        'data'      => $inverse_hide
                    ] );

                    $this->pushOperation( [
                        'type'      => $type_hide,
                        'action'    => 'update',
                        'rif_order' => $match[$type_hide],
                        'data'      => $to_hide
                    ] );

                    Segments_SegmentMatchDao::hideByOrderAndType( $match[ $type_hide ], $id_job, $type_hide );
                    Segments_SegmentMatchDao::hideByOrderAndType( $match[ $type_inverse_hide ], $id_job, $type_inverse_hide );

                }
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

    public function show(){
        $id_job = $this->job->id;

        $matches = $this->params[ 'matches' ];

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();

            foreach ($matches as $match) {

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