<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 19/11/2018
 * Time: 15:11
 */


namespace Features\Aligner\Controller;

use Exceptions\ValidationError;
use Features\Aligner\Utils\TMSService;
use FilesStorage\AbstractFilesStorage;

class TmController extends AlignerController {


    public function getUserTM() {

        $tm = [];

        try {

            $_keyList = new \TmKeyManagement_MemoryKeyDao( \Database::obtain() );
            $dh       = new \TmKeyManagement_MemoryKeyStruct( [ 'uid' => $this->user->uid ] );

            $keyList = $_keyList->read( $dh );

            foreach ( $keyList as $memKey ) {
                //all keys are available in this condition ( we are creating a project
                $tm[] = $memKey->tm_key;
            }

        } catch ( \Exception $e ) {
            \Log::doLog( $e->getMessage() );
            throw new ValidationError($e->getMessage());
        }

        return $this->response->json( $tm );

    }

    public function createTmKey() {

        try{
            $tms = \Engine::getInstance( 1 );
        } catch ( \Exception $e ){
            throw new ValidationError( $e->getMessage() );
        }

        $mymemory_key = $tms->createMyMemoryKey();

        return $this->response->json( $mymemory_key );
    }

    private function createTmData(){
        $tmKeyStruct       = new \TmKeyManagement_TmKeyStruct();
        $tmKeyStruct->key  = $this->params[ 'key' ];
        $tmKeyStruct->name = $this->params[ 'name' ];
        $tmKeyStruct->tm   = true;
        $tmKeyStruct->glos = true;

        $memoryKeyToUpdate         = new \TmKeyManagement_MemoryKeyStruct();
        $memoryKeyToUpdate->uid    = $this->user->uid;
        $memoryKeyToUpdate->tm_key = $tmKeyStruct;

        return $memoryKeyToUpdate;
    }

    public function saveTm() {
        $mkDao = new \TmKeyManagement_MemoryKeyDao( \Database::obtain() );

        $memoryKeyToUpdate = $this->createTmData();

        try{
            $userMemoryKeys = $mkDao->create( $memoryKeyToUpdate );
        } catch (\Exception $e) {
            throw new ValidationError( $e->getMessage() );
        }

        if ( !empty( $userMemoryKeys ) ) {
            return $this->response->json( true );
        } else {
            throw new ValidationError( "Unable to create" );
        }

    }

    /**
     * Note: Methods under below are not used yet, but I think they will be implement soon
     *
     */

    public function updateTm() {
        $mkDao = new \TmKeyManagement_MemoryKeyDao( \Database::obtain() );

        $memoryKeyToUpdate = $this->createTmData();
        $userMemoryKeys = $mkDao->update( $memoryKeyToUpdate );

        return $userMemoryKeys;
    }

    public function uploadTMX() {

        $tm_key = $this->params[ 'tm_key' ];

        $TMService = new TMSService();
        $TMService->setTmKey( $tm_key );

        try{
            $file = $TMService->uploadFile();
        } catch (\Exception $e) {
            throw new ValidationError( $e->getMessage() );
        }

        foreach ( $file as $fileInfo ) {
            if ( AbstractFilesStorage::pathinfo_fix( strtolower( $fileInfo->name ), PATHINFO_EXTENSION ) !== 'tmx' ) {
                throw new \Exception( "Please upload a TMX.", -8 );
            }

            $TMService->setName( $fileInfo->name );
            try {
                $TMService->addTmxInMyMemory();
            } catch ( \Exception $e ) {
                throw new \Exception( $e->getMessage() );
            }

            /*
             * We update the KeyRing only if this is NOT the Default MyMemory Key
             *
             * If it is NOT the default the key belongs to the user, so it's correct to update the user keyring.
             */
            if ( $tm_key != \INIT::$DEFAULT_TM_KEY ) {

                /*
                 * Update a memory key with the name of th TMX if the key name is empty
                 */
                $mkDao           = new \TmKeyManagement_MemoryKeyDao( \Database::obtain() );
                $searchMemoryKey = new \TmKeyManagement_MemoryKeyStruct();
                $key             = new \TmKeyManagement_TmKeyStruct();
                $key->key        = $tm_key;

                $searchMemoryKey->uid    = $this->user->uid;
                $searchMemoryKey->tm_key = $key;

                try {
                    $userMemoryKey = $mkDao->read( $searchMemoryKey );
                } catch ( \Exception $e ){
                    throw new ValidationError( $e->getMessage() );
                }

                if ( empty( $userMemoryKey[ 0 ]->tm_key->name ) && !empty( $userMemoryKey ) ) {
                    $userMemoryKey[ 0 ]->tm_key->name = $fileInfo->name;
                    try{
                        $mkDao->updateList( $userMemoryKey );
                    } catch ( \Exception $e ){
                        throw new ValidationError( $e->getMessage() );
                    }
                }

            }

        }

    }

    public function checkStatusUploadTMX() {

        $tm_key   = $this->params[ 'tm_key' ];
        $filename = $this->params[ 'filename' ];

        try {
            $TMService = new TMSService();
            $TMService->setTmKey( $tm_key );

            $TMService->setName( \Upload::fixFileName( $filename ) );
            $status = $TMService->tmxUploadStatus();
        } catch ( \Exception $e ){
            throw new ValidationError( $e->getMessage() );
        }

        $this->response->json( $status );

    }

}