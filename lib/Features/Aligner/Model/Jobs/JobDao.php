<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 16:38
 */
namespace Features\Aligner\Model;

use DataAccess\LoudArray;
use DataAccess\ShapelessConcreteStruct;

class Jobs_JobDao extends DataAccess_AbstractDao {

    const TABLE       = "jobs";
    const STRUCT_TYPE = "Jobs_JobStruct";

    protected static $auto_increment_fields = [ 'id' ];
    protected static $primary_keys          = [ 'id', 'password' ];

    protected static $_sql_update_password = "UPDATE jobs SET password = :new_password WHERE id = :id AND password = :old_password ";

    protected static $_sql_get_jobs_by_project = "SELECT * FROM jobs WHERE id_project = ? ORDER BY id ASC;";


    /**
     * @param     $id_job
     * @param     $password
     * @param int $ttl
     *
     * @return Jobs_JobStruct
     */
    public static function getByIdAndPassword( $id_job, $password, $ttl = 0 ) {
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare(
                "SELECT * FROM jobs WHERE " .
                " id = :id_job AND password = :password "
        );

        $thisDao = new self();
        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Jobs_JobStruct(), [
                'id_job' => $id_job,
                'password' => $password
        ] )[ 0 ];

    }

    public static function getByProjectId( $id_project, $ttl = 0 ) {

        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( self::$_sql_get_jobs_by_project );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Jobs_JobStruct(), [ $id_project ] );

    }


    /**
     *
     * @param int $id_job
     *
     * @param int $ttl
     *
     * @return DataAccess_IDaoStruct[]|Jobs_JobStruct[]
     */
    public static function getById( $id_job, $ttl = 0 ) {

        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Jobs_JobStruct(), [ $id_job ] );

    }


    /**
     * For now this is used by tests
     *
     * @param Jobs_JobStruct $jobStruct
     *
     * @return Jobs_JobStruct
     */
    public static function createFromStruct( Jobs_JobStruct $jobStruct ){

        $conn = NewDatabase::obtain()->getConnection();

        $jobStructToArray = $jobStruct->toArray();
        $columns = array_keys( $jobStructToArray );
        $values = array_values( $jobStructToArray );

        //clean null values
        foreach( $values as $k => $val ){
            if( is_null( $val ) ){
                unset( $values[ $k ] );
                unset( $columns[ $k ] );
            }
        }

        //reindex the array
        $columns = array_values( $columns );
        $values  = array_values( $values );

        NewDatabase::obtain()->begin();

        $stmt = $conn->prepare( 'INSERT INTO `jobs` ( ' . implode( ',', $columns ) . ' ) VALUES ( ' . implode( ',' , array_fill( 0, count( $values ), '?' ) ) . ' )' );

        foreach( $values as $k => $v ){
            $stmt->bindValue( $k +1, $v ); //Columns/Parameters are 1-based
        }

        $stmt->execute();

        $job = static::getById( $conn->lastInsertId() )[0];

        $conn->commit();

        return $job;

    }



    public function changePassword( Jobs_JobStruct $jStruct, $new_password ){

        if( empty( $new_password ) ) throw new \PDOException( "Invalid empty value: password." );

        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( self::$_sql_update_password );
        $stmt->execute( [
                'id'           => $jStruct->id,
                'new_password' => $new_password,
                'old_password' => $jStruct->password
        ] );

        $jStruct->password = $new_password;

        return $jStruct;

    }

    public static function changeStatusAnalysis( $id, $new_status ){

        $query = "UPDATE `jobs`
        SET `jobs`.`status_analysis` = ?
        WHERE `jobs`.`id` = ?;";

        $params = [ $new_status, $id ];

        $conn = NewDatabase::obtain()->getConnection();
        $stm = $conn->prepare( $query );
        $stm->execute( $params );

    }

}
