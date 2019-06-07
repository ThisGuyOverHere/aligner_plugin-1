<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 26/10/2018
 * Time: 11:24
 */

namespace Features\Aligner\Utils\AsyncTasks\Workers;

use Features\Aligner\Utils\TaskRunner\Commons\AbstractWorker;

class TMXImportWorker extends AbstractWorker {

    public function process( \TaskRunner\Commons\AbstractElement $queueElement ) {

        /**
         * @var $queueElement QueueElement
         */
        $this->_checkForReQueueEnd( $queueElement );
        $this->_checkDatabaseConnection();
        $this->_TMXImport( $queueElement );

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

    protected function _TMXImport(\TaskRunner\Commons\QueueElement $queueElement){
        $options = json_decode( $queueElement->params, true );

        $TMService = new \Features\Aligner\Utils\TMSService();
        $TMService->setTmKey( $options['tm_key'] );

        $TMService->exportJobAsTMX( $options['job']['id'], $options['job']['source'], $options['job']['target'] );

        $TMService->setName( "Aligner-" . $options['job']['id'] . ".tmx" );

        $response = $TMService->importTMXInTM();

        $tmx_id = $response->id;

        while ( empty($upload_status[ 'completed' ]) ) {
            sleep( 1 );
            $upload_status = $TMService->tmxUploadStatus();
        }

        $TMService->requestChunkTMXEmailDownload( $tmx_id, $options['email'], $options['first_name'], $options['last_name'] );

    }

}