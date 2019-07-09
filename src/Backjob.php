<?php

namespace Backjob;

use Backjob\Backend\Azurequeue\Adapter;


class Backjob
{
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public static function factory(string $serviceAccountName,
                                   string $queueName,
                                   string $accessKey)
    {
        $adapter = Adapter::createAdapter($serviceAccountName, $queueName, $accessKey);
        return new Backjob($adapter);
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function run()
    {
        $job = $this->adapter->dequeue();
        if(is_null($job)){
            return null;
        }
        try {
            $result = $job->run();
        }catch(\Exception $e){
            $this->afterFail($job, $e);
            throw $e;
        }catch(\Error $e){
            $this->afterFail($job, $e);
            throw $e;
        }

        $job->success();

        return $result;
    }

    public function queue(Job $job)
    {
        return $this->adapter->enqueue($job);
    }

    private function afterFail($job)
    {
        $job->fail();
        if($job->enableRetry()){
            $job->incrementCurrentRetry();
            $this->adapter->enqueue($job);
        }
    }

}
