<?php 
require_once 'Job.php';

class DummyJob extends Job 
{

    protected $retryCountLimit = 3;

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
        }
        catch (\Exception $ex) {
            $this->retry();
        }
    }
    public function tearDown() 
    {
    }
}