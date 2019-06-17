<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 17/06/2019
 * Time: 16:38
 */

namespace Features\Aligner\Controller;


use Features\Aligner\Controller\Validators\JobPasswordValidator;
use Exceptions\ValidationError;
use Features\Aligner\Model\NewDatabase;
use Features\Aligner\Model\Segments_SegmentDao;
use Features\Aligner\Model\Segments_SegmentMatchDao;
use Features\Aligner\Model\Segments_SegmentMatchStruct;
use Features\Aligner\Model\Segments_SegmentStruct;
use Features\Aligner\Utils\AlignUtils;
use Features\Aligner\Utils\Constants;


class JobActionController extends AlignerController
{

    protected $operations;
    protected $job;

    public function afterConstruct() {
        $jobValidator = ( new JobPasswordValidator( $this ) );

        $jobValidator->onSuccess( function () use ( $jobValidator ) {
            $this->job     = $jobValidator->getJob();
            $this->project = $this->job->getProject();
        } );

        $this->appendValidator( $jobValidator );
    }


    protected function pushOperation( $operation ) {
        $operation_fields = [ 'type', 'action', 'rif_order', 'data' ];
        foreach ( array_keys( $operation ) as $field ){
            if( !in_array( $field, $operation_fields ) ){
                throw new ValidationError( "Operation format is not valid" );
            }
        }
        AlignUtils::_parseArrayIntegers( $operation );
        if( !empty( $operation[ 'data' ] ) ){
            $segment_fields = AlignUtils::_getObjectVariables(new Segments_SegmentStruct());
            $match_fields   = AlignUtils::_getObjectVariables(new Segments_SegmentMatchStruct());

            $data_fields    = AlignUtils::_array_union($segment_fields, $match_fields);
            foreach ( array_keys( $operation[ 'data' ] ) as $field ){
                if( !in_array( $field, $data_fields ) ){
                    throw new ValidationError( "Operation data format is not valid" );
                }
            }
            $operation['data']['content_raw']   = AlignUtils::_mark_xliff_tags($operation['data']['content_raw']);
            $operation['data']['content_clean'] = htmlspecialchars_decode($operation['data']['content_clean']);
            AlignUtils::_parseArrayIntegers( $operation['data'] );
        }
        $this->operations[] = $operation;
    }

    protected function getOperations() {
        return $this->response->json( $this->operations );
    }

}