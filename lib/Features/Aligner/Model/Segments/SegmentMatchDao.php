<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 17:23
 */
namespace Features\Aligner\Model;

class Segments_SegmentMatchDao extends DataAccess_AbstractDao {
    const TABLE = "segments_match";


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

}