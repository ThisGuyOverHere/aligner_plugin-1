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
            throw new \Exception( $this->result->error->message, $this->result->responseStatus );
        }

        \Log::doLog('TMX exported to E-mail.');
        return $this->result;
    }

    public function getMulti( $_configs ) {

        $segments = array();
        foreach ($_configs as $_config) {
            $parameters = array();
            if ( $this->client_secret != '' && $this->client_secret != null ) {
                $parameters[ 'key' ] = $this->client_secret;
            }
            $parameters['de'] = "matecat-aligner-2018@translated.net";
            $parameters['mtonly'] = 1;
            $parameters['q'] = $this->_preserveSpecialStrings($_config['segment']);
            $parameters['langpair'] = $this->_fixLangCode( $_config['source'] ) . '|' . $this->_fixLangCode( $_config['target'] );
            $segments[] = $parameters;

        }

        $this->_setAdditionalCurlParams(
            array(
                CURLOPT_POST       => true,
                //CURLOPT_POSTFIELDS => http_build_query( $parameters )
            )
        );


        $this->callMulti( "translate_relative_url", $segments, true );

        return $this->result;

    }

    public function _callMulti( $url, Array $curl_options_array = array() ) {

        $mh       = new \MultiCurlHandler();
        $resourceHashes = array();
        foreach ($curl_options_array as $curl_options) {
            $uniq_uid = uniqid( '', true );

            /*
             * Append array elements from the second array
             * to the first array while not overwriting the elements from
             * the first array and not re-indexing
             *
             * Use the + array union operator
             */
            $resourceHash = $mh->createResource($url,
                $this->curl_additional_params + $curl_options, $uniq_uid
            );
            $resourceHashes[] = $resourceHash;
        }
        $mh->multiExec();

        $rawValues = array();
        foreach ( $resourceHashes as $resourceHash ) {
            if ( $mh->hasError( $resourceHash ) ) {
                $curl_error = $mh->getError( $resourceHash );
                \Log::doLog( 'Curl Error: (http status ' . $curl_error[ 'http_code' ] .') '. $curl_error[ 'errno' ] . " - " . $curl_error[ 'error' ] . " " . var_export( parse_url( $url ), true ) );
                $responseRawValue = $mh->getSingleContent( $resourceHash );
                $rawValues[] = array(
                    'error' => array(
                        'code'      => -$curl_error[ 'errno' ],
                        'message'   => " {$curl_error[ 'error' ]} - Server Error (http status " . $curl_error[ 'http_code' ] .")",
                        'response'  => $responseRawValue // Some useful info might still be contained in the response body
                    ),
                    'responseStatus'    => $curl_error[ 'http_code' ]
                ); //return negative number
            } else {
                $rawValues[] = $mh->getSingleContent( $resourceHash );
            }
        }

        $mh->multiCurlCloseAll();

        if( $this->doLog ){
            foreach ($curl_options_array as $curl_options){
                $curl_parameters = $this->curl_additional_params + $curl_options;
                if( isset( $curl_parameters[ CURLOPT_POSTFIELDS ] ) ){
                    \Log::doLog( $uniq_uid . " ... Post Parameters ... \n" . var_export( $curl_parameters[ CURLOPT_POSTFIELDS ], true ) );
                }
            }
            \Log::doLog( $uniq_uid . " ... Received... " . var_export( $rawValues, true ) );
        }

        return $rawValues;

    }

    public function callMulti( $function, Array $segments = array(), $isPostRequest = false, $isJsonRequest = false ) {

        if ( $this->_isAnalysis && $this->_skipAnalysis ) {
            $this->result = [];
            return;
        }

        $this->error = array(); // reset last error
        if ( !$this->$function ) {
            \Log::doLog( 'Requested method ' . $function . ' not Found.' );
            $this->result = array(
                'error' => array(
                    'code'    => -43,
                    'message' => " Bad Method Call. Requested method '$function' not Found."
                )
            ); //return negative number
            return;
        }

        $curl_opt_array = array();
        foreach ($segments as $parameters) {
            if ($isPostRequest) {
                $function = strtolower(trim($function));
                $url = "{$this->engineRecord['base_url']}/" . $this->$function;
                $curl_opt = array(
                    CURLOPT_POSTFIELDS => (!$isJsonRequest ? http_build_query($parameters) : json_encode($parameters)),
                    CURLINFO_HEADER_OUT => true,
                    CURLOPT_TIMEOUT => 120
                );
            } else {
                $function = strtolower(trim($function));
                $url = "{$this->engineRecord['base_url']}/" . $this->$function . "?";
                $url .= http_build_query($parameters);
                $curl_opt = array(
                    CURLOPT_HTTPGET => true,
                    CURLOPT_TIMEOUT => 10
                );
            }
            $curl_opt_array[] = $curl_opt;
        }
        $rawValues = $this->_callMulti( $url, $curl_opt_array );

        /*
         * $parameters['segment'] is used in MT engines,
         * they does not return original segment, only the translation.
         * Taken when needed as "variadic function parameter" ( func_get_args )
         *
         * Pass the called $function also
        */
        $this->result = $this->_decodeMulti( $rawValues, $segments, $function );

    }

    protected function _decodeMulti( $rawValues ) {

        $all_args =  func_get_args();
        foreach ($all_args[1] as $key => $arg){
            $all_args[1][$key]['text'] = $arg['q'];
        }

        $decoded = array();
        foreach ($rawValues as $key => $rawValue) {
            if (is_string($rawValue)) {
                $decoded_element = json_decode($rawValue, true);
                if (isset($decoded_element["responseData"])) {
                    $decoded[] = $decoded_element['matches'][0];
                } else {
                    $decoded = [
                        'error' => [
                            'code' => $decoded_element["code"],
                            'message' => $decoded_element["message"]
                        ]
                    ];
                }
            } else {
                $resp = json_decode($rawValue["error"]["response"], true);
                if (isset($resp["error"]["code"]) && isset($resp["error"]["message"])) {
                    $rawValue["error"]["code"] = $resp["error"]["code"];
                    $rawValue["error"]["message"] = $resp["error"]["message"];
                }
                $decoded[] = $rawValue; // already decoded in case of error
            }
        }

        return $decoded;

    }
}