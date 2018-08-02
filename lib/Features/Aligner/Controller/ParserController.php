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
use Exception;

class ParserController extends AlignerController {


    /**
     * @throws Exception
     */
    public function jobParser() {
        $id_job = $this->params['id_job'];

        $source_file = Files_FileDao::getByJobId($id_job, "source")[0];
        $target_file = Files_FileDao::getByJobId($id_job, "target")[0];

        $source_segments = $this->_file2segments($source_file);
        $target_segments = $this->_file2segments($target_file);

        $this->response->json( ['source' => $source_segments, 'target' => $target_segments] );
    }


    /**
     * @param $file
     * @return array
     * @throws Exception
     */
    protected function _file2segments($file) {
        list($date, $sha1) = explode("/", $file->sha1_original_file);

        // Get file content
        try {
            $fileStorage = new \FilesStorage;
            $xliff_file = $fileStorage->getXliffFromCache($sha1, $file->language_code);
            $xliff_content = file_get_contents($xliff_file);
        } catch ( Exception $e ) {
            throw new Exception( $file, $e->getCode(), $e );
        }

        // Parse xliff
        try {
            $parser = new \Xliff_Parser;
            $xliff = $parser->Xliff2Array($xliff_content);
        } catch ( Exception $e ) {
            throw new Exception( $file, $e->getCode(), $e );
        }

        // Checking that parsing went well
        if ( isset( $xliff[ 'parser-errors' ] ) or !isset( $xliff[ 'files' ] ) ) {
            throw new Exception( $file, -4 );
        }

        // Creating the Segments
        $segments = array();

        foreach ( $xliff[ 'files' ] as $xliff_file ) {

            if ( !array_key_exists( 'trans-units', $xliff_file ) ) {
                continue;
            }

            $segments = array_merge($segments,  $xliff_file[ 'trans-units' ]);
        }

        return $segments;
    }
}