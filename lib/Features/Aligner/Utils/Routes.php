<?php

/**
 * Created by PhpStorm.
 * User: riccio
 * Date: 22/09/17
 * Time: 10.47
 */

namespace Features\Aligner\Utils;


class Routes {

    public static function staticBuild( $file, $options = [] ) {
        $host = \Routes::pluginsBase( $options );

        return $host . "/aligner/build/$file";
    }

}