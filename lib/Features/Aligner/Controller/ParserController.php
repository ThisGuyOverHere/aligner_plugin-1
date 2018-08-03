<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 02/08/2018
 * Time: 13:33
 */


namespace Features\Aligner\Controller;
include_once \INIT::$UTILS_ROOT . "/xliff.parser.1.3.class.php";

use CatUtils;
use Exception;
use Features\Aligner\Model\Files_FileDao;

class ParserController extends AlignerController {


    /**
     * @throws Exception
     */
    public function jobParser() {
        $id_job = $this->params['id_job'];

        $source_file = Files_FileDao::getByJobId($id_job, "source")[0];
        $target_file = Files_FileDao::getByJobId($id_job, "target")[0];

        $source_lang = $source_file->language_code;
        $target_lang = $target_file->language_code;

        $source_segments = $this->_file2segments($source_file, $source_lang);
        $target_segments = $this->_file2segments($target_file, $target_lang);

        $this->response->json( ['source' => $source_segments, 'target' => $target_segments] );
    }


    /**
     * @param $file
     * @param $lang
     * @return array
     * @throws Exception
     */
    protected function _file2segments($file, $lang) {
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

            // An xliff can contains multiple files (docx has style, settings, ...) but only some with useful trans-units
            if ( !array_key_exists( 'trans-units', $xliff_file ) ) {
                continue;
            }

            foreach ($xliff_file[ 'trans-units' ] as $trans_unit) {

                // Extract only raw-content
                $unit_segments = array_map(function ($item) {
                    return $item['raw-content'];
                }, $trans_unit[ 'seg-source' ]);

                // Build an object with raw-content and clean-content
                $unit_segments = array_map(function ($item) use ($lang) {
                    return [
                        'raw' => $item,
                        'clean' => $this->_cleanSegment($item, $lang),
                        'words' => $this->_countWordsInSegment($item, $lang)
                    ];
                }, $unit_segments);

                // Append to existing Segments
                $segments = array_merge($segments, $unit_segments);
            }
        }

        return $segments;
    }

    protected function _cleanSegment($segment, $lang) {
        return $segment;
    }

    protected function _countWordsInSegment($segment, $lang) {
        $wordCount = CatUtils::segment_raw_wordcount( $segment, $lang );

        return $wordCount;
    }
}
