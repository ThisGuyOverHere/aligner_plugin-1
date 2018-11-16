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

        route( '/job/[:id_job]/[:password]/information', 'GET', 'Features\Aligner\Controller\JobController', 'information' );

        route( '/segments/[:id_job]/[:password]', 'GET', 'Features\Aligner\Controller\SegmentsController', 'get' );

        route( '/job/[:id_job]/[:password]/check_progress', 'GET', 'Features\Aligner\Controller\ApiController', 'checkProgress' );

        route( '/job/[:id_job]/[:password]/segment/split', 'POST', 'Features\Aligner\Controller\ApiController', 'split' );
        route( '/job/[:id_job]/[:password]/segment/merge', 'POST', 'Features\Aligner\Controller\ApiController', 'merge' );
        route( '/job/[:id_job]/[:password]/segment/gap', 'POST', 'Features\Aligner\Controller\ApiController', 'addGap' );
        route( '/job/[:id_job]/[:password]/segment/move', 'POST', 'Features\Aligner\Controller\ApiController', 'move' );
        route( '/job/[:id_job]/[:password]/segment/delete', 'POST', 'Features\Aligner\Controller\ApiController', 'delete' );
        route( '/job/[:id_job]/[:password]/segment/switch', 'POST', 'Features\Aligner\Controller\ApiController', 'switchAction' );
        route( '/job/[:id_job]/[:password]/segment/merge_align', 'POST', 'Features\Aligner\Controller\ApiController', 'mergeAndAlign' );

        route( '/configs', 'GET', 'Features\Aligner\Controller\ApiController', 'getConfig' );

        route( '/tm/mine', 'GET', 'Features\Aligner\Controller\TMXController', 'getUserTM' );
        route( '/tm/create_key', 'POST', 'Features\Aligner\Controller\TMXController', 'createTmKey' );
        route( '/tm/[:key]/save', 'POST', 'Features\Aligner\Controller\TMXController', 'saveTm' );
        route( '/tm/[:key]/update', 'POST', 'Features\Aligner\Controller\TMXController', 'updateTm' );
        route( '/tm/[:key]/check_upload', 'POST', 'Features\Aligner\Controller\TMXController', 'checkStatusUploadTMX' );

        route( '/job/[:id_job]/[:password]/push_tmx', 'POST', 'Features\Aligner\Controller\TMXController', 'pushTMXInTM' );
        route( '/job/[:id_job]/[:password]/tm/[:key]/push_tmx', 'POST', 'Features\Aligner\Controller\TMXController', 'pushTMXInTM' );

        $klein->respond( 'GET', '/index', [ __CLASS__, 'homeRoute' ] );
    }

    public static function homeRoute( $request, $response, $service, $app ) {
        $controller    = new HomeController( $request, $response, $service, $app );
        $template_path = dirname( __FILE__ ) . '/Aligner/View/Html/index.html';
        $controller->setView( $template_path );
        $controller->respond( 'composeView' );
    }


}