<?php

namespace Backjob\Backend\Azurequeue;

class Config
{
    private $serviceAccountName = '';
    private $accessKey = '';
    private $queueName = '';

    public function __construct(string $accountName,
                                string $accessKey,
                                string $queueName){
        $this->serviceAccountName = $accountName;
        $this->accessKey = $accessKey;
        $this->queueName = $queueName;
    }

    public function getServiceAccountName()
    {
        return $this->serviceAccountName;
    }

}
