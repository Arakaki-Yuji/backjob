<?php

namespace Backjob\Backend\Azurequeue;

use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use Backjob\Job;
use Backjob\AdapterInterface;

class Adapter implements AdapterInterface
{
    private $accountName;
    private $queueName;
    private $client;

    static public function createAdapter(string $accountName,
                                         string $queueName,
                                         string $accessKey)
    {
        $adapter = new Adapter($accountName, $queueName);
        $connStr = $adapter->makeConnectionStringByAccessKey($accessKey);
        $adapter->makeClient($connStr);
        return $adapter;
    }

    public function __construct(string $accountName,
                                string $queueName)
    {
        $this->accountName = $accountName;
        $this->queueName = $queueName;
    }

    public function makeConnectionStringByAccessKey(string $accessKey)
    {
        return "DefaultEndpointsProtocol=https;AccountName={$this->accountName};AccountKey={$accessKey}";
    }

    public function makeClient(string $connectionString)
    {
        $this->client = QueueRestProxy::createQueueService($connectionString);
        return $this->client;
    }

    public function job2MessageText(Job $job)
    {
        return json_encode([
            'class_name' => get_class($job),
            'params' => $job->getParams(),
            'currentRetry' => $job->getCurrentRetry(),
            'createdAt' => $job->getCreatedAt()
        ]);
    }

    public function message2Job(QueueMessage $msg)
    {
        $msgText = $msg->getMessageText();
        $array = json_decode($msgText, true);
        $class_name = $array['class_name'];
        $params = $array['params'];
        $createdAt = $array['createdAt'];
        $job = new $class_name($msg->getMessageId(),
                               $params,
                               $msg->getPopReceipt(),
                               $createdAt);
        $job->setCurrentRetry($array['currentRetry'] ?? 0);
        return $job;
    }

    public function enqueue(Job $job)
    {
        $msgText = $this->job2MessageText($job);
        return $this->client->createMessage($this->queueName,$msgText);
    }

    public function dequeue()
    {
        $msgOptions = new ListMessagesOptions();
        $msgOptions->setNumberOfMessages(1);
        $msgOptions->setVisibilityTimeoutInSeconds(300);
        $messageResult = $this->client->listMessages($this->queueName, $msgOptions);
        $messages = $messageResult->getQueueMessages();
        
        if(count($messages) < 1){
            return null;
        }
        $queueMsg = $messages[0];
        $this->client->deleteMessage(
            $this->queueName,
            $queueMsg->getMessageId(),
            $queueMsg->getPopReceipt()
        );
        return $this->message2Job($queueMsg);
    }

    public function countWaitingJobs()
    {
        $queueMeta = $this->client->getQueueMetadata($this->queueName);
        return $queueMeta->getApproximateMessageCount();
    }
}
