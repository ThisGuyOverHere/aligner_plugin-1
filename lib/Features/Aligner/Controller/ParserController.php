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

        $files = Files_FileDao::getByJobId($id_job);

        foreach($files as $file){
            list($date, $sha1) = explode("/", $file->sha1_original_file);
            $fileStorage = new \FilesStorage;
            $xliff_file = $fileStorage->getXliffFromCache($sha1, $file->language_code);
            $xliff_content = file_get_contents($xliff_file);
            $parser = new \Xliff_Parser;
            $array = $parser->Xliff2Array($xliff_content);
        }


        sleep(1);

    }
}