<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 17:23
 */

namespace Features\Aligner\Model;

class Segments_SegmentMatchStruct extends \DataAccess_AbstractDaoSilentStruct implements \DataAccess_IDaoStruct {
    public $id_job ;
    public $order ;
    public $type ;
    public $segment_id ;
    public $next ;
    public $score ;

    /**
     * @param int $ttl
     *
     * @return Jobs_JobStruct[]
     */
    public function getJob( $ttl = 0 ) {
        return $this->cachable(__function__, $this->id_job, function($id) use( $ttl ) {
            return Jobs_JobDao::getById( $id, $ttl );
        });
    }

    public function getSegment( ) {
        return Segments_SegmentDao::findById( $this->segment_id );
    }


}