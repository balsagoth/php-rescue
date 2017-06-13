<?php

class NotImplementedException extends BadMethodCallException {}

class RetryLimitException extends RuntimeException {}


/**
 * Class Job
 *
 */
class Job
{

    protected $retryCountLimit = 10;
    
    protected $id;

    protected $queue;

    protected $args;

    protected $status;
    
    protected $retryCount;
    
    protected $redis;

    protected $job;


    public function getId()
    {
        return $this->id;
    }
    
    public function getStatus()
    {
        return $this->status;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function getRetryCount() 
    {
        if (empty($this->retryCount)) {
            $redis = $this->getRedis(); 
            $this->retryCount = $redis->hget(\Resque\Job::redisKey($this->job), 'retry');
            if (!$this->retryCount) { //returns false if not there
                $redis->hset(\Resque\Job::redisKey($this->job), 'retry', 0);
                $this->retryCount = 0;
            }
        }
        return $this->retryCount;
    }

    private function getRedis()
    {
        // TODO: Check if this works with other config different from default?
        // TODO: Change to DB 1 in settings.
        if (empty($this->redis)) {
           $this->redis = \Resque\Redis::instance();
        }
        return $this->redis;
    }
 
    public function __construct($queue = 'default', $job = null)
    {
        // FIXME this is not working
        \Resque::loadConfig();
        
        $this->queue   = $queue;  //this is always overrided with the right queuename
        $this->class    = get_class($this);

        if (!empty($job)) {
            $this->fillJob($job);
        }
    }
    
    public static function load($id) 
    {
        $job = \Resque::job($id);
        return new static($job->getQueue(), $job);
    }

    private function fillJob($job) 
    {
        // overrride class stuff with stuff on Job
        $this->job = $job;
        $this->id = $job->getId();
        $this->queue = $job->getQueue();
        $this->class = $job->getClass();
        $this->status = $job->getStatus();
    }
    
    public function run($args) {
        throw NotImplementedException("You should override this method");
    }

    public function perform($args, $job) {
        $this->fillJob($job);
        $this->run($args);
    }

    public function send($args = array(), $delay = 0)
    {
        if (!empty($this->id)) {
            throw new \RuntimeException('Already sent');   
        }
        
        try {
            if ($delay > 0 ) {
                $job  = \Resque::later($delay, $this->class, $args, $this->queue);
            } else {
                $job  = \Resque::push($this->class, $args, $this->queue);
            }
            $this->fillJob($job);
            
        } catch (Exception $ex) {
           echo "Log to sentry";
        }
    }
    
    // FIXME: this doesn't work
    public function cancel()
    {
        if (empty($this->id)) {
            throw new \RuntimeException('You need load a job first');
        }
        $this->job->cancel();

        // \Resque\Stats::decr('queued', 1);
        // \Resque\Stats::decr('queued', 1, Queue::redisKey($this->queue, 'stats'));    
    
    }

    // give control to the retrier to pass diferent args
    public function retry($delay = 0) 
    {
        $this->retryLimit();
        $this->incRetryCount();
        if ($delay > 0) {
            $delay += time();
            $this->job->delay($delay);
        } else {
            $this->job->queue();
        }
    }

    // child class can override this and decide what are its limits
    // should raise RetryLimitException
    private function retryLimit()
    {
        if ($this->getRetryCount() >= $this->retryCountLimit) {
            throw RetryLimitException("Tried ".$this->getRetryCount()." times");
        }
    }
   
    private function incRetryCount()
    {
        $redis = $this->getRedis(); 
        $redis->hincrby(\Resque\Job::redisKey($this->job), 'retry', 1);
        $this->retryCount++;
    }
    
}