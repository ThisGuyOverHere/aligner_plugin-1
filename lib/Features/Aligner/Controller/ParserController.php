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
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Utils\Alignment;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Utils\Constants;


class ParserController extends AlignerController {

    protected $id_job;

    protected function mergeSegments(Array $segments){
        $new_segment = array();
        $new_segment['id'] = $segments[0]['id'];
        $new_segment['content_clean'] = '';
        foreach ($segments as $segment){
            $new_segment['content_clean'].= ' '.$segment['content_clean'];
        }
        Segments_SegmentDao::mergeSegments($segments);
        return $new_segment;
    }


    /**
     * @throws Exception
     */
    public function jobParser() {
        ini_set('max_execution_time', 200);
        $this->id_job = $this->params['id_job'];
        $job = Jobs_JobDao::getById($this->id_job)[0];

        $segmentsMatchDao = new Segments_SegmentMatchDao;
        $segmentsMatchDao->deleteByJobId($this->id_job);

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
            case 'v4':
                $alignment = $alignment_class->_alignSegmentsV4($source_segments, $target_segments, $source_lang, $target_lang);
                break;
        }

        // DEBUG //
//        $this->response->json( ['res' => $alignment] );

        $source_array = [];
        $target_array = [];
        foreach($alignment as $key => $value){
            $source_element = [];

            if(!empty($value['source'])){
                //Check if $value['source'] is an array of segments otherwise it wouldn't have any numerical keys
                if(isset($value['source'][0])){
                    $value['source'] = $this->mergeSegments($value['source']);
                }
            }
            $source_element['segment_id'] = $value['source']['id'];
            $source_element['order'] = ($key+1)*Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $source_element['next'] = ($key+2)*Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $source_element['id_job'] = $this->id_job;
            $source_element['score'] = $value['score'];
            $source_element['type'] = "source";

            $target_element = [];

            if(!empty($value['target'])){
                //Check if $value['target'] is an array of segments otherwise it wouldn't have any numerical keys
                if(isset($value['target'][0])){
                    $value['target'] = $this->mergeSegments($value['target']);
                }
            }

            $target_element['segment_id'] = $value['target']['id'];
            $target_element['order'] = ($key+1)*Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $target_element['next'] = ($key+2)*Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $target_element['score'] = $value['score'];
            $target_element['id_job'] = $this->id_job;
            $target_element['type'] = "target";

            $source_array[] = $source_element;
            $target_array[] = $target_element;
        }

        $source_array[count($source_array)-1]['next'] = null;
        $target_array[count($target_array)-1]['next'] = null;


        $segmentsMatchDao->createList($source_array);
        $segmentsMatchDao->createList($target_array);

        $this->response->json( ['source' => $source_array, 'target' => $target_array] );
    }

}
