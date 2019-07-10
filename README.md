# backjob

a job queue library that use Azure Queue Storage .

# Installation

Include arakaki-yuji/backjob in your project, by adding it to your composer.json file.

``` json
{
    "require": {
        "arakaki-yuji/backjob": "^0.0.5"
    }
}
```

# Usage

## Define your own Job

``` php
class CustomJob extends \Backjob\Job
{
    /**
     * You must define a run method.
     * this method is called when dequeued and run
     */
    public function run()
    {
        $msg = $this->params['message'];
        return $msg;
    }
    
    /**
     * this method is optional.
     * if you define success method, it is called after run method successed.
     */
    public function success()
    {
        return 'success job';
    }
    
     /**
     * this method is optional.
     * if you define fail method, it is called after run method failed.
     */
    public function fail()
    {
        return 'success job';
    }
}
```



## Enqueue, dequeue and run a job.

setup a backjob instance.

``` php
$backjob = new \Backjob\Backjob::factory($storageAccountName, $queueName, $accessKey);

```

Enqueue a job

``` php

$params = ['message' => 'Hello Backjob'];
$job = CustomeJob::makeJob($params);
$backjob->queue($job);
```

Dequeue and run a job.

``` php
$backjob = new \Backjob\Backjob::factory($storageAccountName, $queueName, $accessKey);
$backjob->run(); // => 'Hello Backjob'

```
