<?php 
require_once 'Job.php';

class DummyDelayJob extends Job 
{

    protected $retryCountLimit = 6;

    public function setUp() 
    {
    }
    public function run($args=array()) 
    {
        sleep(6);
        try {
            if ($args['res'] == "FAIL") {
                throw new \RuntimeException("Failing job");
            }
            return $args['x'] * $args['y'];

        } catch (\Exception $ex) {
            // exponentional backoff
            print(pow(2, $this->getRetryCount()));
            $this->retry(pow(2, $this->getRetryCount()));
        }
    }
    public function tearDown() 
    {
    }
}