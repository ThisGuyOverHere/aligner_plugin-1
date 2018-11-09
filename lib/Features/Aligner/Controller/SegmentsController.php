<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 28/08/2018
 * Time: 11:26
 */

namespace Features\Aligner\Controller;

use Features\Aligner;
use Features\Aligner\Model\Segments_SegmentDao;

class SegmentsController extends AlignerController {

    public function get(){

        $id_job = $this->params['id_job'];
        
        /*$where  = $this->request->param( 'where' );
        $order_source  = $this->request->param( 'order_source' );
        $order_target  = $this->request->param( 'order_target' );
        $amount = $this->request->param( 'amount' );

        $config = Aligner::getConfig();

        if(empty($where)){$where = 'after';}
        if(empty($order_source)){$order_source = 0;}
        if(empty($order_target)){$order_target = 0;}
        if(empty($amount)){$amount = $config['SEGMENT_AMOUNT_PER_PAGE'];}*/

        //$target = Segments_SegmentDao::getTargetOrdered($id_job, $where, $order_target, $amount);
        $target = Segments_SegmentDao::getTypeOrderedByJobId($id_job, 'target');
        foreach ($target as $key => $segment){
            $target[$key]->content_raw   = Aligner\Utils\AlignUtils::_mark_xliff_tags($segment->content_raw);
            $target[$key]->content_clean = htmlspecialchars_decode($segment->content_clean);
        }

        //$source = Segments_SegmentDao::getSourceOrdered($id_job, $where, $order_source, $amount);
        $source = Segments_SegmentDao::getTypeOrderedByJobId($id_job, 'source');
        foreach ($source as $key => $segment){
            $source[$key]->content_raw   = Aligner\Utils\AlignUtils::_mark_xliff_tags($segment->content_raw);
            $source[$key]->content_clean = htmlspecialchars_decode($segment->content_clean);
        }
        $this->response->json(['target' => $target, 'source' => $source]);

    }
}