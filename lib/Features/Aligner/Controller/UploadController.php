<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 16/07/2018
 * Time: 13:01
 */

namespace Features\Aligner\Controller;

use Features\Aligner\Utils\AlignUtils;

include_once \INIT::$UTILS_ROOT . "/fileupload/upload.class.php";


class UploadController extends AlignerController {

    protected $file_name;
    protected $source_lang;
    protected $target_lang;
    protected $segmentation_rule;

    protected $guid;

    public $result;

    public function __construct( $request, $response, $service, $app ) {
        $this->setOrGetGuid();
        if ( !isset( $_COOKIE[ 'upload_session' ] ) && empty( $_COOKIE[ 'upload_session' ] ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Cookie session is not set" ];
        }
        parent::__construct( $request, $response, $service, $app );
    }

    public function convert() {

        if ( @count( $this->result[ 'errors' ] ) ) {
            //$this->response->json( $this->result );
            //return;
            throw new \Exception( $this->result[ 'errors' ][ 0 ][ 'message' ] );
        }

        $filterArgs = [
                'file_name'         => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW
                ],
                'source_lang'       => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'target_lang'       => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ],
                'segmentation_rule' => [
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ]
        ];

        $postInput = filter_input_array( INPUT_POST, $filterArgs );

        $this->file_name         = AlignUtils::encode_filename( $postInput[ 'file_name' ] );
        $this->source_lang       = $postInput[ "source_lang" ];
        $this->target_lang       = $postInput[ "target_lang" ];
        $this->segmentation_rule = $postInput[ "segmentation_rule" ];

        if ( $this->segmentation_rule == "" ) {
            $this->segmentation_rule = null;
        }

        $cookieDir = $_COOKIE[ 'upload_session' ];
        $intDir    = \INIT::$UPLOAD_REPOSITORY . DIRECTORY_SEPARATOR . $cookieDir;
        $errDir    = \INIT::$STORAGE_DIR . DIRECTORY_SEPARATOR . 'conversion_errors' . DIRECTORY_SEPARATOR . $cookieDir;

        $conversionHandler = new \ConversionHandler();
        $conversionHandler->setFileName( $this->file_name );
        $conversionHandler->setSourceLang( $this->source_lang );
        $conversionHandler->setTargetLang( $this->target_lang );
        $conversionHandler->setSegmentationRule( $this->segmentation_rule );
        $conversionHandler->setCookieDir( $cookieDir );
        $conversionHandler->setIntDir( $intDir );
        $conversionHandler->setErrDir( $errDir );
        $conversionHandler->setFeatures( $this->featureSet );
        $user = $this->getUser();
        if ( $user ) {
            $userIsLogged = true;
        } else {
            $userIsLogged = false;
        }
        $conversionHandler->setUserIsLogged( $userIsLogged );

        $conversionHandler->doAction();

        $this->result = $conversionHandler->getResult();
        if ( @count( $this->result[ 'errors' ] ) ) {
            throw new \Exception( $this->result[ 'errors' ][ 0 ][ 'message' ] );
        }
        $this->response->json( $this->result );
    }

    public function upload() {
        if ( @count( $this->result[ 'errors' ] ) ) {
            throw new \Exception( $this->result[ 'errors' ][ 0 ][ 'message' ] );
        }
        $uploadFile = new \Upload( $_COOKIE[ 'upload_session' ] );
        setlocale(LC_ALL, "en_US.utf8");
        $matches = [];
        foreach($_FILES as $key => $file){
            $_FILES[$key]['name'] = AlignUtils::encode_filename($_FILES[$key]['name']);
        }

        try {
            $this->result = $uploadFile->uploadFiles( $_FILES );
            foreach ( $this->result as $key => $value ) {
                unset( $this->result->$key->file_path );
            }
        } catch ( \Exception $e ) {
            throw new \Exception( $e->getMessage() );
        }

        $this->result->files->name = AlignUtils::removeVersionFromFileName($this->result->files->name);
        $this->result->files->name = AlignUtils::decode_filename($this->result->files->name );

        $this->result = array_values((array)$this->result);
        if ( @count( $this->result[ 'errors' ] ) ) {
            throw new \Exception( $this->result[ 'errors' ][ 0 ][ 'message' ] );
        }

        $this->response->json( $this->result );
    }

    public function delete(){

        $file = $this->params[ 'file' ];
        $type = $this->params[ 'type' ];
        $size = $this->params[ 'size' ];

        if( empty($file) || empty($type) || empty($size) ){
            throw new \Exception( "Invalid request object" );
        }

        $uploadSession = $_COOKIE['upload_session'];
        $upload = new \Upload($uploadSession);

        $path = $upload->getUploadPath();
        $file = $this->_sanitizeFileName($file);

        $filePath = $path . DIRECTORY_SEPARATOR . $file;

        $filetype = mime_content_type($filePath);
        $filesize = filesize($filePath);

        if (($filetype === false || $filesize === false) ||
            ($filetype !== $type || $filesize !== $size)){
            throw new \Exception( "Invalid request object" );
        }

        @unlink($filePath);

        return $this->response->json( [ "success" => true ] );
    }

    protected function _sanitizeFileName( $filename ){

        $upload  = new \Upload();

        $filename = str_replace('/','',$filename);

        try{
            $filename = $upload->fixFileName( $filename );
        } catch ( \Exception $e ) {
            throw new \Exception( $e->getMessage() );
        }

        $contains_separator = strpos('/', $filename) !== false ||
        strpos('&#47;', $filename) !== false ||
        strpos('%2F', $filename) !== false;

        if($contains_separator){
            throw new \Exception('Invalid file input');
        }

        return $filename;

    }

    /*public function upload(){

        if ( @count( $this->result[ 'errors' ] ) ) {
            //$this->response->json( $this->result );
            //return;
            throw new \Exception($this->result['errors'][0]['message']);

        }


        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Disposition: inline; filename="files.json"');
        header('X-Content-Type-Options: nosniff');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');
        $this->setOrGetGuid();
        $this->initUploadDir();


        $upload_handler  = new \UploadHandler();
        $upload_response = $upload_handler->post( true );
        if ( $upload_response[ 0 ]->error ) {
            throw new \Exception( $upload_response[ 0 ]->error );
        } else {
            return $this->response->json( $upload_response );
        }
    }*/

    private function setOrGetGuid() {
        // Get the guid from the guid if it exists, otherwise set the guid into the cookie
        if ( !isset( $_COOKIE[ 'upload_session' ] ) ) {
            $guid = \Utils::createToken();
            setcookie( "upload_session", $guid, time() + 86400, '/' );
            $_COOKIE['upload_session'] = $guid;
        } else {
            $guid = $_COOKIE[ 'upload_session' ];
        }
        $this->guid = $guid;
        return $guid;

    }

    private function initUploadDir(){
        $intDir = \INIT::$UPLOAD_REPOSITORY . '/' . $this->guid . '/';
        if ( !is_dir( $intDir ) ) {
            mkdir( $intDir, 0775, true );
        }
    }

}