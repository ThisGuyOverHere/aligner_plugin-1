<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 17:23
 */
namespace Features\Aligner\Model;

use DataAccess\ShapelessConcreteStruct;

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

    public function createList( Array $obj_arr ) {

        $obj_arr = array_chunk( $obj_arr, 100 );

        $baseQuery = "INSERT INTO segments_match ( 
                            id_job, 
                            `type`,
                            `order`,
                            score,
                            segment_id,
                            `next`
                            ) VALUES ";


        $tuple_marks = "( " . rtrim( str_repeat( "?, ", 6 ), ", " ) . " )";

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

            } catch ( PDOException $e ) {
                throw new Exception( "Segment import - DB Error: " . $e->getMessage() . " - $chunk", -2 );
            }

        }


    }

}