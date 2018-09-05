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
        $where  = $this->request->param( 'where' );
        $order_source  = $this->request->param( 'order_source' );
        $order_target  = $this->request->param( 'order_target' );
        $amount = $this->request->param( 'amount' );

        $config = Aligner::getConfig();

        if(empty($where)){$where = 'after';}
        if(empty($order_source)){$order_source = 0;}
        if(empty($order_target)){$order_target = 0;}
        if(empty($amount)){$amount = $config['SEGMENT_AMOUNT_PER_PAGE'];}

        $target = Segments_SegmentDao::getTargetOrdered($id_job, $where, $order_target, $amount);
        $source = Segments_SegmentDao::getSourceOrdered($id_job, $where, $order_source, $amount);
        $this->response->json(['target' => $target, 'source' => $source]);

    }
}