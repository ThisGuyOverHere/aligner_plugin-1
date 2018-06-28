<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 28/06/2018
 * Time: 15:35
 */

namespace Features\Aligner\Controller;


use PHPTALWithAppend;

class HomeController extends \BaseKleinViewController {

    /**
     * @var \PHPTAL ;
     */
    protected $view;

    public function afterConstruct() {

    }

    public function setView( $template_name ) {
        $this->view = new PHPTALWithAppend( $template_name );
    }


    public function composeView(){
        //$this->setDefaultTemplateData() ;

        $this->response->body( $this->view->execute() );
        $this->response->send();

    }

}