<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 31/08/2018
 * Time: 11:20
 */

namespace Features\Aligner\Controller;

use Exceptions\NotFoundError;
use Features\Aligner\Model\Files_FileDao;
use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Model\Segments_SegmentMatchDao;

class JobController extends AlignerController {

    public function information() {

        $id_job  = $this->params[ 'id_job' ];
        $password  = $this->params[ 'password' ];
        $job     = Jobs_JobDao::getByIdAndPassword( $id_job, $password );
        if(empty($job)){
            throw new NotFoundError("Job not found");
        }
        $project = $job->getProject();

        $segmentDao            = new Segments_SegmentDao;
        $count_source_segments = $segmentDao->countByJobId( $id_job, "source" );
        $count_source_segments = ( !empty($count_source_segments) ) ? $count_source_segments[0]['amount'] : 0;
        $count_target_segments = $segmentDao->countByJobId( $id_job, "target" );
        $count_target_segments = ( !empty($count_target_segments) ) ? $count_target_segments[0]['amount'] : 0;

        $source_file = Files_FileDao::getByJobId($id_job, "source");
        $target_file = Files_FileDao::getByJobId($id_job, "target");

        $segmentMatchDao = new Segments_SegmentMatchDao;
        $miss_alignments = $segmentMatchDao->missAlignments($id_job);

        $information = [
                'job_name'              => $project->name,
                'source_lang'           => $job->source,
                'target_lang'           => $job->target,
                'total_source_segments' => $count_source_segments,
                'total_target_segments' => $count_target_segments,
                'miss_alignments' => $miss_alignments,
                'source_filename' => $source_file->filename,
                'target_filename' => $target_file->filename
        ];


        return $this->response->json( $information );

    }
}