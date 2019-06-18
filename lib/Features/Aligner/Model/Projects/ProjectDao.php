<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 12:32
 */
namespace Features\Aligner\Model;

class Projects_ProjectDao extends DataAccess_AbstractDao {
    const TABLE = "projects";

    protected static $auto_increment_fields = array('id');
    protected static $primary_keys = array('id');

    /**
     * @param $project
     * @param $field
     * @param $value
     *
     * @return Projects_ProjectStruct
     */
    public static function updateField( $project, $field, $value ) {

        $sql = "UPDATE projects SET {$field} = :value WHERE id = :id ";

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
     * @param Projects_ProjectStruct $project
     * @param                        $newPass
     *
     * @return Projects_ProjectStruct
     * @internal param $pid
     */
    public function changePassword( Projects_ProjectStruct $project, $newPass ){
        $res = self::updateField( $project, 'password', $newPass );
        $this->destroyCacheById( $project->id );
        return $res;
    }

    public function deleteFailedProject( $idProject ){

        if( empty( $idProject ) ) return 0;

        $sql = "DELETE FROM projects WHERE id = :id_project";
        $conn = NewDatabase::obtain()->getConnection();
        $stmt = $conn->prepare( $sql );
        $success = $stmt->execute( [ 'id_project' => $idProject ] );
        return $stmt->rowCount();

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
        $stmt = $conn->prepare( " SELECT * FROM projects WHERE id = :id " );
        return @$thisDao->setCacheTTL( $ttl )->_fetchObject( $stmt, new Projects_ProjectStruct(), [ 'id' => $id ] )[ 0 ];

    }

    public static function createFromStruct(Projects_ProjectStruct $project){
        $conn = NewDatabase::obtain()->getConnection();

        $projStructToArray = $project->toArray();
        $columns = array_keys( $projStructToArray );
        $values = array_values( $projStructToArray );

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

        $stmt = $conn->prepare( 'INSERT INTO `projects` ( ' . implode( ',', $columns ) . ' ) VALUES ( ' . implode( ',' , array_fill( 0, count( $values ), '?' ) ) . ' )' );

        foreach( $values as $k => $v ){
            $stmt->bindValue( $k +1, $v ); //Columns/Parameters are 1-based
        }

        $stmt->execute();

        $project = static::findById( $conn->lastInsertId() );

        $conn->commit();

        return $project;
    }



}
