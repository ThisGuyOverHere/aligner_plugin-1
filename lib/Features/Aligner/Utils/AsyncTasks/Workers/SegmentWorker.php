<?php
/**
 * Created by PhpStorm.
 * User: matteopulcrano
 * Date: 28/03/2019
 * Time: 16:41
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
use Features\Aligner\Model\Exceptions\FileWordLimit;
use FilesStorage\AbstractFilesStorage;
use FilesStorage\FilesStorageFactory;
use FilesStorage\S3FilesStorage;
use Features\Aligner\Utils\FilesStorage\S3AlignerFilesStorage;

class SegmentWorker extends AbstractWorker {
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
        $this->_createSegments( $queueElement );
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

    protected function _createSegments( \TaskRunner\Commons\QueueElement $queueElement ) {
        $attributes = json_decode( $queueElement->params );

        $this->id_job = $attributes->id_job;
        $this->job    = $attributes->job;
        $this->project = $attributes->project;
        $this->popProjectInQueue($this->project->id, 'ALIGNER_SEGMENT_CREATE');


        \Log::doLog('/-------/ START to extract segments for JOB #'.$this->id_job.' /--------/');


        $source_file  = $attributes->source_file;
        $target_file  = $attributes->target_file;

        $source_lang = $this->job->source;
        $target_lang = $this->job->target;

        $fileStorage = FilesStorageFactory::create();

        try {
            \Log::doLog('/-------/ Convert xliff file to array for source /--------/');
            $source_segments = $this->_file2segments($source_file, $source_lang);

            \Log::doLog('/-------/ Convert xliff file to array for target /--------/');
            $target_segments = $this->_file2segments($target_file, $target_lang);

            \Log::doLog('/-------/ Converted xliff files to array  /--------/');

            $segment_count = count($source_segments) + count($target_segments);

            $this->updateSegmentsCount($this->project->id, $segment_count);

            $config = Aligner::getConfig();

            if($segment_count < $config['LOW_LIMIT_QUEUE_SIZE']){
                $attributes->queue = 'align_job_small_list';
                $queue = "ALIGNER_ALIGN_JOB_SMALL";
            } else if ($segment_count < $config['HIGH_LIMIT_QUEUE_SIZE']){
                $attributes->queue = 'align_job_medium_list';
                $queue = "ALIGNER_ALIGN_JOB_MEDIUM";
            } else {
                $attributes->queue = 'align_job_big_list';
                $queue = "ALIGNER_ALIGN_JOB_BIG";
            }

            $this->pushProjectInQueue($this->project->id, $attributes->queue);

            if(empty($source_segments) || empty($target_segments)){
                throw new \Exception( "Empty source or target segments" );
            }

            $source_segments = $this->_storeSegments($source_segments, "source");
            $target_segments = $this->_storeSegments($target_segments, "target");



            $fileStorage->moveFromCacheToFileDir(
                    $source_file->sha1_original_file,
                    $this->job->source,
                    $source_file->id,
                    $source_file->filename
            );

            $fileStorage->moveFromCacheToFileDir(
                    $target_file->sha1_original_file,
                    $this->job->target,
                    $target_file->id,
                    $target_file->filename
            );

            $this->_saveSegmentsAsJson($source_file->sha1_original_file, $source_segments, 'source', $source_file->id, $this->project->name);
            $this->_saveSegmentsAsJson($target_file->sha1_original_file, $target_segments, 'target', $target_file->id, $this->project->name);

            $this->sendInQueue($attributes, $queue);

        }catch (\Exception $e){
            \Log::doLog($e->getMessage());
            if($e instanceof FileWordLimit){
                Projects_ProjectDao::updateField( $this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_8);
            }
            else {
                Projects_ProjectDao::updateField($this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_9);
            }

            $this->updateProgress($this->project->id, 0);
        }

        \Log::doLog('/-------/ FINISH to extract segments for JOB #'.$this->id_job.' /--------/');


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
            $fileStorage = FilesStorageFactory::create();
            $xliff_file = $fileStorage->getXliffFromCache($sha1, $lang);
            \Log::doLog('Found xliff file ['.$xliff_file.']');
            $xliff_content = $this->getFileContent($xliff_file);
        } catch ( \Exception $e ) {
            throw new \Exception( "File xliff not found", $e->getCode(), $e );
        }

        // Parse xliff
        try {
            $parser = new \Xliff_Parser;
            $xliff = $parser->Xliff2Array($xliff_content);
            \Log::doLog('Parsed xliff file ['.$xliff_file.']');
        } catch ( \Exception $e ) {
            throw new \Exception( "Error during xliff parsing", $e->getCode(), $e );
        }

        // Checking that parsing went well
        if ( isset( $xliff[ 'parser-errors' ] ) or !isset( $xliff[ 'files' ] ) ) {
            throw new \Exception( "Parsing errors: ".json_encode($xliff[ 'parser-errors' ]), -4 );
        }

        // Creating the Segments
        $segments = [];
        $total_words = 0;

        foreach ( $xliff[ 'files' ] as $xliff_file ) {

            // An xliff can contains multiple files (docx has style, settings, ...) but only some with useful trans-units
            if ( !array_key_exists( 'trans-units', $xliff_file ) ) {
                continue;
            }

            foreach ($xliff_file[ 'trans-units' ] as $trans_unit) {

                // Extract only raw-content

                if(isset($trans_unit['seg-source'])){
                    $unit_items = array_map(function ($item) {
                        return $item['raw-content'];
                    }, $trans_unit[ 'seg-source' ]);
                } else {
                    $unit_items = [$trans_unit['source']['raw-content']];
                }

                // Build an object with raw-content and clean-content
                $unit_segments = [];
                foreach ($unit_items as $item) {
                    $raw_word_count = \CatUtils::segment_raw_word_count($item, $lang);
                    if ( $raw_word_count <= 0 ) {
                        continue;
                    }

                    $unit_segment = [
                        'content_clean' => AlignUtils::_cleanSegment($item, $lang),
                        'raw_word_count' => $raw_word_count
                    ];

                    if($lang == "hi-IN"){
                        // !!! Temporary workaround for the Mr. Mrs. and Dr. abbreviation !!!
                        if(mb_strlen($unit_segment['content_clean']) <= 4 && mb_substr($unit_segment['content_clean'], -1) == "."){
                            $abbr = (isset($abbr)) ? $abbr." ".$unit_segment['content_clean'] : $unit_segment['content_clean'];
                            $total_words += $unit_segment['raw_word_count'];
                            continue;
                        }

                        if(!empty($abbr)){
                            $unit_segment['content_clean'] = $abbr." ".$unit_segment['content_clean'];
                            $abbr = null;
                        }
                    }

                    $total_words += $unit_segment['raw_word_count'];
                    $unit_segments[] = $unit_segment;
                }

                // Append to existing Segments
                $segments = array_merge($segments, $unit_segments);
            }
        }

        $config = Aligner::getConfig();

        if ($total_words > $config["MAX_WORDS_PER_FILE"]){
            throw new FileWordLimit("File exceeded the word limit, job creation canceled");
        }

        return $segments;
    }

    /**
     * @param $file_content
     *
     * @return false|string
     * @throws Exception
     */
    private function getFileContent($file_content ) {
        if ( AbstractFilesStorage::isOnS3() ) {
            $s3Client = S3FilesStorage::getStaticS3Client();

            if ( $s3Client->hasEncoder() ) {
                $file_content = $s3Client->getEncoder()->decode( $file_content );
            }

            return $s3Client->openItem( [ 'bucket' => S3FilesStorage::getFilesStorageBucket(), 'key' => $file_content ] );
        }

        return file_get_contents( $file_content );
    }


    private function _storeSegments($segments, $type){

        $sequenceIds = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count( $segments ) );
        foreach($sequenceIds as $key => $sequenceId){
            $segments[$key]['id'] = $sequenceId;
            $segments[$key]['type'] = $type;
            $segments[$key]['id_job'] = $this->job->id;
        }

        $segmentsDao = new Segments_SegmentDao;
        $segmentsDao->createList( $segments );

        return $segments;

    }

    private function _saveSegmentsAsJson( $dateHashPath, $segments ,$type, $idFile, $newFileName ){

        $json_segments = json_encode($segments);

        $fileStorage = FilesStorageFactory::create();

        list( $datePath, $hash ) = explode( DIRECTORY_SEPARATOR, $dateHashPath );
        $cacheTree = implode( DIRECTORY_SEPARATOR, $fileStorage->composeCachePath( $hash ) );

        //destination dir
        $fileDir  = \INIT::$FILES_REPOSITORY. DIRECTORY_SEPARATOR . $datePath . DIRECTORY_SEPARATOR .
            $idFile . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . $cacheTree . "|" . $type ;

        $json_path = $fileDir . DIRECTORY_SEPARATOR . $newFileName . '.json';

        \Log::doLog( $fileDir );

        $res = true;

        if ( !is_dir( $fileDir ) ) {
            $res &= mkdir( $fileDir, 0755, true );
        }

        if (!$res){
            $message = "Couldn't create directory, process halted";
            \Log::doLog($message);
            throw new \Exception($message);
        }

        $lang = ($type == "source") ? $this->job->source : $this->job->target;

        file_put_contents($json_path, $json_segments);
        if ( AbstractFilesStorage::isOnS3() ) {
            $json_storage = new S3AlignerFilesStorage();
            $json_storage->makeJsonCachePackage($cacheTree, $lang, $json_path, $newFileName, $idFile);
        }
        return;

    }

    private function sendInQueue($attributes, $queue){
        \WorkerClient::init( new \AMQHandler() );
        \WorkerClient::enqueue( $queue, 'Features\Aligner\Utils\AsyncTasks\Workers\AlignJobWorker', json_encode( $attributes ), [
                'persistent' => \WorkerClient::$_HANDLER->persistent
        ] );
    }

}