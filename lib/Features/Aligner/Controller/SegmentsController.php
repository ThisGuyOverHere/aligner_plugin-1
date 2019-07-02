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

    public function get() {
        $id_job = $this->job->id;

        $order_segments = Segments_SegmentDao::getOrderedByJobId( $id_job );
        $target         = [];
        $source         = [];

        foreach ( $order_segments as &$order_segment ) {
            $order_segment->content_clean = htmlspecialchars_decode( $order_segment->content_clean );
            if ( $order_segment->type == "source" ) {
                $source[] = $order_segment;
            } elseif ( $order_segment->type == "target" ) {
                $target[] = $order_segment;
            }
        }
        $this->response->json( [ 'target' => $target, 'source' => $source ] );

    }
}