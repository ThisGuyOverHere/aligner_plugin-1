<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 18:17
 */

namespace Features\Aligner\Model;

class Files_FileDao extends DataAccess_AbstractDao {
    const TABLE = "files";

    protected static $auto_increment_fields = ['id'] ;

    /**
     * @param     $id_job
     *
     * @param $type
     *
     * @param int $ttl
     *
     * @return DataAccess_IDaoStruct[]|Files_FileStruct[]
     */
    public static function getByJobId( $id_job, $type, $ttl = 60 ) {

        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare(
                "SELECT * FROM files WHERE id_job = :id_job AND `type` = :type "
        );

        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Files_FileStruct, [ 'id_job' => $id_job, 'type' => $type ] );

    }

    /**
     * @param     $id_project
     *
     * @param int $ttl
     *
     * @return DataAccess_IDaoStruct[]|Files_FileStruct[]
     */
    public static function getByProjectId( $id_project, $ttl = 600 ) {
        $thisDao = new self();
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( "SELECT * FROM files where id_project = :id_project ");
        return $thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Files_FileStruct, [ 'id_project' => $id_project ] );
    }

    public static function updateField( $file, $field, $value ) {
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare(
                "UPDATE files SET $field = :value " .
                " WHERE id = :id "
        );

        return $stmt->execute( array(
                'value' => $value,
                'id' => $file->id
        ));
    }

    /**
     * @param $id
     *
     * @return Files_FileStruct
     */
    public static function getById( $id ) {
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( "SELECT * FROM files where id = :id ");
        $stmt->execute( array( 'id' => $id ) );
        $stmt->setFetchMode(\PDO::FETCH_CLASS, 'Features\Aligner\Model\Files_FileStruct');
        return $stmt->fetch();
    }

    public static function createFromStruct( Files_FileStruct $fileStruct ){

        $conn = NewDatabase::obtain()->getConnection();

        $fileStructToArray = $fileStruct->toArray();
        $columns = array_keys( $fileStructToArray );
        $values = array_values( $fileStructToArray );

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

        $stmt = $conn->prepare( 'INSERT INTO '.self::TABLE.' ( ' . implode( ',', $columns ) . ' ) VALUES ( ' . implode( ',' , array_fill( 0, count( $values ), '?' ) ) . ' )' );

        foreach( $values as $k => $v ){
            $stmt->bindValue( $k +1, $v ); //Columns/Parameters are 1-based
        }

        $stmt->execute();

        $file = static::getById( $conn->lastInsertId() );

        $conn->commit();

        return $file;

    }

}
