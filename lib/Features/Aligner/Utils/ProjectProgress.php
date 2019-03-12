<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 08/03/2019
 * Time: 14:21
 */

namespace Features\Aligner\Utils;

use Predis\ClientContextInterface;

trait ProjectProgress {

    /**
     * @var $redisClient ClientContextInterface
     */
    public $redisClient;

    public function updateProgress($project_id, $progress){
        $this->getRedisClient()->set("project_".$project_id."_progress", $progress);
    }

    protected function getRedisClient(){
        if( empty( $this->redisClient ) ){
            $this->redisClient = (new \RedisHandler())->getConnection();
        }
        return $this->redisClient;
    }

    public function setRedisClient($redisClient){
        $this->redisClient = $redisClient;
    }

    public function getProgress($project_id){
        return $this->getRedisClient()->get("project_".$project_id."_progress");
    }

}