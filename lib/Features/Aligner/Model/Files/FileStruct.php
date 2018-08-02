<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 18:18
 */

namespace Features\Aligner\Model;

class Files_FileStruct extends \DataAccess_AbstractDaoSilentStruct implements \DataAccess_IDaoStruct {
    public $id  ;
    public $id_project  ;
    public $id_job ;
    public $filename ;
    public $type ;
    public $language_code ;
    public $mime_type ;
    public $sha1_original_file ;

    public function getSegmentsCount() {
        return ( new Segments_SegmentDao() )->countByFile( $this );
    }

}
