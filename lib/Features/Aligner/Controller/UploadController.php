<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 16/07/2018
 * Time: 13:01
 */

namespace Features\Aligner\Controller;
use API\V2\KleinController;
use Features\Aligner\Model\Projects_ProjectDao;
use Features\Aligner\Model\Projects_ProjectStruct;

class UploadController extends  KleinController {

    private $guid = '';

    protected $file_name;
    protected $source_lang;
    protected $target_lang;
    protected $segmentation_rule;

    public function convert(){
        $filterArgs = array(
                'file_name'         => array(
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW
                ),
                'source_lang'       => array(
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ),
                'target_lang'       => array(
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                ),
                'segmentation_rule' => array(
                        'filter' => FILTER_SANITIZE_STRING,
                        'flags'  => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
                )
        );

        $postInput = filter_input_array( INPUT_POST, $filterArgs );

        $this->file_name         = $postInput[ 'file_name' ];
        $this->source_lang       = $postInput[ "source_lang" ];
        $this->target_lang       = $postInput[ "target_lang" ];
        $this->segmentation_rule = $postInput[ "segmentation_rule" ];

        if ( $this->segmentation_rule == "" ) {
            $this->segmentation_rule = null;
        }

        $this->setOrGetGuid();
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
        $conversionHandler->setUserIsLogged( $this->userIsLogged );

        $conversionHandler->doAction();

        $result = $conversionHandler->getResult();
        $this->response->json( $result );
    }

    public function createProject(){

        if ( empty( $_POST['project_name'] ) ) {
            $this->result[ 'errors' ][] = [ "code" => -1, "message" => "Missing file name." ];
        }

        $default_project_name = $_POST[ 'file_name' ];
        $projectStruct = new Projects_ProjectStruct();

        $user = $this->getUser();
        if(!empty($user)){
            $projectStruct->id_customer = $user->uid;
        } else {
            $projectStruct->id_customer = null;
        }

        if ( empty( $_POST['project_name'] ) ) {
            $projectStruct->name = $default_project_name;
        } else {
            $projectStruct->name = $_POST['project_name'];
        }
        $projectStruct->password = \CatUtils::generate_password( 12 );

        $projectStruct->create_date = date('Y-m-d H:i:s');
        $projectStruct->remote_ip_address = \Utils::getRealIpAddr();
        $project = Projects_ProjectDao::createFromStruct($projectStruct);
        sleep(1);
    }

    private function setOrGetGuid() {
        // Get the guid from the guid if it exists, otherwise set the guid into the cookie
        if ( !isset( $_COOKIE[ 'upload_session' ] ) ) {
            $this->guid = \Utils::create_guid();
            setcookie( "upload_session", $this->guid, time() + 86400, '/' );
        } else {
            $this->guid = $_COOKIE[ 'upload_session' ];
        }

    }

}