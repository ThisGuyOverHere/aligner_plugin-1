<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 26/10/2018
 * Time: 11:24
 */

namespace Features\Aligner\Utils\AsyncTasks\Workers;

include_once \INIT::$UTILS_ROOT . "/xliff.parser.1.3.class.php";

use Exceptions\ValidationError;
use Features\Aligner;
use Features\Aligner\Model\Files_FileDao;
use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Model\Projects_ProjectDao;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Utils\Alignment;
use Features\Aligner\Utils\AlignUtils;
use Features\Aligner\Utils\Constants;
use Features\Aligner\Utils\ConstantsJobAnalysis;
use Features\Aligner\Utils\TaskRunner\Commons\AbstractWorker;
use FilesStorage\AbstractFilesStorage;
use FilesStorage\FilesStorageFactory;
use Features\Aligner\Utils\FilesStorage\S3AlignerFilesStorage;

class AlignJobWorker extends AbstractWorker {
    use Aligner\Utils\ProjectProgress;

    private $id_job;
    private $job;
    private $project;

    public function process( \TaskRunner\Commons\AbstractElement $queueElement ) {

        /**
         * @var $queueElement QueueElement
         */
        $this->_checkForReQueueEnd( $queueElement );
        $this->_checkDatabaseConnection();
        $this->setRedisClient($this->_queueHandler->getRedisClient());
        $this->_Align( $queueElement );
    }

    protected function _checkForReQueueEnd( \TaskRunner\Commons\QueueElement $queueElement ) {

        /**
         *
         * check for loop re-queuing
         */
        if ( isset( $queueElement->reQueueNum ) && $queueElement->reQueueNum >= 100 ) {

            $msg = "\n\n Error Set Contribution  \n\n " . var_export( $queueElement, true );
            \Utils::sendErrMailReport( $msg );
            $this->_doLog( "--- (Worker " . $this->_workerPid . ") :  Frame Re-queue max value reached, acknowledge and skip." );
            throw new \TaskRunner\Exceptions\EndQueueException( "--- (Worker " . $this->_workerPid . ") :  Frame Re-queue max value reached, acknowledge and skip.", self::ERR_REQUEUE_END );

        } elseif ( isset( $queueElement->reQueueNum ) ) {
            $this->_doLog( "--- (Worker " . $this->_workerPid . ") :  Frame re-queued {$queueElement->reQueueNum} times." );
        }

    }

    protected function _Align( \TaskRunner\Commons\QueueElement $queueElement ) {

        $attributes = json_decode( $queueElement->params );

        $this->id_job = $attributes->id_job;
        $this->job    = $attributes->job;
        $this->project = $attributes->project;

        $source_file  = $attributes->source_file;
        $target_file  = $attributes->target_file;

        $queue = $attributes->queue;

        \Log::doLog('/-------/ This job is in the following queue: '.$queue.' /--------/');

        Projects_ProjectDao::updateField($this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_1);


        \Log::doLog('STARTED ALIGN FOR JOB: '.$this->id_job);

        /*$job          = Jobs_JobDao::getById( $this->id_job )[ 0 ];
        $source_file  = Files_FileDao::getByJobId( $this->id_job, "source" );
        $target_file  = Files_FileDao::getByJobId( $this->id_job, "target" );*/

        $source_lang = $this->job->source;
        $target_lang = $this->job->target;

        try {

            Projects_ProjectDao::updateField($this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_2);

            $segmentsMatchDao = new Segments_SegmentMatchDao;
            //$segmentsMatchDao->deleteByJobId( $this->id_job );


            Projects_ProjectDao::updateField($this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_3);
            $this->updateProgress($this->project->id, 10);

            $source_segments = $this->_getSegmentsFromJson($source_file->sha1_original_file, 'source', $source_file->id, $this->project->name);
            $target_segments = $this->_getSegmentsFromJson($target_file->sha1_original_file, 'target', $target_file->id, $this->project->name);

            $alignment_class = new Alignment;


            $alignment = $alignment_class->alignSegments(
                    $this->project,
                    $source_segments,
                    $target_segments,
                    $source_lang,
                    $target_lang
            );

            Projects_ProjectDao::updateField($this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_6);
            $this->updateProgress($this->project->id, 95);


            $source_array = [];
            $target_array = [];
            foreach ( $alignment as $key => $value ) {
                $source_element = [];

                if ( !empty( $value[ 'source' ] ) ) {
                    //Check if $value['source'] is an array of segments otherwise it wouldn't have any numerical keys
                    if ( isset( $value[ 'source' ][ 0 ] ) ) {
                        $value[ 'source' ] = Segments_SegmentDao::mergeSegments( $value[ 'source' ] );
                    }
                }

                $source_element[ 'segment_id' ] = $value[ 'source' ][ 'id' ];
                $source_element[ 'order' ]      = ( $key + 1 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
                $source_element[ 'next' ]       = ( $key + 2 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
                $source_element[ 'id_job' ]     = $this->id_job;
                $source_element[ 'score' ]      = $value[ 'score' ];
                $source_element[ 'type' ]       = "source";

                $target_element = [];

                if ( !empty( $value[ 'target' ] ) ) {
                    //Check if $value['target'] is an array of segments otherwise it wouldn't have any numerical keys
                    if ( isset( $value[ 'target' ][ 0 ] ) ) {
                        $value[ 'target' ] = Segments_SegmentDao::mergeSegments( $value[ 'target' ] );
                    }
                }

                $target_element[ 'segment_id' ] = $value[ 'target' ][ 'id' ];
                $target_element[ 'order' ]      = ( $key + 1 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
                $target_element[ 'next' ]       = ( $key + 2 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
                $target_element[ 'score' ]      = $value[ 'score' ];
                $target_element[ 'id_job' ]     = $this->id_job;
                $target_element[ 'type' ]       = "target";

                $source_array[] = $source_element;
                $target_array[] = $target_element;
            }

            $source_array[ count( $source_array ) - 1 ][ 'next' ] = null;
            $target_array[ count( $target_array ) - 1 ][ 'next' ] = null;

            $segmentsMatchDao->createList( $source_array );
            $segmentsMatchDao->createList( $target_array );

            Projects_ProjectDao::updateField($this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_7);
            $this->updateProgress($this->project->id, 100);
            $this->popProjectInQueue($this->project->id, $queue);
        }catch (\Exception $e){
            \Log::doLog($e->getMessage());
            Projects_ProjectDao::updateField($this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_9);
            $this->updateProgress($this->project->id, 0);
            $this->popProjectInQueue($this->project->id, $queue);
        }

    }



    protected function _getSegmentsFromJson($dateHashPath, $type, $idFile, $newFileName){
        $fileStorage = FilesStorageFactory::create();

        list( $datePath, $hash ) = explode( DIRECTORY_SEPARATOR, $dateHashPath );
        $cacheTree = implode( DIRECTORY_SEPARATOR, $fileStorage->composeCachePath( $hash ) );

        if ( AbstractFilesStorage::isOnS3() ) {
            $lang = ($type == "source") ? $this->job->source : $this->job->target;
            $json_storage = new S3AlignerFilesStorage();
            $segments = $json_storage->getJsonCachePackage($cacheTree, $lang, $idFile);
        } else {

            //destination dir
            $fileDir  = \INIT::$FILES_REPOSITORY. DIRECTORY_SEPARATOR . $datePath . DIRECTORY_SEPARATOR .
                $idFile . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . $cacheTree . "|" . $type ;

            $json_path = $fileDir . DIRECTORY_SEPARATOR . $newFileName . '.json';
            $segments = file_get_contents($json_path);
        }

        if($segments === false){
            throw new \Exception("Error: no json file found");
        }

        $segments = json_decode($segments, true);

        return $segments;

    }

}