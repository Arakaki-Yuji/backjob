<?php
use PHPUnit\Framework\TestCase;
use Backjob\Backend\Azurequeue\Adapter;
use Backjob\Backjob;
use Backjob\Job;

class BackjobTest extends TestCase
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

    public function testConstructor()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $backjob = new Backjob($adapter);
        $this->assertInstanceOf(Adapter::class, $backjob->getAdapter());
    }

    public function testGetAdapter()
    {
        $adapter = new Adapter($this->accountName, $this->queueName);
        $backjob = new Backjob($adapter);
        $this->assertInstanceOf(Adapter::class, $backjob->getAdapter());
    }

    public function testRun()
    {
        $adapter = Adapter::createAdapter($this->accountName,
                                          $this->queueName,
                                          $this->accessKey);
        $sampleJob = new SampleJob();
        $adapter->enqueue($sampleJob);
        $beforeCount = $adapter->countWaitingJobs();

        $backjob = new Backjob($adapter);

        $result = $backjob->run();
        // キューに入ったタスクが取り出されているか？
        $this->assertEquals($beforeCount - 1, $adapter->countWaitingJobs());
        // SampleJobのrunメソッドの返り値が返ってきているか？
        $this->assertInstanceOf('SampleJob', $result);
        $this->assertEquals(true, $result->success);
    }

    public function testRunException()
    {
        $adapter = Adapter::createAdapter($this->accountName,
                                          $this->queueName,
                                          $this->accessKey);
        $exceptionJob = new ExceptionJob();
        $adapter->enqueue($exceptionJob);
        $beforeCount = $adapter->countWaitingJobs();

        $backjob = new Backjob($adapter);

        try {
            $result = $backjob->run();
        }catch(Exception $e){
            // キューに入ったタスクが、もう一度キューに戻っているか？
            $this->assertEquals($beforeCount, $adapter->countWaitingJobs());
        }

        // retryMaxの上限を超えた場合にキューにタスクが入らないようになっているか
        try {
            $result = $backjob->run();
        }catch(Exception $e){
            $this->assertEquals($beforeCount - 1,$adapter->countWaitingJobs());
        }
    }

    public function testQueue()
    {
        $adapter = Adapter::createAdapter($this->accountName,
                                          $this->queueName,
                                          $this->accessKey);
        $beforeCount = $adapter->countWaitingJobs();
        
        $sampleJob = new SampleJob();
        $backjob = new Backjob($adapter);
        $backjob->queue($sampleJob);

        $this->assertEquals($beforeCount + 1, $adapter->countWaitingJobs());
    }
}

class SampleJob extends Job
{
    public $success = false;
    
    public function run()
    {
        return $this;
    }

    public function success()
    {
        $this->success = true;
    }
}

class ExceptionJob extends Job
{
    public $failed = false;
    
    protected $retryMax = 1;

    public function run()
    {
        throw new Exception('Running Error');
    }

    public function fail()
    {
        $this->failed = true;
    }
}
