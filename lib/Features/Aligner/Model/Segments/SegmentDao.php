<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 13:24
 */

namespace Features\Aligner\Model;

use DataAccess\ShapelessConcreteStruct;
use Features\Aligner;
use Symfony\Component\Config\Definition\Exception\Exception;

class Segments_SegmentDao extends DataAccess_AbstractDao {
    const TABLE = "segments";

    protected static $auto_increment_fields = [ 'id' ];
    protected static $primary_keys          = [ 'id' ];

    /**
     * @param $project
     * @param $field
     * @param $value
     *
     * @return Projects_ProjectStruct
     */
    public function updateField( $project, $field, $value ) {

        $sql = "UPDATE segments SET {$field} = :value WHERE id = :id ";

        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );

        $success = $stmt->execute( [
                'value' => $value,
                'id'    => $project->id
        ] );

        if ( $success ) {
            $project->$field = $value;
        }

        return $project;

    }


    /**
     * @param     $id
     * @param int $ttl
     *
     * @return Segments_SegmentStruct
     */
    public static function findById( $id, $ttl = 0 ) {

        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();
        $stmt    = $conn->prepare( " SELECT * FROM segments WHERE id = :id " );

        return @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ 'id' => $id ] )[ 0 ];

    }

    public static function getByJobIdAndType( $id_job, $type, $ttl = 0 ) {

        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();
        $stmt    = $conn->prepare( "SELECT * FROM segments WHERE id_job = ? AND type = ? ORDER BY id ASC" );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $id_job, $type ] );

    }

    public static function getPreviousFromOrderJobIdAndType( $order, $id_job, $type, $ttl = 0 ) {

        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();
        $stmt    = $conn->prepare( "SELECT * 
        FROM segments RIGHT JOIN segments_match ON segment_id = segments.id
        WHERE segments_match.next = ? AND segments_match.id_job = ? AND segments_match.type = ? ORDER BY id ASC" );

        //There's a [0] at the end because it's supposed to return a single element instead of an array
        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $order, $id_job, $type ] )[ 0 ];

    }

    public static function getFromOrderJobIdAndType( $order, $id_job, $type, $ttl = 0 ) {

        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();
        $stmt    = $conn->prepare( "SELECT * 
        FROM segments RIGHT JOIN segments_match ON segment_id = segments.id
        WHERE segments_match.order = ? AND segments_match.id_job = ? AND segments_match.type = ? ORDER BY id ASC" );

        //There's a [0] at the end because it's supposed to return a single element instead of an array
        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $order, $id_job, $type ] )[ 0 ];

    }

    public static function getFromOrderArrayJobIdAndType( Array $orders, $id_job, $type, $ttl = 0 ) {

        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();
        $qMarks = str_repeat('?,', count($orders) - 1) . '?';
        $stmt    = $conn->prepare( "SELECT * 
        FROM segments RIGHT JOIN segments_match ON segment_id = segments.id
        WHERE segments_match.order IN ($qMarks)
        AND segments_match.id_job = ?
        AND segments_match.type = ? ORDER BY id ASC" );
        $params = array_merge($orders, array($id_job, $type) );

        //There's a [0] at the end because it's supposed to return a single element instead of an array
        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), $params )[ 0 ];

    }

    public static function getTranslationsForTMXExport( $id_job ) {
        $conn = NewDatabase::obtain()->getConnection();



        $source_query = "SELECT s.content_clean as source, s.id as source_segment_id FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? ORDER by sm.order";

        $stmt = $conn->prepare( $source_query );

        $stmt->execute( [ $id_job, 'source' ] );

        $source_data = $stmt->fetchAll();

        $target_query = "SELECT s.content_clean as target, s.id as target_segment_id FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? ORDER by sm.order";

        $stmt = $conn->prepare( $target_query );

        $stmt->execute( [ $id_job, 'target' ] );

        $target_data = $stmt->fetchAll();

        $data = [];

        if(count($target_data) != count($source_data)){
            throw new Exception("Numbers of segments on target and source are different");
        }

        foreach ( $target_data as $key => $target ) {
            $data[] = array_merge( $target, $source_data[ $key ] );
        }

        /*$conn->query("set @RN1=0");
        $conn->query("set @RN2=0");*/

        /*$stmt = $conn->prepare( "
SELECT source, target, source_segment_id, target_segment_id FROM (
SELECT s.content_raw as source, @RN1 := @RN1 + 1 as RN1, s.id as source_segment_id FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? ORDER by sm.order
        ) s LEFT JOIN (
        SELECT s.content_raw as target, @RN2 := @RN2 + 1 as RN2, s.id as target_segment_id FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ?
        ORDER by sm.order
        ) t 
        ON s.RN1 = t.RN2" );

        $stmt->execute( [ $id_job, 'source', $id_job, 'target' ] );

        $data = $stmt->fetchAll();*/

        return $data;
    }


    public static function getTMForTMXExport( $id_job ) {

        $conn = NewDatabase::obtain()->getConnection();

        $stmt = $conn->prepare( "SELECT s.content_clean as source FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? ORDER by sm.order" );

        $stmt->execute( [ $id_job, 'source' ] );

        $source = $stmt->fetchAll();

        $stmt = $conn->prepare( "SELECT s.content_clean as target FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? ORDER by sm.order" );

        $stmt->execute( [ $id_job, 'target' ] );

        $target = $stmt->fetchAll();

        return [ 'source' => $source, 'target' => $target ];
    }

    public static function getFromOrderInterval( $order_start, $order_end, $id_job, $type, $ttl = 0 ) {

        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();
        $stmt    = $conn->prepare( "SELECT * 
        FROM segments RIGHT JOIN segments_match ON segment_id = segments.id
        WHERE segments_match.order >= ? AND segments_match.order < ?
        AND segments_match.id_job = ? AND segments_match.type = ? 
        ORDER BY segments_match.order ASC" );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $order_start, $order_end, $id_job, $type ] );

    }

    public static function getDataForAlignment( $id_job, $type, $ttl = 0 ) {
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( "SELECT id, content_clean, raw_word_count FROM segments WHERE id_job = ? AND type = ? ORDER BY id ASC" );
        $stmt->execute( [ $id_job, $type ] );

        return $stmt->fetchAll( \PDO::FETCH_ASSOC );

    }

    public static function getTargetOrdered( $id_job, $where, $order, $amount ) {
        return self::getTypeOrderedByJobIdWithPagination( $id_job, "target", 0, $where, $order, $amount );
    }

    public static function getSourceOrdered( $id_job, $where, $order, $amount ) {
        return self::getTypeOrderedByJobIdWithPagination( $id_job, "source", 0, $where, $order, $amount );
    }

    public static function getTypeOrderedByJobIdWithPagination( $id_job, $type, $ttl = 0, $where, $order, $segmentAmount ) {
        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();

        $queryBefore = "SELECT `order` FROM (SELECT sm.order FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? AND sm.order < ?
        ORDER by sm.order DESC
        LIMIT %u) as SM";

        $queryAfter = "SELECT `order` FROM (SELECT sm.order FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? AND sm.order > ?
        ORDER by sm.order
        LIMIT %u) as SM";

        $queryCenter = "SELECT `order` FROM (SELECT sm.order FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? AND sm.order < ?
        ORDER by sm.order DESC
        LIMIT %u) as SM1
        UNION
        SELECT `order` FROM (SELECT sm.order FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? AND sm.order >= ?
        ORDER by sm.order
        LIMIT %u) as SM2";

        switch ( $where ) {
            case 'before':
                $subquery    = sprintf( $queryBefore, $segmentAmount );
                $queryParams = [ $id_job, $type, $order ];
                break;
            case 'after':
                $subquery    = sprintf( $queryAfter, $segmentAmount );
                $queryParams = [ $id_job, $type, $order ];
                break;
            case 'center':
                $subquery    = sprintf( $queryCenter, floor( $segmentAmount / 2 ), ceil( $segmentAmount / 2 ) );
                $queryParams = [ $id_job, $type, $order, $id_job, $type, $order ];
                break;
        }

        $query = "SELECT * FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? and `order` in ($subquery)
        ORDER by sm.order";

        $queryParams = array_merge( [ $id_job, $type ], $queryParams );

        $stmt = $conn->prepare( $query );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), $queryParams );
    }


    public static function getTypeOrderedByJobId( $id_job, $type, $ttl = 0 ) {
        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();


        $query = "SELECT s.id, sm.`type`, sm.order, sm.next, s.content_clean FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ?
        ORDER by sm.order";

        $queryParams = [ $id_job, $type ];

        $stmt = $conn->prepare( $query );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), $queryParams );
    }

    public static function getOrderedByJobId( $id_job, $ttl = 0 ) {
        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();


        $query = "SELECT s.id, sm.`type`, sm.order, sm.next, s.content_clean, sm.hidden FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ?
        ORDER by sm.order";

        $queryParams = [ $id_job ];

        $stmt = $conn->prepare( $query );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), $queryParams );
    }


    public function countByJobId( $id_job, $type, $ttl = 0 ) {
        $thisDao = new self();
        $conn    = NewDatabase::obtain()->getConnection();
        $stmt    = $conn->prepare( "SELECT count(id) as amount FROM segments WHERE id_job = ? AND type = ? ORDER BY id ASC" );
        $result  = $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), [ $id_job, $type ] );

        return $result;
    }

    public function createList( Array $obj_arr ) {

        $obj_arr = array_chunk( $obj_arr, 100 );

        $baseQuery = "INSERT INTO segments ( 
                            id, 
                            id_job, 
                            type,
                            content_clean, 
                            raw_word_count,
                            create_date
                            ) VALUES ";


        $tuple_marks = "( " . str_repeat( "?, ", 5 ) . " NOW() )";

        foreach ( $obj_arr as $i => $chunk ) {

            $query = $baseQuery . rtrim( str_repeat( $tuple_marks . ", ", count( $chunk ) ), ", " );

            $values = [];
            foreach ( $chunk as $segStruct ) {

                $values[] = $segStruct[ 'id' ];
                $values[] = $segStruct[ 'id_job' ];
                $values[] = $segStruct[ 'type' ];
                $values[] = $segStruct[ 'content_clean' ];
                $values[] = $segStruct[ 'raw_word_count' ];
            }

            try {
                $conn = NewDatabase::obtain()->getConnection();
                $stm  = $conn->prepare( $query );
                $stm->execute( $values );

            } catch ( PDOException $e ) {
                throw new Exception( "Segment import - DB Error: " . $e->getMessage() . " - $chunk", -2 );
            }

        }


    }

    public static function mergeSegments( Array $segments, $glue = ' ' ) {

        $clean_array = [];
        $merge_count = 0;

        $segments_id = [];
        foreach ( $segments as $key => $segment ) {
            $clean_array[] = $segment[ 'content_clean' ];
            $merge_count   += $segment[ 'raw_word_count' ];

            $segments_id[] = $segment[ 'id' ];
        }

        $clean_merge = implode( $glue, $clean_array );

        self::updateSegmentContent( array_shift( $segments_id ), [ $clean_merge, $merge_count ] );

        self::deleteSegmentsByIds( $segments_id );

        $first_segment = array_shift( $segments );

        $first_segment[ 'content_raw' ]    = null;
        $first_segment[ 'content_clean' ]  = $clean_merge;
        $first_segment[ 'content_hash' ]   = null;
        $first_segment[ 'raw_word_count' ] = $merge_count;
        $first_segment[ 'order' ]          = (int)$first_segment[ 'order' ];
        $first_segment[ 'next' ]           = (int)$first_segment[ 'next' ];

        return $first_segment;

    }

    public static function deleteSegmentsByIds( $segments_id ) {
        $qMarks = str_repeat( '?,', count( $segments_id ) - 1 ) . '?';
        $query  = "DELETE FROM segments WHERE id IN ($qMarks)";

        $conn = NewDatabase::obtain()->getConnection();
        $stm  = $conn->prepare( $query );
        $stm->execute( $segments_id );
    }

    public static function updateSegmentContent( $id, Array $contents ) {
        $query        = "UPDATE segments
                    SET content_clean = ?,
                    raw_word_count = ?
                    WHERE id = ?;";
        $query_params = array_merge( $contents, [ $id ] );

        $conn = NewDatabase::obtain()->getConnection();
        $stm  = $conn->prepare( $query );
        $stm->execute( $query_params );

    }


}
