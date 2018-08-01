<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 14:37
 */

namespace Features\Aligner\Model;

class Projects_ProjectStruct extends \DataAccess_AbstractDaoSilentStruct implements \DataAccess_IDaoStruct {
    public $id ;
    public $password ;
    public $name ;
    public $id_customer ;
    public $create_date ;
    public $status_analysis ;
    public $remote_ip_address ;
    public $due_date;

    /**
     * @return bool
     */
    public function analysisComplete() {
        return
                $this->status_analysis == \Constants_ProjectStatus::STATUS_DONE ||
                $this->status_analysis == \Constants_ProjectStatus::STATUS_NOT_TO_ANALYZE ;
    }

    /**
     * @param int $ttl
     *
     * @return Jobs_JobStruct[]
     */
    public function getJobs( $ttl = 0 ) {
        return $this->cachable(__function__, $this->id, function($id) use( $ttl ) {
            return Jobs_JobDao::getByProjectId( $id, $ttl );
        });
    }


    public function getRemoteFileServiceName(){

        return $this->cachable( __function__, $this, function () {

            $dao = new Projects_ProjectDao() ;
            return @$dao->setCacheTTL( 60 * 60 * 24 * 7 )->getRemoteFileServiceName( [ $this->id ] )[0] ;

        } );

    }


    /**
     * WARNING $id_customer could not correspond to the real team/assignee
     *
     * @return Users_UserStruct
     */
    public function getOriginalOwner() {
        return ( new \Users_UserDao() )->setCacheTTL( 60 * 60 * 24 * 30 )->getByUid( $this->id_customer ) ;
    }




}
