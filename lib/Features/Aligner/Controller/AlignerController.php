<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 02/08/2018
 * Time: 14:54
 */

namespace Features\Aligner\Controller;

use API\V2\KleinController;
use Features\Aligner;
use Features\Aligner\Model\NewDatabase;

class AlignerController extends KleinController {
    public $dbHandler;

    public function __construct( $request, $response, $service, $app ) {
        $config = Aligner::getConfig();
        $this->dbHandler = NewDatabase::obtain($config['DB_SERVER'], $config['DB_USER'], $config['DB_PASS'], $config['DB_DATABASE']);
        $this->dbHandler->getConnection();
        parent::__construct( $request, $response, $service, $app );
    }
}