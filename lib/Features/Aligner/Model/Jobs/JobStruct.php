<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 16:38
 */
namespace Features\Aligner\Model;

use DataAccess\ArrayAccessTrait;

class Jobs_JobStruct extends \DataAccess_AbstractDaoSilentStruct implements \DataAccess_IDaoStruct, \ArrayAccess {

    use ArrayAccessTrait;

    public $id;
    public $password;
    public $id_project;
    public $source;
    public $target;
    public $create_date;
    public $last_update;
    public $status;
    public $subject;


    /**
     * @return Files_FileStruct[]
     */
    public function getFiles() {
        return Files_FileDao::getByJobId( $this->id );
    }

    /**
     * getProject
     *
     * Returns the project struct, caching the result on the instance to avoid
     * unnecessary queries.
     *
     * @param int $ttl
     *
     * @return \Projects_ProjectStruct
     */
    public function getProject( $ttl = 86400 ) {
        return $this->cachable( __function__, $this, function ( $job ) use ( $ttl ){
            return Projects_ProjectDao::findById( $job->id_project, $ttl );
        } );
    }

    public function isCanceled() {
        return $this->status == Constants_JobStatus::STATUS_CANCELLED ;
    }

    public function isArchived() {
        return $this->status == Constants_JobStatus::STATUS_ARCHIVED ;
    }



}
