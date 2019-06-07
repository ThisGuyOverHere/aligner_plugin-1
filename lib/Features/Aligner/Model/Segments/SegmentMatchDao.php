<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 17:23
 */
namespace Features\Aligner\Model;

use DataAccess\ShapelessConcreteStruct;
use Features\Aligner\Utils\AlignUtils;

class Segments_SegmentMatchDao extends DataAccess_AbstractDao {
    const TABLE = "segments_match";


    public function missAlignments($id_job, $ttl = 0){

        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( " SELECT `type`, `order` FROM segments_match WHERE segment_id IS NULL AND id_job =  :id_job" );
        return @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), [ 'id_job' => $id_job ] );
    }

    public function deleteByJobId($id_job) {
        $sql = "DELETE FROM segments_match " .
                " WHERE id_job = :id_job " ;
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );
        return $stmt->execute( array('id_job' => $id_job ) ) ;
    }

    public static function getSegmentMatch($order, $id_job, $type, $ttl = 0){
        $thisDao = new self();
        $sql = "SELECT `id_job`, `order`, `type`, `segment_id`, `next`, `score` 
        FROM segments_match as sm
        WHERE sm.order = :order AND sm.id_job = :id_job AND sm.type = :type";
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );
        //There's a [0] at the end because it's supposed to return a single element instead of an array
        return @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), [ 'order' => $order, 'id_job' => $id_job, 'type' => $type ] )[0];
    }

    public static function getPreviousSegmentMatch($order, $id_job, $type, $ttl = 0){
        $thisDao = new self();
        $sql = "SELECT `id_job`, `order`, `type`, `segment_id`, `next`, `score`
        FROM segments_match as sm 
        WHERE sm.next = :order AND sm.id_job = :id_job AND sm.type = :type";
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );

        $segmentMatch = @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), [ 'order' => $order, 'id_job' => $id_job, 'type' => $type ] );
        if(!empty($segmentMatch)){
            return $segmentMatch[0];
        } else {
            return $segmentMatch;
        }
    }

    public static function getLastSegmentMatch($id_job, $type, $ttl = 0){
        $thisDao = new self();
        $sql = "SELECT * FROM segments_match as sm WHERE sm.next IS NULL AND sm.id_job = :id_job AND sm.type = :type";
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );
        //There's a [0] at the end because it's supposed to return a single element instead of an array
        return @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), [ 'id_job' => $id_job, 'type' => $type ] )[0];
    }

    public static function getMatchesFromOrderArray( Array $orders, $id_job, $type, $ttl = 0 ){
        $thisDao = new self();
        $qMarks = str_repeat('?,', count($orders) - 1) . '?';
        $sql = "SELECT * FROM segments_match as sm WHERE sm.order IN ($qMarks) AND sm.id_job = ? AND sm.type = ?";
        $params = array_merge($orders, array($id_job, $type) );
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );

        $segmentMatches = @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new ShapelessConcreteStruct(), $params );
        return $segmentMatches;
    }

    public static function hideByOrderAndType( $order, $id_job, $type){
        $thisDao = new self();
        $sql = "UPDATE segments_match SET hidden = 1 WHERE `order` = ? AND id_job = ? AND `type` = ?";
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );
        $params = [$order, $id_job, $type];

        $stmt->execute($params);
    }

    public static function showByOrderAndType( $order, $id_job, $type){
        $thisDao = new self();
        $sql = "UPDATE segments_match SET hidden = 0 WHERE `order` = ? AND id_job = ? AND `type` = ?";
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );
        $params = [$order, $id_job, $type];

        $stmt->execute($params);
    }

    public function createList( Array $obj_arr ) {

        $obj_arr = array_chunk( $obj_arr, 100 );

        $baseQuery = "INSERT INTO segments_match ( 
                            id_job, 
                            `type`,
                            `order`,
                            score,
                            segment_id,
                            `next`,
                            create_date
                            ) VALUES ";


        $tuple_marks = "( " . str_repeat( "?, ", 6 ) . "NOW() )";

        foreach ( $obj_arr as $i => $chunk ) {

            $query = $baseQuery . rtrim( str_repeat( $tuple_marks . ", ", count( $chunk ) ), ", " );

            $values = [];
            foreach( $chunk as $segStruct ){

                $values[] = $segStruct['id_job'];
                $values[] = $segStruct['type'];
                $values[] = $segStruct['order'];
                $values[] = $segStruct['score'];
                $values[] = $segStruct['segment_id'];
                $values[] = $segStruct['next'];
            }

            try {
                $conn = NewDatabase::obtain()->getConnection();
                $stm = $conn->prepare( $query );
                $stm->execute( $values );

            } catch ( \PDOException $e ) {
                throw new \PDOException( "Segment import - DB Error: " . $e->getMessage() . " - $chunk", -2 );
            }

        }


    }

    public static function updateFields($attributes, $order, $id_job, $type){
        $qMark = [];
        $qValues = [];

        $valid_fields= AlignUtils::_getObjectVariables(new Segments_SegmentMatchStruct());

        foreach($attributes as $attribute_k => $attribute_v){
            if( !in_array( $attribute_k, $valid_fields ) ){
                throw new \PDOException("You tried to update an invalid field, the update has been canceled");
            }
            $qMark[] = "sm.".$attribute_k." = ?";
            $qValues[] = $attribute_v;
        }
        $query = "UPDATE segments_match as sm
        SET ".implode(", ", $qMark)."
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $params = array_merge($qValues, array($order, $id_job, $type));

        $conn = NewDatabase::obtain()->getConnection();
        $stm = $conn->prepare( $query );
        $stm->execute( $params );
    }

    public static function nullifySegmentsInMatches(Array $orders, $id_job, $type ){
        $qMarks = str_repeat('?,', count($orders) - 1) . '?';

        $query = "UPDATE segments_match as sm
                    SET sm.segment_id = null
                    WHERE sm.order IN ($qMarks) AND sm.id_job = ? AND sm.type = ?";
        $params = array_merge($orders, array($id_job, $type));

        $conn = NewDatabase::obtain()->getConnection();
        $stm = $conn->prepare( $query );
        $stm->execute( $params );
    }

    public static function setSegmentInMatch($segment_id, $order, $id_job, $type ){

        $query = "UPDATE segments_match as sm
                    SET sm.segment_id = ?
                    WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $params = [$segment_id, $order, $id_job, $type];

        $conn = NewDatabase::obtain()->getConnection();
        $stm = $conn->prepare( $query );
        $stm->execute( $params );
    }

    public static function updateNextSegmentMatch($new_order, $order, $id_job, $type){
        $query = "UPDATE segments_match as sm
        SET sm.next = ?
        WHERE sm.order = ? AND sm.id_job = ? AND sm.type = ?";
        $params = array($new_order, $order, $id_job, $type);

        $conn = NewDatabase::obtain()->getConnection();
        $stm = $conn->prepare( $query );
        $stm->execute( $params );

    }

    public static function updateMatchBeforeDeletion($order, $id_job, $type){

        $query  = "UPDATE segments_match AS sm1, segments_match AS sm2
            SET sm1.next = sm2.next
            WHERE sm1.next = ?  AND sm2.order = ?
            AND sm1.type = ? AND sm2.type = ?
            AND sm1.id_job = ? AND sm2.id_job = ?";
        $params = [$order, $order, $type, $type, $id_job, $id_job ];

        $conn = NewDatabase::obtain()->getConnection();
        $stm = $conn->prepare( $query );
        $stm->execute( $params );

    }

    public static function deleteMatch($order, $id_job, $type){
        $query  = "DELETE FROM segments_match
            WHERE `order` = ?
            AND `type` = ?
            AND id_job = ?";
        $params = [ $order, $type, $id_job ];

        $conn = NewDatabase::obtain()->getConnection();
        $stm = $conn->prepare( $query );
        $stm->execute( $params );

    }
    
}