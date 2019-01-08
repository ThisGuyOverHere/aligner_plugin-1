<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 12:42
 */


namespace Features\Aligner\Model;

use DataAccess\ArrayAccessTrait;

class Segments_SegmentStruct extends \DataAccess_AbstractDaoSilentStruct implements \DataAccess_IDaoStruct {
    use ArrayAccessTrait;

    public $id ;
    public $id_job ;
    public $type ;
    public $order ;
    public $next ;
    public $content_clean ;
    public $content_raw ;
    public $content_hash ;
    public $language_code ;
    public $raw_word_count;
    public $score;
    public $hidden;


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

}
