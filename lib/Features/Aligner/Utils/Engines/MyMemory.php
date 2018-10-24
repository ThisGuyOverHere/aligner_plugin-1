<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 24/10/2018
 * Time: 15:44
 */

namespace Features\Aligner\Utils;

class Engines_MyMemory extends \Engines_MyMemory {


    /**
     * Calls the MyMemory endpoint to send the TMX download URL to the user e-mail
     *
     * @param $key
     * @param $name
     * @param $userEmail
     * @param $userName
     * @param $userSurname
     *
     *
     * @return Engines_Results_MyMemory_ExportResponse
     * @throws Exception
     *
     */
    public function emailExport( $key, $name, $userEmail, $userName, $userSurname, $id_tmx) {
        $parameters = array();

        $parameters[ 'key' ] = trim( $key );
        $parameters[ 'user_email' ] = trim( $userEmail );
        $parameters[ 'user_name' ] = trim( $userName ) . " " . trim( $userSurname );
        ( !empty( $name ) ? $parameters[ 'zip_name' ] = $name : $parameters[ 'zip_name' ] = $key );
        $parameters[ 'zip_name' ] = $parameters[ 'zip_name' ] . ".zip";
        $parameters['id_tmx'] = $id_tmx;

        $this->call( 'tmx_export_email_url', $parameters );

        /**
         * $result Engines_Results_MyMemory_ExportResponse
         */
        if ( $this->result->responseStatus >= 400 ) {
            throw new Exception( $this->result->error->message, $this->result->responseStatus );
        }

        Log::doLog('TMX exported to E-mail.');
        return $this->result;
    }
}