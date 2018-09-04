<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 13:24
 */

namespace Features\Aligner\Model;

use Features\Aligner;

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
        return self::getLimitedTypeOrderedByJobId($id_job, "target");
    }

    public static function getSourceOrdered($id_job){
        return self::getLimitedTypeOrderedByJobId($id_job, "source");
    }

    public static function getTypeOrderedByJobId($id_job, $type, $ttl = 0){
        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $query = "SELECT * FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? and order in()
        ORDER by sm.order";
        $stmt = $conn->prepare( $query );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), [ $id_job, $type ] );
    }

    public static function getLimitedTypeOrderedByJobId($id_job, $type, $ttl = 0, $where = "center", $order, $segmentAmount = 0){
        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();

        if(empty($segmentAmount)){
            $config = Aligner::getConfig();
            $segmentAmount = $config['SEGMENT_AMOUNT_PER_PAGE'];
        }

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

        switch ($where){
            case 'before':
                $subquery = sprintf( $queryBefore, $segmentAmount );
                $queryParams = array( $id_job, $type, $order );
                break;
            case 'after':
                $subquery = sprintf( $queryAfter, $segmentAmount );
                $queryParams = array( $id_job, $type, $order );
                break;
            case 'center':
                $subquery = sprintf( $queryCenter, floor($segmentAmount/2), ceil($segmentAmount/2) );
                $queryParams = array( $id_job, $type, $order, $id_job, $type, $order );
                break;
        }

        $query = "SELECT * FROM segments as s
        RIGHT JOIN segments_match as sm ON s.id = sm.segment_id
        WHERE sm.id_job = ? AND sm.type = ? and `order` in ($subquery)
        ORDER by sm.order";

        $queryParams = array_merge( array( $id_job, $type ), $queryParams );

        $stmt = $conn->prepare( $query );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Segments_SegmentStruct(), $queryParams );
    }

    public function countByJobId($id_job, $type){
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( "SELECT count(id) FROM segments WHERE id_job = ? AND type = ? ORDER BY id ASC" );
        $stmt->execute( [$id_job, $type] );

        $result = $stmt->fetch();
        return $result[0];
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
