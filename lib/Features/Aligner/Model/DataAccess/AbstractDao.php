<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 12:35
 */
namespace Features\Aligner\Model;


abstract class DataAccess_AbstractDao extends \DataAccess_AbstractDao {

    /*public function updateField( $project, $field, $value ) {

        $sql = "UPDATE ".self::TABLE." SET {$field} = :value WHERE id = :id ";

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

    }*/

}
