<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 11/10/2018
 * Time: 18:53
 */

namespace Features\Aligner\Controller;

class TMXController extends AlignerController {


    public function getUserTM(){

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

        return $this->response->json($tm);

    }

    public function createTmCode(){
        $tms                    =  \Engine::getInstance( 1 );
        $mymemory_key = $tms->createMyMemoryKey();
        return $this->response->json($mymemory_key);
    }

    public function saveTm(){

        $tmKeyStruct       = new \TmKeyManagement_TmKeyStruct();
        $tmKeyStruct->key  = $this->params['key'];
        $tmKeyStruct->name = $this->params['name'];
        $tmKeyStruct->tm   = true;
        $tmKeyStruct->glos = true;

        $mkDao = new \TmKeyManagement_MemoryKeyDao( \Database::obtain() );

        $memoryKeyToUpdate         = new \TmKeyManagement_MemoryKeyStruct();
        $memoryKeyToUpdate->uid    = $this->user->uid;
        $memoryKeyToUpdate->tm_key = $tmKeyStruct;

        $userMemoryKeys = $mkDao->create( $memoryKeyToUpdate );

        return $userMemoryKeys;
    }



}