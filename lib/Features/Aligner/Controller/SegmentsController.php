<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 28/08/2018
 * Time: 11:26
 */

namespace Features\Aligner\Controller;

use Features\Aligner\Model\Segments_SegmentDao;

class SegmentsController extends AlignerController {

    public function get(){
        $id_job = $this->params['id_job'];
        $matches = Segments_SegmentDao::getByJobId($id_job);
        $this->response->json($matches);
    }

}