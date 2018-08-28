<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 27/08/2018
 * Time: 12:42
 */


namespace Features\Aligner\Model;

class Segments_SegmentStruct extends \DataAccess_AbstractDaoSilentStruct implements \DataAccess_IDaoStruct {
    public $id ;
    public $id_job ;
    public $id_file ;
    public $type ;
    public $content ;
    public $content_hash ;
    public $language_code ;
    public $raw_word_count;


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
