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

    protected function getRedisClient(){
        if( empty( $this->redisClient ) ){
            $this->redisClient = (new \RedisHandler())->getConnection();
        }
        return $this->redisClient;
    }

    public function setRedisClient($redisClient){
        $this->redisClient = $redisClient;
    }

    public function getJobsInQueue(){
        return $this->getRedisClient()->lrange("jobs_in_queue", 0, $this->getRedisClient()->llen("jobs_in_queue")-1);
    }

    public function pushJobInQueue($job_id){
        $this->getRedisClient()->rpush("jobs_in_queue", $job_id);
    }

    public function popJobInQueue($job_id){
        $this->getRedisClient()->lrem("jobs_in_queue", 0, $job_id);
    }

    public function updateProgress($project_id, $progress){
        $this->getRedisClient()->set("project_".$project_id."_progress", $progress);
    }

    public function getProgress($project_id){
        return $this->getRedisClient()->get("project_".$project_id."_progress");
    }

}