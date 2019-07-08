<?php

namespace Backjob;

class Backjob
{
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function run()
    {
        $job = $this->adapter->dequeue();
        try {
            $result = $job->run();
        }catch(\Exception $e){

            $job->fail();

            if($job->enableRetry()){
                $job->incrementCurrentRetry();
                $this->adapter->enqueue($job);
            }
            throw $e;
        }

        $job->success();

        return $result;
    }

    public function queue(Job $job)
    {
        return $this->adapter->enqueue($job);
    }

}
