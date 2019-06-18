<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 14/06/2019
 * Time: 17:06
 */

namespace Features\Aligner\Controller;


use Exceptions\ValidationError;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Model\Segments_SegmentDao;

class JobUndoActionController extends JobActionController
{

    public function undoDelete() {

        $id_job   = $this->job->id;
        $matches = $this->params[ 'matches' ];

        $sources = [];
        $targets = [];

        foreach ( $matches as $match ) {
            if ( $match[ 'type' ] == 'target' ) {
                $targets[] = $match[ 'order' ];
            } else {
                $sources[] = $match[ 'order' ];
            }
        }

        $previous_matches = [ 'target' => [], 'source' => [] ];
        $next_matches     = [ 'target' => [], 'source' => [] ];
        $next_orders      = [ 'target' => [], 'source' => [] ];
        $new_matches      = [ 'target' => [], 'source' => [] ];

        foreach ( [ 'target' => $targets, 'source' => $sources ] as $key => $matches ){

            foreach ( $matches as $match ){

                $previous_match = Segments_SegmentMatchDao::getPreviousMatchOfNonExistent( $match, $id_job, $key );
                $next_match     = Segments_SegmentMatchDao::getNextMatchOfNonExistent( $match, $id_job, $key );
                $next_order     = (!empty($next_match)) ? $next_match['order'] : null;

                if (isset($previous_match)) {
                    $previous_match['next'] = $match;
                    $previous_matches[$key][] = $previous_match;
                }

                if (isset($next_match)){
                    $next_matches[$key][] = $next_match;
                }

                $next_orders[$key][] = $next_order;

                $new_match = [
                    'id_job' => $id_job,
                    'type' => $key,
                    'order' => $match,
                    'score' => 100,
                    'segment_id' => null,
                    'next' => $next_order,
                ];

                $new_matches[$key][] = $new_match;

                try {

                    $this->pushOperation( [
                        'type'      => $key,
                        'action'    => ($next_order !== null) ? 'create': 'push',
                        'rif_order' => $match,
                        'data'      => $new_match
                    ] );

                } catch ( ValidationError $e ) {
                    throw new ValidationError( $e->getMessage(), -2 );
                }

            }

        }

        $conn = NewDatabase::obtain()->getConnection();
        try {

            $conn->beginTransaction();

            $matchesDao = new Segments_SegmentMatchDao();

            $matchesDao->updateList($previous_matches['source']);
            $matchesDao->updateList($previous_matches['target']);
            $matchesDao->createList($new_matches['source']);
            $matchesDao->createList($new_matches['target']);

            $conn->commit();

        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }

    public function undoSwitchAction() {

        //It's just like the switch but it doesn't return operations to undo

        $id_job   = $this->job->id;

        $type   = $this->params[ 'type' ];
        $order1 = $this->params[ 'order1' ];
        $order2 = $this->params[ 'order2' ];

        $segment_1 = Segments_SegmentDao::getFromOrderJobIdAndType( $order1, $id_job, $type );
        if(!is_object($segment_1)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $segment_1 = $segment_1->toArray();

        $segment_2 = Segments_SegmentDao::getFromOrderJobIdAndType( $order2, $id_job, $type );
        if(!is_object($segment_2)){
            throw new ValidationError("There's no segment with the parameters specified in the input");
        }
        $segment_2 = $segment_2->toArray();

        $conn = NewDatabase::obtain()->getConnection();
        try {
            $conn->beginTransaction();
            Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $segment_2[ 'id' ], 'score' => 100 ], $order1, $id_job, $type );
            Segments_SegmentMatchDao::updateFields( [ 'segment_id' => $segment_1[ 'id' ], 'score' => 100 ], $order2, $id_job, $type );
            $conn->commit();
        } catch ( \PDOException $e ) {
            $conn->rollBack();
            throw new \PDOException( "Segment update - DB Error: " . $e->getMessage(), -2 );
        }

        $segment_1_copy = $segment_1;

        $segment_1[ 'order' ] = $segment_2[ 'order' ];
        $segment_1[ 'next' ]  = $segment_2[ 'next' ];
        $segment_1[ 'score' ] = 100;

        $segment_2[ 'order' ] = $segment_1_copy[ 'order' ];
        $segment_2[ 'next' ]  = $segment_1_copy[ 'next' ];
        $segment_2[ 'score' ] = 100;

        try{
            $this->pushOperation( [
                'type'      => $type,
                'action'    => "update",
                'rif_order' => $segment_2[ 'order' ],
                'data'      => $segment_2
            ] );

            $this->pushOperation( [
                'type'      => $type,
                'action'    => "update",
                'rif_order' => $segment_1[ 'order' ],
                'data'      => $segment_1
            ] );
        } catch ( ValidationError $e ) {
            throw new ValidationError( $e->getMessage(), -2 );
        }

        return $this->getOperations();

    }
}