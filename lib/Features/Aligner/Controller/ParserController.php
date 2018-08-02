<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 02/08/2018
 * Time: 13:33
 */


namespace Features\Aligner\Controller;
include_once \INIT::$UTILS_ROOT . "/xliff.parser.1.3.class.php";

use Features\Aligner\Model\Files_FileDao;

class ParserController extends AlignerController {


    public function jobParser() {
        $id_job = $this->params['id_job'];

        $source_file = Files_FileDao::getByJobId($id_job, "source")[0];
        $target_file = Files_FileDao::getByJobId($id_job, "target")[0];

        $source_segments = $this->_file2array($source_file);
        $target_segments = $this->_file2array($target_file);

        $this->response->json( ['source' => $source_segments, 'target' => $target_segments] );
    }

    protected function _file2array($file) {
        list($date, $sha1) = explode("/", $file->sha1_original_file);

        $fileStorage = new \FilesStorage;
        $xliff_file = $fileStorage->getXliffFromCache($sha1, $file->language_code);
        $xliff_content = file_get_contents($xliff_file);

        $parser = new \Xliff_Parser;
        $array = $parser->Xliff2Array($xliff_content);

        $array = array_filter($array['files'], function($item) {
            return array_key_exists('trans-units', $item);
        });

        return reset($array)['trans-units'];
    }
}