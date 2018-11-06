<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 05/11/2018
 * Time: 12:28
 */

namespace Features\Aligner\Utils\TaskRunner\Commons;

use Features\Aligner;
use Features\Aligner\Model\NewDatabase;
use \PDOException;

/**
 * Class AbstractWorker
 */
abstract class AbstractWorker extends \TaskRunner\Commons\AbstractWorker {


    protected function _checkDatabaseConnection(){

        parent::_checkDatabaseConnection();

        $config = Aligner::getConfig();

        $db = NewDatabase::obtain($config['DB_SERVER'], $config['DB_USER'], $config['DB_PASS'], $config['DB_DATABASE']);
        try {
            $db->ping();
        } catch ( PDOException $e ) {
            $this->_doLog( "--- (Worker " . $this->_workerPid . ") : {$e->getMessage()} " );
            $this->_doLog( "--- (Worker " . $this->_workerPid . ") : Database connection reloaded. " );
            $db->close();
            //reconnect
            $db->getConnection();
        }




    }

}