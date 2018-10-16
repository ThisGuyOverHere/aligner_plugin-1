<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 11/10/2018
 * Time: 18:53
 */

namespace Features\Aligner\Controller;

use Features\Aligner\Utils\TMSService;

class TMXController extends AlignerController {


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
        }

        return $this->response->json( $tm );

    }

    public function createTmKey() {
        $tms          = \Engine::getInstance( 1 );
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
        $userMemoryKeys = $mkDao->create( $memoryKeyToUpdate );

        if ( !empty( $userMemoryKeys ) ) {
            return $this->response->json( true );
        } else {
            throw new \Exception( "Unable to create" );
        }

    }

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


        $file = $TMService->uploadFile();

        foreach ( $file as $fileInfo ) {
            if ( \FilesStorage::pathinfo_fix( strtolower( $fileInfo->name ), PATHINFO_EXTENSION ) !== 'tmx' ) {
                throw new \Exception( "Please upload a TMX.", -8 );
            }

            $TMService->setName( $fileInfo->name );
            $TMService->addTmxInMyMemory();

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
                $userMemoryKey           = $mkDao->read( $searchMemoryKey );

                if ( empty( $userMemoryKey[ 0 ]->tm_key->name ) && !empty( $userMemoryKey ) ) {
                    $userMemoryKey[ 0 ]->tm_key->name = $fileInfo->name;
                    $mkDao->updateList( $userMemoryKey );
                }

            }

        }

    }

    public function checkStatusUploadTMX() {

        $tm_key   = $this->params[ 'tm_key' ];
        $filename = $this->params[ 'filename' ];

        $TMService = new TMSService();
        $TMService->setTmKey( $tm_key );

        $TMService->setName( \Upload::fixFileName( $filename ) );
        $status = $TMService->tmxUploadStatus();

        $this->response->json( $status );

    }


}