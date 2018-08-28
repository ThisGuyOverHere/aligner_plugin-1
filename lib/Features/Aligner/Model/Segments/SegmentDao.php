<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 13:24
 */

namespace Features\Aligner\Model;

class Segments_SegmentDao extends DataAccess_AbstractDao {
    const TABLE = "segments";

    protected static $auto_increment_fields = array('id');
    protected static $primary_keys = array('id');

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

        $success = $stmt->execute( array(
                'value' => $value,
                'id' => $project->id
        ));

        if( $success ){
            $project->$field = $value;
        }

        return $project;

    }


    /**
     * @param     $id
     * @param int $ttl
     *
     * @return Projects_ProjectStruct
     */
    public static function findById( $id, $ttl = 0 ) {

        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( " SELECT * FROM segments WHERE id = :id " );
        return @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Projects_ProjectStruct(), [ 'id' => $id ] )[ 0 ];

    }

    public static function getByJobId( $id_job, $ttl = 0 ) {

        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( "SELECT * FROM segments WHERE id_job = ? ORDER BY id ASC" );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $id_job ] );

    }

    public function createList( Array $obj_arr ) {

        $obj_arr = array_chunk( $obj_arr, 100 );

        $baseQuery = "INSERT INTO segments ( 
                            id, 
                            id_job, 
                            id_file,
                            type,
                            content, 
                            content_hash, 
                            raw_word_count, 
                            language_code
                            ) VALUES ";


        $tuple_marks = "( " . rtrim( str_repeat( "?, ", 8 ), ", " ) . " )";

        foreach ( $obj_arr as $i => $chunk ) {

            $query = $baseQuery . rtrim( str_repeat( $tuple_marks . ", ", count( $chunk ) ), ", " );

            $values = [];
            foreach( $chunk as $segStruct ){

                $values[] = $segStruct->id;
                $values[] = $segStruct->id_job;
                $values[] = $segStruct->id_file;
                $values[] = $segStruct->type;
                $values[] = $segStruct->content;
                $values[] = $segStruct->content_hash;
                $values[] = $segStruct->raw_word_count;
                $values[] = $segStruct->language_code;

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
