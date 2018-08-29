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
use Features\Aligner;
use Features\Aligner\Model\Files_FileDao;
use Features\Aligner\Utils\Alignment;
use Features\Aligner\Model\NewDatabase;


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

        $version = $this->params['version'];

        $alignment_class = new Alignment;

        switch ($version) {
            case 'v0':
                $alignment = $alignment_class->_alignSegmentsV0($source_segments, $target_segments);
                break;
            case 'v1':
                $alignment = $alignment_class->_alignSegmentsV1($source_segments, $target_segments);
                break;
            case 'v2':
                $alignment = $alignment_class->_alignSegmentsV2($source_segments, $target_segments, $source_lang, $target_lang);
                break;
            case 'v3':
                $alignment = $alignment_class->_alignSegmentsV3($source_segments, $target_segments, $source_lang, $target_lang);
                break;
            case 'v3b':
                $alignment = $alignment_class->_alignSegmentsV3B($source_segments, $target_segments, $source_lang, $target_lang);
                break;
            case 'v3c':
                $alignment = $alignment_class->_alignSegmentsV3C($source_segments, $target_segments, $source_lang, $target_lang);
                break;
        }

        // DEBUG //
//        $this->response->json( ['res' => $alignment] );

        // Format alignment for frontend test purpose
        $source = array_map(function ($index, $item) {
            return [
                'clean' => $item['source']['content_clean'],
                'raw' => $item['source']['content_raw'],
                'order' => ($index + 1)* 1000000000,
                'next' => ($index + 2) * 1000000000
                ];
        }, array_keys($alignment), $alignment);

        $target = array_map(function ($index, $item) {
            return [
                'clean' => $item['target']['content_clean'],
                'raw' => $item['target']['content_raw'],
                'order' => ($index + 1)* 1000000000,
                'next' => ($index + 2) * 1000000000
            ];
        }, array_keys($alignment), $alignment);

        $source[count($source)-1]['next'] = null;
        $target[count($target)-1]['next'] = null;

        $this->response->json( ['source' => $source, 'target' => $target] );
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
                        'content_raw' => $item,
                        'content_clean' => $this->_cleanSegment($item, $lang),
                        'raw_word_count' => $this->_countWordsInSegment($item, $lang)
                    ];
                }, $unit_segments);

                // Append to existing Segments
                $segments = array_merge($segments, $unit_segments);
            }
        }

        return $segments;
    }

    /**
     *
     * Code almost cloned from CatUtils::placehold_xliff_tags()
     *
     * @param $segment
     * @param $lang
     * @return null|string|string[]
     */
    protected function _cleanSegment($segment, $lang) {

        $tagsRegex = [
                '|(</x>)|si',
                '|<(g\s*id=["\']+.*?["\']+\s*[^<>]*?)>|si',
                '|<(/g)>|si',
                '|<(x .*?/?)>|si',
                '#<(bx[ ]{0,}/?|bx .*?/?)>#si',
                '#<(ex[ ]{0,}/?|ex .*?/?)>#si',
                '|<(bpt\s*.*?)>|si',
                '|<(/bpt)>|si',
                '|<(ept\s*.*?)>|si',
                '|<(/ept)>|si',
                '|<(ph .*?)>|si',
                '|<(/ph)>|si',
                '|<(it .*?)>|si',
                '|<(/it)>|si',
                '|<(mrk\s*.*?)>|si',
                '|<(/mrk)>|si'
        ];

        foreach ($tagsRegex as $regex) {
            $segment = preg_replace($regex, '', $segment);
        }

        return $segment;
    }

    /**
     * @param $segment
     * @param $lang
     * @return float|int
     */
    protected function _countWordsInSegment($segment, $lang) {
        $wordCount = CatUtils::segment_raw_wordcount( $segment, $lang );

        return $wordCount;
    }

    private function __storeSegments($segments){

        $sequenceIds = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count( $segments ) );
        foreach($sequenceIds as $key => $sequenceId){
            $segments[$key]['id'] = $sequenceId;

        }


    }
}
