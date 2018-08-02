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
use Features\Aligner\Model\NewDatabase;

class Aligner extends BaseFeature
{
    const FEATURE_CODE = "aligner";

    protected $autoActivateOnProject = false;

    public function __construct( BasicFeatureStruct $feature ) {
        //$config = self::getConfig();
        //$conn = \NewDatabase::obtain($config['DB_SERVER'], $config['DB_USER'], $config['DB_PASS'], $config['DB_DATABASE']);
        parent::__construct( $feature );
    }

    public static function loadRoutes( Klein $klein ) {
        $config = self::getConfig();
        NewDatabase::obtain($config['DB_SERVER'], $config['DB_USER'], $config['DB_PASS'], $config['DB_DATABASE'])->getConnection();
        route( '/xliff_conversion', 'POST', 'Features\Aligner\Controller\UploadController', 'convert' );
        route( '/create_project', 'POST', 'Features\Aligner\Controller\CreateProjectController', 'create' );

        $klein->respond( 'GET', '/index', [ __CLASS__, 'homeRoute' ] );
    }

    public static function homeRoute( $request, $response, $service, $app ) {
        $controller    = new HomeController( $request, $response, $service, $app );
        $template_path = dirname( __FILE__ ) . '/Aligner/View/Html/index.html';
        $controller->setView( $template_path );
        $controller->respond( 'composeView' );
    }


}