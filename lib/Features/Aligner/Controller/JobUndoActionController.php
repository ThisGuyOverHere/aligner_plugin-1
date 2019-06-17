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
}