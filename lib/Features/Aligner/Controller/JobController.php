<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 31/08/2018
 * Time: 11:20
 */

namespace Features\Aligner\Controller;

use Exceptions\ValidationError;
use Features\Aligner;
use Features\Aligner\Controller\Validators\JobPasswordValidator;
use Features\Aligner\Model\Files_FileDao;
use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Model\Jobs_JobStruct;
use Features\Aligner\Model\Projects_ProjectDao;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Utils\ConstantsJobAnalysis;
use Features\Aligner\Utils\ProjectProgress;


class JobController extends AlignerController {

    use ProjectProgress;
    /**
     * @var $job Jobs_JobStruct
     */
    protected $job;
    protected $project;

    public function afterConstruct() {
        $jobValidator = ( new JobPasswordValidator( $this ) );

        $jobValidator->onSuccess( function () use ( $jobValidator ) {
            $this->job     = $jobValidator->getJob();
            $this->project = $this->job->getProject();
        } );

        $this->appendValidator( $jobValidator );
    }

    public function information() {

        $source_file = Files_FileDao::getByJobId( $this->job->id, "source" );
        $target_file = Files_FileDao::getByJobId( $this->job->id, "target" );

        $language = \Langs_Languages::getInstance();


        $information = [
                'job_name'        => $this->project->name,
                'source_lang'     => $this->job->source,
                'target_lang'     => $this->job->target,
                'source_filename' => $source_file->filename,
                'target_filename' => $target_file->filename,
                'target_is_rtl'   => $language->isRTL( $this->job->target ),
                'source_is_rtl'   => $language->isRTL( $this->job->source )
        ];


        return $this->response->json( $information );

    }

    public function checkProgress() {


        $id_job  = $this->job->id;
        $project = Projects_ProjectDao::findById( $this->job->id_project )->toArray();

        $status_analysis = ( !empty( $project ) ) ? $project[ 'status_analysis' ] : ConstantsJobAnalysis::ALIGN_PHASE_0;

        $this->setRedisClient( ( new \RedisHandler() )->getConnection() );
        $progress = $this->getProgress( $project[ 'id' ] );

        $segment_count = $this->getSegmentCount($project['id']);
        $config = Aligner::getConfig();

        if($segment_count < $config['LOW_LIMIT_QUEUE_SIZE']){
            $queue = 'align_job_small_list';
        } else if ($segment_count < $config['HIGH_LIMIT_QUEUE_SIZE']){
            $queue = 'align_job_medium_list';
        } else {
            $queue = 'align_job_big_list';
        }

        $segmentDao = new Segments_SegmentDao;

        $source_segments = null;
        $target_segments = null;

        $previous_project_number = null;

        switch ( $status_analysis ) {
            case ConstantsJobAnalysis::ALIGN_PHASE_0:
                $phase  = 0;
                //$previous_project_number = \AMQHandler::getQueueLength( 'aligner_align_job' );
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_1:
                $jobs_in_queue = $this->getJobsInQueue($queue);
                $previous_project_number = $this->getNumbersOfPreviousQueues($this->job->id, $jobs_in_queue);
                $phase = 1;
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_2:
                $phase = 2;
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_3:
                $phase = 3;
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_4:
                $phase = 4;
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_5:
                $phase = 5;
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_6:
                $phase = 6;
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_7:
                $phase = 7;
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_8:
                throw new ValidationError( "Max words limit exceeded" );
                break;
            case ConstantsJobAnalysis::ALIGN_PHASE_9:
                throw new ValidationError( "Error during analysis, please retry" );
                break;
            default:
                $phase = 1;
                break;
        }

        if ( in_array( $phase, [ 2, 3, 4, 5, 6, 7 ] ) ) {
            $source_segments = $segmentDao->countByJobId( $id_job, 'source', 3600 );
            $source_segments = ( !empty( $source_segments ) ) ? $source_segments[ 0 ][ 'amount' ] : null;
            $target_segments = $segmentDao->countByJobId( $id_job, 'target', 3600 );
            $target_segments = ( !empty( $target_segments ) ) ? $target_segments[ 0 ][ 'amount' ] : null;
        }


        return $this->response->json( [
                        'previous_project_number' => (is_numeric($previous_project_number))?$previous_project_number-1:$previous_project_number,
                        'phase'                   => $phase,
                        'phase_name'              => $status_analysis,
                        'progress'                => (int)$progress,
                        'source_segments'         => $source_segments,
                        'target_segments'         => $target_segments,
                ]
        );
    }

    protected function getNumbersOfPreviousQueues($job_id, $jobs_in_queue){
    $previous_queues = 0;
    foreach($jobs_in_queue as $job_in_queue){
        $previous_queues++;
        if($job_in_queue == $job_id){
            break;
        }
    }
    return $previous_queues;
}
}