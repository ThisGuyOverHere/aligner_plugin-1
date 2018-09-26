<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 31/08/2018
 * Time: 11:20
 */

namespace Features\Aligner\Controller;

use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Model\Segments_SegmentMatchDao;

class JobController extends AlignerController {

    public function information() {
        /*{
      job_name: String,
      miss_alignments: [
          {type: String, order: Number},
          {type: String, order: Number},
      ],
        total_lines: Number,
      total_segments: Number,
        source_lang: String,
        target_lang: String

}*/
        $id_job  = $this->params[ 'id_job' ];
        $job     = Jobs_JobDao::getById( $id_job )[ 0 ];
        $project = $job->getProject();

        $segmentDao            = new Segments_SegmentDao;
        $count_source_segments = $segmentDao->countByJobId( $id_job, "source" );
        $count_target_segments = $segmentDao->countByJobId( $id_job, "target" );

        $segmentMatchDao = new Segments_SegmentMatchDao;
        $miss_alignments = $segmentMatchDao->missAlignments($id_job);

        $information = [
                'job_name'              => $project->name,
                'source_lang'           => $job->source,
                'target_lang'           => $job->target,
                'total_source_segments' => $count_source_segments,
                'total_target_segments' => $count_target_segments,
                'miss_alignments' => $miss_alignments
        ];


        return $this->response->json( $information );

    }
}