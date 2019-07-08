<?php

use PHPUnit\Framework\TestCase;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use Backjob\Backend\Azurequeue\Adapter;
use Backjob\Job;

/**
 * @group QueueStorage
 */
class AdapterTest extends TestCase
{

    public function setUp()
    {
        $this->accountName = $_ENV['BACKJOB_AZURE_SERVICE_ACCOUNT_NAME'];
        $this->accessKey = $_ENV['BACKJOB_AZURE_ACCESS_KEY'];
        $this->queueName = $_ENV['BACKJOB_AZURE_QUEUE_NAME'];
    }

    public function tearDown()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $conStr =  $adapter->makeConnectionStringByAccessKey($this->accessKey);
        $client = $adapter->makeClient($conStr);
        $client->clearMessages($this->queueName);
    }

    public function testConstruct()
    {
        $adapter = new Adapter('accountName', 'queueName');
        $this->assertInstanceOf(Adapter::class, $adapter);
    }

    public function testMakeConnectionStringByAccessKey()
    {
        $adapter = new Adapter('accountName', 'queueName');
        $this->assertEquals('DefaultEndpointsProtocol=https;AccountName=accountName;AccountKey=accessKey',
                            $adapter->makeConnectionStringByAccessKey('accessKey'));
    }

    public function testMakeClient()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $conStr =  $adapter->makeConnectionStringByAccessKey($this->accessKey);
        $client = $adapter->makeClient($conStr);
        $this->assertInstanceOf(QueueRestProxy::class, $client);
    }

    public function testJob2Message()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $job = new Job(null, ['test' => true]);
        $this->assertEquals(json_encode(['class_name' => 'Backjob\Job',
                                         'params' => ['test' => true],
                                         'currentRetry' => 0]),
                            $adapter->job2MessageText($job));
    }

    public function testEnqueue()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $conStr =  $adapter->makeConnectionStringByAccessKey($this->accessKey);
        $adapter->makeClient($conStr);
        $job = new Job(null, ['test' => true]);
        $result = $adapter->enqueue($job);
        $this->assertInstanceOf('MicrosoftAzure\Storage\Queue\Models\CreateMessageResult',
                                $result);
    }

    public function testDequeue()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $conStr =  $adapter->makeConnectionStringByAccessKey($this->accessKey);
        $adapter->makeClient($conStr);

        // Jobがまだ登録されていないパターン
        $this->assertNull($adapter->dequeue());

        // Jobを登録
        $job = new Job(null, ['test' => true]);
        $adapter->enqueue($job);

        // Jobがキューから取れるかテスト
        $job = $adapter->dequeue();
        $this->assertInstanceOf('Backjob\Job', $job);
        $this->assertEquals(['test' => true], $job->getParams());
        $this->assertIsString($job->getId());
        $this->assertIsString($job->getPopReceipt());
    }

    public function testMessage2Job()
    {
        $params = ['class_name' => 'Backjob\Job', 'params' => ['test'=>true], 'currentRetry' => 3];
        $msgText = json_encode($params);
        $queueMsg = new QueueMessage;
        $queueMsg->setMessageText($msgText);
        $queueMsg->setMessageId('test-message-id-1234');
        $queueMsg->setPopReceipt('test-pop-receipe-1234');

        $adapter = new Adapter($this->accountName, $this->queueName);
        $job = $adapter->message2Job($queueMsg);

        $this->assertInstanceOf("Backjob\Job", $job);
        $this->assertEquals(["test" => true], $job->getParams());
        $this->assertEquals('test-message-id-1234', $job->getId());
        $this->assertEquals('test-pop-receipe-1234', $job->getPopReceipt());
        $this->assertEquals(3, $job->getCurrentRetry());
    }

    public function testCountWaitingJobs()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $conStr =  $adapter->makeConnectionStringByAccessKey($this->accessKey);
        $adapter->makeClient($conStr);

        $this->assertEquals(0, $adapter->countWaitingJobs());

        // Jobを登録
        $job = new Job(null, ['test' => true]);
        $adapter->enqueue($job);

        $this->assertEquals(1, $adapter->countWaitingJobs());

        $adapter->dequeue();

        $this->assertEquals(0, $adapter->countWaitingJobs());
    }

}
