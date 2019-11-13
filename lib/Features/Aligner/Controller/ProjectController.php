<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 01/08/2018
 * Time: 17:50
 */

namespace Features\Aligner\Controller;
include_once \INIT::$UTILS_ROOT . "/xliff.parser.1.3.class.php";

use Exceptions\ValidationError;
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
use Features\Aligner\Utils\ProjectProgress;
use FilesStorage\AbstractFilesStorage;

class ProjectController extends AlignerController {

    use ProjectProgress;

    protected $postInput;

    protected $project;
    protected $job;

    protected $uploadDir;
    protected $fileSourcePath;
    protected $fileTargetPath;

    public $result;

    public function __construct( $request, $response, $service, $app ) {
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
                        'flags'  => FILTER_FLAG_STRIP_LOW
                ],
                'file_name_source' => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW
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

        //$source_filename = AlignUtils::getLatestVersionFileName( $this->postInput[ 'file_name_source' ], $this->uploadDir );
        $source_filename = $this->postInput[ 'file_name_source' ];

        $this->fileSourcePath = $this->uploadDir . "/" . $source_filename;
        if ( !file_exists( $this->fileSourcePath ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing file source." ];
        }

        //$target_filename = AlignUtils::getLatestVersionFileName( $this->postInput[ 'file_name_target' ], $this->uploadDir );
        $target_filename = $this->postInput[ 'file_name_target' ];

        $this->fileTargetPath = $this->uploadDir . "/" . $target_filename;
        if ( !file_exists( $this->fileTargetPath ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing file target." ];
        }

        parent::__construct( $request, $response, $service, $app );
    }

    public function create() {
        if ( count( @$this->result[ 'errors' ] ) ) {
            throw new ValidationError( $this->result[ 'errors' ][ 0 ][ 'message' ] );
        }

        $default_project_name = AlignUtils::removeVersionFromFileName($this->postInput[ 'file_name_source' ]);//"ALIGNER-" . date( 'Y-m-d H:i:s' );
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
        $jobStruct->create_date = date( 'Y-m-d H:i:s' );

        $this->job = Jobs_JobDao::createFromStruct( $jobStruct );

        $sha1_source_file = sha1_file( $this->fileSourcePath );
        $source_file = $this->_insertFile( $this->postInput[ 'file_name_source' ], $sha1_source_file, "source" );

        $sha1_target_file = sha1_file( $this->fileTargetPath );
        $target_file      = $this->_insertFile( $this->postInput[ 'file_name_target' ], $sha1_target_file, "target" );

        $params = [
                'id_job'      => $this->job->id,
                'job'         => $this->job,
                'project'     => $this->project,
                'source_file' => $source_file,
                'target_file' => $target_file,
                'upload_session' => $_COOKIE['upload_session']
        ];

        $this->pushProjectInQueue($this->project->id, 'ALIGNER_SEGMENT_CREATE');

        try {
            \WorkerClient::init( new \AMQHandler() );
            \WorkerClient::enqueue( 'ALIGNER_SEGMENT_CREATE', 'Features\Aligner\Utils\AsyncTasks\Workers\SegmentWorker', json_encode( $params ), [
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

        $this->result = [
                'project' => $this->project,
                'job'     => $this->job
        ];

        $this->response->json( $this->result );
    }

    protected function _insertFile( $filename, $sha1, $type ) {

        $yearMonthPath    = date_create( $this->project->create_date )->format( 'Ymd' );
        $fileDateSha1Path = $yearMonthPath . DIRECTORY_SEPARATOR . $sha1;

        $extension = AbstractFilesStorage::pathinfo_fix( $filename, PATHINFO_EXTENSION );

        $fileStruct = new Files_FileStruct();

        $fileStruct->id_project         = $this->project->id;
        $fileStruct->id_job             = $this->job->id;
        $fileStruct->filename           = $filename;
        $fileStruct->type               = $type;
        $fileStruct->extension          = $extension;
        $fileStruct->sha1_original_file = $fileDateSha1Path;

        $file = Files_FileDao::createFromStruct( $fileStruct );

        return $file;

    }



}