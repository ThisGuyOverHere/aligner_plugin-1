<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 28/08/2018
 * Time: 11:26
 */

namespace Features\Aligner\Controller;

use Features\Aligner\Utils\AlignUtils;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Controller\Validators\JobPasswordValidator;

class SegmentsController extends AlignerController {

    protected $job;

    public function afterConstruct() {
        $jobValidator = ( new JobPasswordValidator( $this ) );

        $jobValidator->onSuccess( function () use ( $jobValidator ) {
            $this->job     = $jobValidator->getJob();
        } );

        $this->appendValidator( $jobValidator );
    }

    public function get(){
        $id_job = $this->job->id;

        $target = Segments_SegmentDao::getTypeOrderedByJobId($id_job, 'target');
        foreach ($target as $key => $segment){
            $target[$key]->content_raw   = AlignUtils::_mark_xliff_tags($segment->content_raw);
            $target[$key]->content_clean = htmlspecialchars_decode($segment->content_clean);
        }

        $source = Segments_SegmentDao::getTypeOrderedByJobId($id_job, 'source');
        foreach ($source as $key => $segment){
            $source[$key]->content_raw   = AlignUtils::_mark_xliff_tags($segment->content_raw);
            $source[$key]->content_clean = htmlspecialchars_decode($segment->content_clean);
        }
        $this->response->json(['target' => $target, 'source' => $source]);

    }
}