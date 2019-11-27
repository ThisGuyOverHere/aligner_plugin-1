<?php


namespace Features\Aligner\Decorator;


use AbstractDecorator;
use Features\Aligner\Utils\Routes;

class NewProjectDecorator extends AbstractDecorator {
    /**
     * @var \PHPTALWithAppend
     */
    protected $template ;

    public function decorate()
    {
        $this->template->append('footer_js', Routes::staticBuild('js/aligner.js') );

    }
}