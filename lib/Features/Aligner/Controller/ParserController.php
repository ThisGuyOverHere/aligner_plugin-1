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
use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Utils\Alignment;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentDao;


class ParserController extends AlignerController {

    protected $id_job;


    /**
     * @throws Exception
     */
    public function jobParser() {
        $this->id_job = $this->params['id_job'];
        $job = Jobs_JobDao::getById($this->id_job)[0];

        $source_lang = $job->source;
        $target_lang = $job->target;

        $source_segments = Segments_SegmentDao::getDataForAlignment($this->id_job, "source");
        $target_segments = Segments_SegmentDao::getDataForAlignment($this->id_job, "target");

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

}
