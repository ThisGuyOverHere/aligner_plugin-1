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

    public static function getByJobIdAndType( $id_job, $type, $ttl = 0 ) {

        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( "SELECT * FROM segments WHERE id_job = ? AND type = ? ORDER BY id ASC" );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $id_job , $type] );

    }


    public static function getDataForAlignment( $id_job, $type, $ttl = 0 ) {
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( "SELECT id, content_clean FROM segments WHERE id_job = ? AND type = ? ORDER BY id ASC" );
        $stmt->execute( [$id_job, $type] );

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);

    }

    public static function getTargetOrdered($id_job){
        return self::getTypeOrderedByJobId($id_job, "target");
    }

    public static function getSourceOrdered($id_job){
        return self::getTypeOrderedByJobId($id_job, "source");
    }

    public static function getTypeOrderedByJobId($id_job, $type, $ttl = 0){
        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $query = "SELECT * FROM segments as s
        JOIN segments_match as sm ON s.id = sm.segment_id AND s.type = sm.type
        WHERE s.id_job = ? AND s.type = ?
        ORDER by sm.order";
        $stmt = $conn->prepare( $query );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $id_job, $type ] );
    }

    public function createList( Array $obj_arr ) {

        $obj_arr = array_chunk( $obj_arr, 100 );

        $baseQuery = "INSERT INTO segments ( 
                            id, 
                            id_job, 
                            type,
                            content_clean, 
                            content_raw,
                            content_hash, 
                            raw_word_count, 
                            language_code
                            ) VALUES ";


        $tuple_marks = "( " . rtrim( str_repeat( "?, ", 8 ), ", " ) . " )";

        foreach ( $obj_arr as $i => $chunk ) {

            $query = $baseQuery . rtrim( str_repeat( $tuple_marks . ", ", count( $chunk ) ), ", " );

            $values = [];
            foreach( $chunk as $segStruct ){

                $values[] = $segStruct['id'];
                $values[] = $segStruct['id_job'];
                $values[] = $segStruct['type'];
                $values[] = $segStruct['content_clean'];
                $values[] = $segStruct['content_raw'];
                $values[] = $segStruct['content_hash'];
                $values[] = $segStruct['raw_word_count'];
                $values[] = $segStruct['language_code'];

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
