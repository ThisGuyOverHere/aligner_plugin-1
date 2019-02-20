<?php

    namespace Features\Aligner\Decorator;

    use AbstractDecorator;
    use Features\Aligner;
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
            $config = Aligner::getConfig();

            $this->template->append( 'app_js', Routes::staticBuild( 'js/main.'.$config['RELEASE_VERSION'].'.min.js' ) );
            $this->template->append( 'app_css', Routes::staticBuild( 'css/style.'.$config['RELEASE_VERSION'].'.css' ) );
        }


    }
