<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 26/10/2018
 * Time: 11:24
 */

namespace Features\Aligner\AsyncTasks\Workers;

include_once \INIT::$UTILS_ROOT . "/xliff.parser.1.3.class.php";

use Features\Aligner\Model\Jobs_JobDao;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Utils\Alignment;
use Features\Aligner\Utils\Constants;

class AlignJobWorker extends \TaskRunner\Commons\AbstractWorker {

    private $id_job;

    public function process( \TaskRunner\Commons\AbstractElement $queueElement ) {

        /**
         * @var $queueElement QueueElement
         */
        $this->_checkForReQueueEnd( $queueElement );
        $this->_checkDatabaseConnection();
        $this->_Align( $queueElement );
        $this->_publishResults();

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

    protected function _Align( \TaskRunner\Commons\QueueElement $queueElement ) {
        $options = json_decode( $queueElement->params, true );

        $this->id_job = $options[ 'id_job' ];
        $job          = Jobs_JobDao::getById( $this->id_job )[ 0 ];

        $segmentsMatchDao = new Segments_SegmentMatchDao;
        $segmentsMatchDao->deleteByJobId( $this->id_job );

        $source_lang = $job->source;
        $target_lang = $job->target;

        $source_segments = Segments_SegmentDao::getDataForAlignment( $this->id_job, "source" );
        $target_segments = Segments_SegmentDao::getDataForAlignment( $this->id_job, "target" );

        $alignment_class = new Alignment;
        $alignment       = $alignment_class->alignSegments( $source_segments, $target_segments, $source_lang, $target_lang );

        $source_array = [];
        $target_array = [];
        foreach ( $alignment as $key => $value ) {
            $source_element = [];

            if ( !empty( $value[ 'source' ] ) ) {
                //Check if $value['source'] is an array of segments otherwise it wouldn't have any numerical keys
                if ( isset( $value[ 'source' ][ 0 ] ) ) {
                    $value[ 'source' ] = $this->mergeSegments( $value[ 'source' ] );
                }
            }
            $source_element[ 'segment_id' ] = $value[ 'source' ][ 'id' ];
            $source_element[ 'order' ]      = ( $key + 1 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $source_element[ 'next' ]       = ( $key + 2 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $source_element[ 'id_job' ]     = $this->id_job;
            $source_element[ 'score' ]      = $value[ 'score' ];
            $source_element[ 'type' ]       = "source";

            $target_element = [];

            if ( !empty( $value[ 'target' ] ) ) {
                //Check if $value['target'] is an array of segments otherwise it wouldn't have any numerical keys
                if ( isset( $value[ 'target' ][ 0 ] ) ) {
                    $value[ 'target' ] = $this->mergeSegments( $value[ 'target' ] );
                }
            }

            $target_element[ 'segment_id' ] = $value[ 'target' ][ 'id' ];
            $target_element[ 'order' ]      = ( $key + 1 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $target_element[ 'next' ]       = ( $key + 2 ) * Constants::DISTANCE_INT_BETWEEN_MATCHES;
            $target_element[ 'score' ]      = $value[ 'score' ];
            $target_element[ 'id_job' ]     = $this->id_job;
            $target_element[ 'type' ]       = "target";

            $source_array[] = $source_element;
            $target_array[] = $target_element;
        }

        $source_array[ count( $source_array ) - 1 ][ 'next' ] = null;
        $target_array[ count( $target_array ) - 1 ][ 'next' ] = null;


        $segmentsMatchDao->createList( $source_array );
        $segmentsMatchDao->createList( $target_array );
    }


    protected function mergeSegments( Array $segments ) {
        $new_segment                    = [];
        $new_segment[ 'id' ]            = $segments[ 0 ][ 'id' ];
        $new_segment[ 'content_clean' ] = '';
        foreach ( $segments as $segment ) {
            $new_segment[ 'content_clean' ] .= ' ' . $segment[ 'content_clean' ];
        }
        Segments_SegmentDao::mergeSegments( $segments );

        return $new_segment;
    }

}