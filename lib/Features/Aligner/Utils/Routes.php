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

        return $host . "/aligner/static/build/$file";
    }


    public static function downloadTMX( $job_id, $job_password, $options = array() ) {
        $host = \Routes::pluginsBase( $options );

        return "$host/aligner/job/$job_id/$job_password/download_tmx";
    }

}