<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 28/06/2018
 * Time: 15:35
 */

namespace Features\Aligner\Controller;


use Features\Aligner\Decorator\HomeDecorator;
use PHPTALWithAppend;

class HomeController extends \BaseKleinViewController {

    /**
     * @var HomeDecorator
     */
    protected $decorator ;

    /**
     * @var \PHPTAL ;
     */
    protected $view;


    public function setView( $template_name ) {
        $this->view = new PHPTALWithAppend( $template_name );
    }


    public function composeView(){
        $decorator = new HomeDecorator($this, $this->view);
        $decorator->decorate();

        $this->response->body( $this->view->execute() );
        $this->response->send();

    }

}