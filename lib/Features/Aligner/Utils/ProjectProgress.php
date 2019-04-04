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

    public function getJobsInQueue($queue){
        return $this->getRedisClient()->lrange($queue, 0, $this->getRedisClient()->llen($queue)-1);
    }

    public function pushJobInQueue($job_id, $queue){
        $this->getRedisClient()->rpush($queue, $job_id);
    }

    public function popJobInQueue($job_id, $queue){
        $this->getRedisClient()->lrem($queue, 0, $job_id);
    }

    public function updateProgress($project_id, $progress){
        $this->getRedisClient()->set("project_".$project_id."_progress", $progress);
    }

    public function getProgress($project_id){
        return $this->getRedisClient()->get("project_".$project_id."_progress");
    }

    public function updateSegmentCount($project_id, $segment_count){
        $this->getRedisClient()->set("project_".$project_id."_segment_count", $segment_count);
    }

    public function getSegmentCount($project_id){
        return $this->getRedisClient()->get("project_".$project_id."_segment_count");
    }

}