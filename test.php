<?php 

require 'autoload.php';

print("\nJob 1 - OK: ");
$j1 = new DummyJob("myqueue1");
$j1->send(array("x"=> 5, "y" => 3, "res" => "OK"));
print($j1->getId());


print("\nJob 2 - FAIL until limit");
$j2 = new DummyJob("myqueue2");
$j2->send(array("x"=> 10, "y" => 3, "res" => "FAIL"));
print($j2->getId());


print("\nJob 3 - FAIL until manual cancel");
$j3 = new DummyDelayJob();
$j3->send(array("x"=> 50, "y" => 10, "res" => "FAIL"));
print($j3->getId());
#$j3->cancel();

#sleep(6);

//cancel $j3 after first try
// example how to load a job by ID
#$j = DummyJob::load($j3->getId());
#$j->cancel();
#print("\n".$j->getStatus());

// Load ID job
#$id = "e35e00df15c9e170344010";
#$j2 = DummuyJob::load($id);

#echo $j2->getStatus();
#$j2->retry(array("hlle"));
