<?php

    namespace Features\Aligner\Decorator;

    use AbstractDecorator;
    use Features\Aligner\Utils\Routes;

    class HomeDecorator extends AbstractDecorator {
        /**
         * @var \PHPTALWithAppend
         */
        protected $template;

        public function decorate() {
            $this->template->append( 'app-js', Routes::staticBuild( 'js/main.js' ) );
            $this->template->append( 'app-css', Routes::staticBuild( 'css/style.css' ) );
        }


    }