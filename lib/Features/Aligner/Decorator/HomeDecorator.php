<?php

    namespace Features\Aligner\Decorator;

    use AbstractDecorator;
    use Features\Aligner\Utils\Routes;

    class HomeDecorator extends AbstractDecorator {

        /**
         * @var \PHPTALWithAppend
         */
        protected $template ;

        /**
         * @var \PHPTALWithAppend
         */
        public function decorate() {
            $this->template->append( 'app_js', Routes::staticBuild( 'js/main.js' ) );
            $this->template->append( 'app_css', Routes::staticBuild( 'css/style.css' ) );
        }


    }