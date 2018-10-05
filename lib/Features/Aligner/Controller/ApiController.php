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

    protected $operations;

    public function merge() {

        $segments = [];
        $orders   = $this->params[ 'order' ];
        $type     = $this->params[ 'type' ];
        $id_job   = $this->params[ 'id_job' ];

        sort( $orders );
        foreach ( $orders as $order ) {
            $segments[] = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type )->toArray();
        }

        $deleted_orders = [];

        foreach ( $segments as $key => $segment ) {
            $deleted_orders[]            = $segment[ 'order' ];
        }

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            $first_segment = Segments_SegmentDao::mergeSegments( $segments );
            Segments_SegmentMatchDao::nullifySegmentsInMatches( $deleted_orders, $id_job, $type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - Merging $orders", -2 );
        }

        $this->pushOperation([
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => $first_segment[ 'order' ],
                'data'      => $first_segment
        ]);

        foreach ( $deleted_orders as $order ) {
            $this->pushOperation([
                    'type'      => $type,
                    'action'    => 'delete',
                    'rif_order' => (int)$order
            ]);
        }

        return $this->getOperations();

    }


    public function split() {

        $order         = $this->params[ 'order' ];
        $id_job        = $this->params[ 'id_job' ];
        $type          = $this->params[ 'type' ];
        $inverse_order = $this->params[ 'inverse_order' ];
        $inverse_type  = ( $type == 'target' ) ? 'source' : 'target';
        $positions     = $this->params[ 'positions' ];

        if ( empty( $positions ) ) {
            return true;
        }

        //Gets from 0 since they are returned as an array
        $split_segment    = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type )->toArray();
        $inverse_segment  = Segments_SegmentDao::getFromOrderJobIdAndType( $inverse_order, $id_job, $inverse_type )->toArray();

        $avg_order   = AlignUtils::_getNewOrderValue( $split_segment[ 'order' ], $split_segment[ 'next' ] );
        $inverse_avg = AlignUtils::_getNewOrderValue( $inverse_segment[ 'order' ], $inverse_segment[ 'next' ] );

        $split_segment[ 'order' ]   = (int)$split_segment[ 'order' ];
        $inverse_segment[ 'order' ] = (int)$inverse_segment[ 'order' ];
        $split_segment[ 'next' ]    = $avg_order;
        $inverse_segment[ 'next' ]  = $inverse_avg;

        $raw_contents = [];
        $full_raw     = AlignUtils::_mark_xliff_tags( $split_segment[ 'content_raw' ] );
        $positions[]  = strlen( $full_raw );
        foreach ( $positions as $key => $position ) {
            $start          = ( $key == 0 ) ? 0 : $positions[ $key - 1 ] + 1;
            $raw_substring  = substr( $full_raw, $start, ( $position + 1 ) - $start );
            $raw_contents[] = AlignUtils::_restore_xliff_tags( $raw_substring );
        }

        $first_raw   = array_shift( $raw_contents );
        $first_hash  = md5( $first_raw );
        $first_clean = AlignUtils::_cleanSegment( $first_raw, $split_segment[ 'language_code' ] );
        $first_count = AlignUtils::_countWordsInSegment( $first_raw, $split_segment[ 'language_code' ] );

        $new_segment = $split_segment;
        $new_match   = $split_segment;
        $null_match  = $inverse_segment;

        $split_segment[ 'content_raw' ]    = $first_raw;
        $split_segment[ 'content_clean' ]  = $first_clean;
        $split_segment[ 'content_hash' ]   = $first_hash;
        $split_segment[ 'raw_word_count' ] = $first_count;

        $update_order         = $avg_order;
        $inverse_update_order = $inverse_avg;

        $new_ids          = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count( $raw_contents ) );
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
            $new_segment[ 'content_raw' ]    = array_shift( $raw_contents );
            $new_segment[ 'content_clean' ]  = AlignUtils::_cleanSegment( $new_segment[ 'content_raw' ], $new_segment[ 'language_code' ] );
            $new_segment[ 'content_hash' ]   = md5( $new_segment[ 'content_raw' ] );
            $new_segment[ 'raw_word_count' ] = AlignUtils::_countWordsInSegment( $new_segment[ 'content_raw' ], $new_segment[ 'language_code' ] );

            //create new matches
            $new_match[ 'segment_id' ] = $id;
            $new_match[ 'order' ]      = $avg_order;
            $new_segment[ 'order' ]    = (int)$new_match[ 'order' ];

            //If we split the last segment we add new next values for the new segments
            $avg_order = ( $split_segment[ 'next' ] != null ) ? AlignUtils::_getNewOrderValue( $new_match[ 'order' ], $split_segment[ 'next' ] ) : $avg_order + Constants::DISTANCE_INT_BETWEEN_MATCHES;

            $new_match[ 'next' ]   = ( $key != count( $new_ids ) - 1 ) ? $avg_order : (int)$split_segment[ 'next' ];
            $new_segment[ 'next' ] = (int)$new_match[ 'next' ];
            $new_matches[]         = $new_match;

            //create new null matches
            $null_match[ 'segment_id' ] = null;
            $null_match[ 'order' ]      = $inverse_avg;
            $null_segment[ 'order' ]    = $inverse_avg;

            //If we split the last segment we add new next values for the new segments
            $inverse_avg = ( $inverse_segment[ 'next' ] != null ) ? AlignUtils::_getNewOrderValue( $null_match[ 'order' ], $inverse_segment[ 'next' ] ) : $inverse_avg + Constants::DISTANCE_INT_BETWEEN_MATCHES;

            $null_match[ 'next' ]   = ( $key != count( $new_ids ) - 1 ) ? $inverse_avg : (int)$inverse_segment[ 'next' ];
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

            Segments_SegmentDao::updateSegmentContent( $split_segment [ 'id' ], [ $first_raw, $first_clean, $first_hash, $first_count ] );
            Segments_SegmentMatchDao::updateNextSegmentMatch( $update_order, $order, $id_job, $type );
            Segments_SegmentMatchDao::updateNextSegmentMatch( $inverse_update_order, $inverse_order, $id_job, $inverse_type );

            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        //Format returned segments

        $source = [];
        $target = [];

        //Check which segments to retrieve for source/target
        $source_start = ( $type == 'source' ) ? $split_segment[ 'order' ] : $inverse_segment[ 'order' ];
        $source_end   = ( $type == 'source' ) ? $split_segment[ 'next' ] : $inverse_segment[ 'next' ];
        $target_start = ( $type == 'target' ) ? $split_segment[ 'order' ] : $inverse_segment[ 'order' ];
        $target_end   = ( $type == 'target' ) ? $split_segment[ 'next' ] : $inverse_segment[ 'next' ];

        $segments       = array_merge( [ $split_segment ], $new_segments );
        $sourceSegments = ( $type == 'source' ) ? $segments : array_merge( [ $inverse_segment ], $null_segments );
        $targetSegments = ( $type == 'target' ) ? $segments : array_merge( [ $inverse_segment ], $null_segments );

        foreach ( $sourceSegments as $key => $segment ) {
            $sourceSegments[ $key ][ 'content_raw' ] = AlignUtils::_mark_xliff_tags( $segment[ 'content_raw' ] );
        }

        foreach ( $targetSegments as $key => $segment ) {
            $targetSegments[ $key ][ 'content_raw' ] = AlignUtils::_mark_xliff_tags( $segment[ 'content_raw' ] );
        }

        $this->pushOperation([
                'type'      => 'source',
                'action'    => 'update',
                'rif_order' => (int)$source_start,
                'data'      => array_shift( $sourceSegments )
        ]);

        foreach ( $sourceSegments as $sourceSegment ) {

            $this->pushOperation([
                    'type'      => 'source',
                    'action'    => 'create',
                    'rif_order' => (int)$source_end,
                    'data'      => $sourceSegment
            ]);

        }

        $this->pushOperation([
                'type'      => 'target',
                'action'    => 'update',
                'rif_order' => (int)$target_start,
                'data'      => array_shift( $targetSegments )
        ]);

        foreach ( $targetSegments as $targetSegment ) {

            $this->pushOperation([
                    'type'      => 'target',
                    'action'    => 'create',
                    'rif_order' => (int)$target_end,
                    'data'      => $targetSegment
            ]);

        }

        return $this->getOperations();
    }

    public function move() {

        $order               = $this->params[ 'order' ];
        $id_job              = $this->params[ 'id_job' ];
        $type                = $this->params[ 'type' ];
        $inverse_type        = ( $type == 'target' ) ? 'source' : 'target';
        $destination         = $this->params[ 'destination' ];
        $inverse_destination = $this->params[ 'inverse_destination' ];

        $movingSegment            = Segments_SegmentDao::getFromOrderJobIdAndType( $order, $id_job, $type )->toArray();
        $movingSegment[ 'order' ] = (int)$movingSegment[ 'order' ];
        $movingSegment[ 'next' ]  = (int)$movingSegment[ 'next' ];

        //$destinationSegment = Segments_SegmentDao::getFromOrderJobIdAndType($destination, $id_job, $type);
        //$oppositeSegment = Segments_SegmentDao::getFromOrderJobIdAndType($opposite_row, $id_job, $type);

        //Create new match between destination and destination->prev with starting segment
        $referenceMatch = Segments_SegmentMatchDao::getPreviousSegmentMatch( $destination, $id_job, $type );
        if ( !empty( $referenceMatch ) ) {
            $referenceMatch = $referenceMatch->toArray();
        }
        $reference_order               = ( !empty( $referenceMatch ) ) ? $referenceMatch[ 'order' ] : 0;
        $new_order                     = AlignUtils::_getNewOrderValue( $reference_order, $destination );
        $new_match                     = $referenceMatch;
        $new_match[ 'order' ]          = (int)$new_order;
        $new_match[ 'next' ]           = (int)$destination;
        $new_match[ 'score' ]          = 100;
        $new_match[ 'segment_id' ]     = $movingSegment[ 'id' ];
        $new_match[ 'type' ]           = $type;
        $new_match[ 'id_job' ]         = $id_job;
        $new_match[ 'content_raw' ]    = null;
        $new_match[ 'content_clean' ]  = null;
        $new_match[ 'raw_word_count' ] = null;

        $this->pushOperation([
                'type'      => $type,
                'action'    => 'create',
                'rif_order' => (int)$destination,
                'data'      => $new_match
        ]);

        //Create a new empty match on the opposite side of the row
        $inverseReference            = Segments_SegmentMatchDao::getSegmentMatch( $inverse_destination, $id_job, $inverse_type )->toArray();
        $new_inverse_order           = AlignUtils::_getNewOrderValue( $inverseReference[ 'order' ], $inverseReference[ 'next' ] );
        $new_gap                     = $inverseReference;
        $new_gap[ 'order' ]          = (int)$new_inverse_order;
        $new_gap[ 'next' ]           = (int)$inverseReference[ 'next' ];
        $new_gap[ 'score' ]          = 100;
        $new_gap[ 'segment_id' ]     = null;
        $new_gap[ 'type' ]           = $inverse_type;
        $new_gap[ 'id_job' ]         = $id_job;
        $new_gap[ 'content_raw' ]    = null;
        $new_gap[ 'content_clean' ]  = null;
        $new_gap[ 'raw_word_count' ] = null;

        $this->pushOperation([
                'type'      => $type,
                'action'    => 'create',
                'rif_order' => (int)$inverseReference[ 'next' ],
                'data'      => $new_gap
        ]);


        $movingSegment[ 'id' ] = null;

        $this->pushOperation([
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => (int)$order,
                'data'      => $movingSegment
        ]);

        if ( $reference_order != 0 ) {

            $referenceSegment            = Segments_SegmentDao::getFromOrderJobIdAndType( $referenceMatch[ 'order' ], $id_job, $type )->toArray();
            $referenceSegment[ 'order' ] = (int)$referenceSegment[ 'order' ];
            $referenceSegment[ 'next' ]  = $new_order;

            $this->pushOperation([
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => (int)$referenceMatch[ 'order' ],
                    'data'      => $referenceSegment
            ]);
        }

        $inverseSegment            = Segments_SegmentDao::getFromOrderJobIdAndType( $inverse_destination, $id_job, $inverse_type )->toArray();
        $inverseSegment[ 'order' ] = (int)$inverseSegment[ 'order' ];
        $inverseSegment[ 'next' ]  = (int)$inverseSegment[ 'next' ];

        $this->pushOperation([
                'type'      => $type,
                'action'    => 'update',
                'rif_order' => (int)$inverseSegment[ 'order' ],
                'data'      => $inverseSegment
        ]);

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            $segmentsMatchDao = new Segments_SegmentMatchDao;
            $segmentsMatchDao->createList( [ $new_match, $new_gap ] );
            //Set original match to empty and edit old next positions
            Segments_SegmentMatchDao::nullifySegmentsInMatches( [ $order ], $id_job, $type );
            if ( $reference_order != 0 ) {
                Segments_SegmentMatchDao::updateNextSegmentMatch( $new_order, $referenceMatch[ 'order' ], $id_job, $type );
            }
            Segments_SegmentMatchDao::updateNextSegmentMatch( $new_inverse_order, $inverseReference[ 'order' ], $id_job, $inverse_type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment Move - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }


    public function addGap() {
        $order      = $this->params[ 'order' ];
        $id_job     = $this->params[ 'id_job' ];
        $type       = $this->params[ 'type' ];
        $other_type = ( $type == 'target' ) ? 'source' : 'target';

        $gap_match     = [];
        $balance_match = [];

        $previous_match = Segments_SegmentMatchDao::getPreviousSegmentMatch( $order, $id_job, $type );
        $previous_order = ( empty( $previous_match ) ) ? 0 : $previous_match[ 'order' ];

        $gap_match[ 'order' ]          = AlignUtils::_getNewOrderValue( $previous_order, $order );
        $gap_match[ 'next' ]           = $order;
        $gap_match[ 'segment_id' ]     = null;
        $gap_match[ 'score' ]          = 100;
        $gap_match[ 'id_job' ]         = $id_job;
        $gap_match[ 'type' ]           = $type;
        $gap_match[ 'content_raw' ]    = null;
        $gap_match[ 'content_clean' ]  = null;
        $gap_match[ 'raw_word_count' ] = null;

        $this->pushOperation([
                'type'      => $type,
                'action'    => 'create',
                'rif_order' => (int)$order,
                'data'      => $gap_match
        ]);

        $last_match                        = Segments_SegmentMatchDao::getLastSegmentMatch( $id_job, $other_type );
        $last_match[ 'order' ]             = (int)$last_match[ 'order' ];
        $balance_match[ 'order' ]          = $last_match[ 'order' ] + Constants::DISTANCE_INT_BETWEEN_MATCHES;
        $balance_match[ 'next' ]           = null;
        $balance_match[ 'segment_id' ]     = null;
        $balance_match[ 'score' ]          = 100;
        $balance_match[ 'id_job' ]         = $id_job;
        $balance_match[ 'type' ]           = $other_type;
        $balance_match[ 'content_raw' ]    = null;
        $balance_match[ 'content_clean' ]  = null;
        $balance_match[ 'raw_word_count' ] = null;

        $this->pushOperation([
                'type'   => $other_type,
                'action' => 'push',
                'data'   => $balance_match
        ]);

        if ( !empty( $previous_match ) ) {
            $previous_match[ 'order' ] = (int)$previous_match[ 'order' ];

            $previous_match[ 'next' ] = (int)$gap_match[ 'order' ];

            $previous_segment            = Segments_SegmentDao::getFromOrderJobIdAndType( $previous_order, $id_job, $type )->toArray();
            $previous_segment[ 'order' ] = (int)$previous_segment[ 'order' ];
            $previous_segment[ 'next' ]  = (int)$gap_match[ 'order' ];

            $this->pushOperation([
                    'type'      => $type,
                    'action'    => 'update',
                    'rif_order' => (int)$previous_match[ 'order' ],
                    'data'      => $previous_segment
            ]);

        }

        $last_match[ 'next' ] = (int)$balance_match[ 'order' ];

        $last_segment = Segments_SegmentDao::getFromOrderJobIdAndType( $last_match[ 'order' ], $id_job, $type );
        if ( !empty( $last_segment ) ) {
            $last_segment = $last_segment->toArray();
        }
        $last_segment[ 'type' ]           = $other_type;
        $last_segment[ 'content_raw' ]    = ( !empty( $last_segment[ 'content_raw' ] ) ) ? $last_segment[ 'content_raw' ] : null;
        $last_segment[ 'content_clean' ]  = ( !empty( $last_segment[ 'content_clean' ] ) ) ? $last_segment[ 'content_clean' ] : null;
        $last_segment[ 'raw_word_count' ] = ( !empty( $last_segment[ 'raw_word_count' ] ) ) ? $last_segment[ 'raw_word_count' ] : null;
        $last_segment[ 'order' ]          = (int)$last_segment[ 'order' ];
        $last_segment[ 'next' ]           = (int)$last_match[ 'next' ];

        $this->pushOperation([
                'type'      => $other_type,
                'action'    => 'update',
                'rif_order' => (int)$last_match[ 'order' ],
                'data'      => $last_segment
        ]);


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
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage() . " - Order no. $order ", -2 );
        }

        return $this->getOperations();
    }

    public function delete() {

        $matches = $this->params[ 'matches' ];
        $id_job = $this->params[ 'id_job' ];

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
            throw new \Exception( "There is a different amount of source matches and target matches, Deletion cancelled", -2 );
        }

        $sourceMatches = Segments_SegmentMatchDao::getMatchesFromOrderArray( $sources, $id_job, 'source' );
        $targetMatches = Segments_SegmentMatchDao::getMatchesFromOrderArray( $targets, $id_job, 'target' );

        foreach ( $sourceMatches as $sourceMatch ) {
            if ( $sourceMatch[ 'segment_id' ] != null ) {
                throw new \Exception( "Segment Matches contain reference to existing segments, Deletion cancelled", -2 );
            }
        }

        foreach ( $targetMatches as $targetMatch ) {
            if ( $targetMatch[ 'segment_id' ] != null ) {
                throw new \Exception( "Segment Matches contain reference to existing segments, Deletion cancelled", -2 );
            }
        }

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            if ( !empty( $sources ) ) {
                Segments_SegmentMatchDao::updateMatchesBeforeDeletion( $sources, $id_job, 'source' );
                Segments_SegmentMatchDao::deleteMatches( $sources, $id_job, 'source' );
            }
            if ( !empty( $targets ) ) {
                Segments_SegmentMatchDao::updateMatchesBeforeDeletion( $targets, $id_job, 'target' );
                Segments_SegmentMatchDao::deleteMatches( $targets, $id_job, 'target' );
            }
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

    }

    public function reverse() {

        $id_job = $this->params[ 'id_job' ];
        $type   = $this->params[ 'type' ];
        $order1 = $this->params[ 'order1' ];
        $order2 = $this->params[ 'order2' ];

        $segment_1 = Segments_SegmentDao::getFromOrderJobIdAndType( $order1, $id_job, $type );
        $segment_2 = Segments_SegmentDao::getFromOrderJobIdAndType( $order2, $id_job, $type );

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $segment_2->id ], $order1, $id_job, $type );
            Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $segment_1->id ], $order2, $id_job, $type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \Exception( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        return true;

    }

    private function pushOperation($operation){
        $this->operations[] = $operation;
    }

    private function getOperations(){
        return $this->response->json($this->operations);
    }

}