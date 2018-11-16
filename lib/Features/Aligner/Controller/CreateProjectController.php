<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 17:50
 */

namespace Features\Aligner\Controller;
include_once \INIT::$UTILS_ROOT . "/xliff.parser.1.3.class.php";

use Features\Aligner\Model\Files_FileDao;
use Features\Aligner\Model\Files_FileStruct;
use Features\Aligner\Model\Projects_ProjectDao;
use Features\Aligner\Model\Projects_ProjectStruct;
use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Model\Jobs_JobStruct;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentDao;
use CatUtils;
use Features\Aligner\Utils\AlignUtils;

class CreateProjectController extends AlignerController {

    protected $postInput;

    protected $project;
    protected $job;

    protected $uploadDir;
    protected $fileSourcePath;
    protected $fileTargetPath;

    public $result;

    public function __construct( $request, $response, $service, $app ) {
        ini_set('max_execution_time', 2000);
        $filterArgs = [
                'project_name'     => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW
                ],
                'source_lang'      => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'target_lang'      => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'file_name_target' => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'file_name_source' => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ]
        ];

        $this->postInput = filter_input_array( INPUT_POST, $filterArgs );

        if ( empty( $this->postInput[ 'source_lang' ] ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing source." ];
        }

        if ( empty( $this->postInput[ 'target_lang' ] ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing target." ];
        }

        if ( empty( $this->postInput[ 'file_name_source' ] ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing file name source." ];
        }

        if ( empty( $this->postInput[ 'file_name_target' ] ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing file name target." ];
        }

        if ( !isset( $_COOKIE[ 'upload_session' ] ) && empty( $_COOKIE[ 'upload_session' ] ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Cookie session is not set" ];
        }

        $this->uploadDir = \INIT::$UPLOAD_REPOSITORY . DIRECTORY_SEPARATOR . $_COOKIE[ 'upload_session' ];

        $this->fileSourcePath = $this->uploadDir . "/" . $this->postInput[ 'file_name_source' ];
        if ( !file_exists( $this->fileSourcePath ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing file source." ];
        }

        $this->fileTargetPath = $this->uploadDir . "/" . $this->postInput[ 'file_name_target' ];
        if ( !file_exists( $this->fileTargetPath ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing file target." ];
        }

        parent::__construct( $request, $response, $service, $app );
    }

    public function create() {

        if ( count( @$this->result[ 'errors' ] ) ) {
            return $this->response->json( $this->result );
        }

        $default_project_name = "ALIGNER-" . date( 'Y-m-d H:i:s' );
        $projectStruct        = new Projects_ProjectStruct();

        $user = $this->getUser();
        if ( !empty( $user ) ) {
            $projectStruct->id_customer = $user->uid;
        } else {
            $projectStruct->id_customer = null;
        }

        if ( empty( $this->postInput[ 'project_name' ] ) ) {
            $projectStruct->name = $default_project_name;
        } else {
            $projectStruct->name = $this->postInput[ 'project_name' ];
        }
        $projectStruct->password = CatUtils::generate_password( 12 );

        $projectStruct->create_date       = date( 'Y-m-d H:i:s' );
        $projectStruct->remote_ip_address = \Utils::getRealIpAddr();
        $this->project                    = Projects_ProjectDao::createFromStruct( $projectStruct );

        $jobStruct = new Jobs_JobStruct();

        $jobStruct->password   = CatUtils::generate_password( 12 );
        $jobStruct->source     = $this->postInput[ 'source_lang' ];
        $jobStruct->target     = $this->postInput[ 'target_lang' ];
        $jobStruct->id_project = $this->project->id;

        $this->job = Jobs_JobDao::createFromStruct( $jobStruct );

        $sha1_source_file = sha1_file( $this->fileSourcePath );
        $source_file = $this->_insertFile( $this->postInput[ 'file_name_source' ], $sha1_source_file, $this->postInput[ 'source_lang' ], "source" );

        $sha1_target_file = sha1_file( $this->fileTargetPath );
        $target_file      = $this->_insertFile( $this->postInput[ 'file_name_target' ], $sha1_target_file, $this->postInput[ 'target_lang' ], "target" );

        $params = [
                'id_job'      => $this->job->id,
                'job'         => $this->job,
                'project'     => $this->project,
                'source_file' => $source_file,
                'target_file' => $target_file
        ];

        try {
            \WorkerClient::init( new \AMQHandler() );
            \WorkerClient::enqueue( 'ALIGNER_ALIGN_JOB', 'Features\Aligner\Utils\AsyncTasks\Workers\AlignJobWorker', json_encode( $params ), [
                    'persistent' => \WorkerClient::$_HANDLER->persistent
            ] );
        } catch ( \Exception $e ) {

            # Handle the error, logging, ...
            $output = "**** Align Job Enqueue failed. AMQ Connection Error. ****\n\t";
            $output .= "{$e->getMessage()}";
            $output .= var_export( $params, true );
            \Log::doLog( $output );
            throw $e;

        }

        /*$source_file  = Files_FileDao::getByJobId( $this->job->id, "source" );
        $target_file  = Files_FileDao::getByJobId( $this->job->id, "target" );

        $source_segments = $this->_file2segments($source_file, $this->postInput[ 'source_lang' ]);
        $target_segments = $this->_file2segments($target_file, $this->postInput[ 'target_lang' ]);

        $this->_storeSegments($source_segments, "source", $this->postInput[ 'source_lang' ]);
        $this->_storeSegments($target_segments, "target", $this->postInput[ 'target_lang' ]);*/

        $this->result = [
                'project' => $this->project,
                'job'     => $this->job
        ];

        $this->response->json( $this->result );
    }

    protected function _insertFile( $filename, $sha1, $language, $type ) {

        $yearMonthPath    = date_create( $this->project->create_date )->format( 'Ymd' );
        $fileDateSha1Path = $yearMonthPath . DIRECTORY_SEPARATOR . $sha1;

        $mimeType = \FilesStorage::pathinfo_fix( $filename, PATHINFO_EXTENSION );

        $fileStruct = new Files_FileStruct();

        $fileStruct->id_project         = $this->project->id;
        $fileStruct->id_job             = $this->job->id;
        $fileStruct->filename           = $filename;
        $fileStruct->type               = $type;
        $fileStruct->language_code      = $language;
        $fileStruct->mime_type          = $mimeType;
        $fileStruct->sha1_original_file = $fileDateSha1Path;

        $file = Files_FileDao::createFromStruct( $fileStruct );

        return $file;

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
        } catch ( \Exception $e ) {
            throw new \Exception( $file, $e->getCode(), $e );
        }

        // Parse xliff
        try {
            $parser = new \Xliff_Parser;
            $xliff = $parser->Xliff2Array($xliff_content);
        } catch ( \Exception $e ) {
            throw new \Exception( $file, $e->getCode(), $e );
        }

        // Checking that parsing went well
        if ( isset( $xliff[ 'parser-errors' ] ) or !isset( $xliff[ 'files' ] ) ) {
            throw new \Exception( $file, -4 );
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
                            'content_clean' => AlignUtils::_cleanSegment($item, $lang),
                            'raw_word_count' => AlignUtils::_countWordsInSegment($item, $lang)
                    ];
                }, $unit_segments);

                // Append to existing Segments
                $segments = array_merge($segments, $unit_segments);
            }
        }

        return $segments;
    }


    private function _storeSegments($segments, $type, $lang){

        $sequenceIds = $this->dbHandler->nextSequence( NewDatabase::SEQ_ID_SEGMENT, count( $segments ) );
        foreach($sequenceIds as $key => $sequenceId){
            $segments[$key]['id'] = $sequenceId;
            $segments[$key]['type'] = $type;
            $segments[$key]['language_code'] = $lang;
            $segments[$key]['id_job'] = $this->job->id;
            $segments[$key]['content_hash'] = md5($segments[$key]['content_raw']);
        }

        $segmentsDao = new Segments_SegmentDao;
        $segmentsDao->createList( $segments );

    }
}