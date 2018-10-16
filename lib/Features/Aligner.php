<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 25/06/18
 * Time: 12:43
 */

namespace Features;

use Klein\Klein;
use BasicFeatureStruct;
use Features\Aligner\Controller\HomeController;

class Aligner extends BaseFeature
{
    const FEATURE_CODE = "aligner";

    protected $autoActivateOnProject = false;

    public function __construct( BasicFeatureStruct $feature ) {
        parent::__construct( $feature );
    }

    public static function loadRoutes( Klein $klein ) {
        route( '/upload', 'POST', 'Features\Aligner\Controller\UploadController', 'upload' );
        route( '/xliff_conversion', 'POST', 'Features\Aligner\Controller\UploadController', 'convert' );
        route( '/create_project', 'POST', 'Features\Aligner\Controller\CreateProjectController', 'create' );
        route( '/parse/[:id_job]', 'GET', 'Features\Aligner\Controller\ParserController', 'jobParser' );
        route( '/job/[:id_job]/[:password]/information', 'GET', 'Features\Aligner\Controller\JobController', 'information' );
        route( '/segments/[:id_job]', 'GET', 'Features\Aligner\Controller\SegmentsController', 'get' );
        route( '/job/[:id_job]/[:password]/segment/split', 'POST', 'Features\Aligner\Controller\ApiController', 'split' );
        route( '/job/[:id_job]/[:password]/segment/merge', 'POST', 'Features\Aligner\Controller\ApiController', 'merge' );
        route( '/job/[:id_job]/[:password]/segment/gap', 'POST', 'Features\Aligner\Controller\ApiController', 'addGap' );
        route( '/job/[:id_job]/[:password]/segment/move', 'POST', 'Features\Aligner\Controller\ApiController', 'move' );
        route( '/job/[:id_job]/[:password]/segment/delete', 'POST', 'Features\Aligner\Controller\ApiController', 'delete' );
        route( '/job/[:id_job]/[:password]/segment/reverse', 'POST', 'Features\Aligner\Controller\ApiController', 'reverse' );

        route( '/tmx/mine', 'GET', 'Features\Aligner\Controller\TMXController', 'getUserTM' );
        route( '/tmx/create_key', 'POST', 'Features\Aligner\Controller\TMXController', 'createTmKey' );
        route( '/tmx/save', 'POST', 'Features\Aligner\Controller\TMXController', 'saveTm' );
        route( '/tmx/upload', 'POST', 'Features\Aligner\Controller\TMXController', 'uploadTMX' );
        route( '/tmx/check_upload', 'POST', 'Features\Aligner\Controller\TMXController', 'checkStatusUploadTMX' );
        
        $klein->respond( 'GET', '/index', [ __CLASS__, 'homeRoute' ] );
    }

    public static function homeRoute( $request, $response, $service, $app ) {
        $controller    = new HomeController( $request, $response, $service, $app );
        $template_path = dirname( __FILE__ ) . '/Aligner/View/Html/index.html';
        $controller->setView( $template_path );
        $controller->respond( 'composeView' );
    }


}