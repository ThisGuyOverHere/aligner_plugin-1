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

        $fileStorage = new \FilesStorage;

        $source_file = Files_FileDao::getByJobId($id_job, "source")[0];
        $target_file = Files_FileDao::getByJobId($id_job, "target")[0];

        list($date, $sha1_source) = explode("/", $source_file->sha1_original_file);
        $xliff_source_file = $fileStorage->getXliffFromCache($sha1_source, $source_file->language_code);
        $xliff_source_content = file_get_contents($xliff_source_file);
        $source_parser = new \Xliff_Parser;
        $source_array = $source_parser->Xliff2Array($xliff_source_content);

        list($date, $sha1_target) = explode("/", $target_file->sha1_original_file);
        $xliff_target_file = $fileStorage->getXliffFromCache($sha1_target, $target_file->language_code);
        $xliff_target_content = file_get_contents($xliff_target_file);
        $target_parser = new \Xliff_Parser;
        $target_array = $target_parser->Xliff2Array($xliff_target_content);


        sleep(1);

    }
}