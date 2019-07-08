<?php

use PHPUnit\Framework\TestCase;
use Backjob\Backend\Azurequeue\Config;

/**
 * @group QueueStorage
 */
class ConfigTest extends TestCase
{
    public function testConstruct()
    {
        $config = new Config('testname', 'accesskey', 'queuename');
        $this->assertInstanceOf('Backjob\Backend\Azurequeue\Config', $config);
    }

    public function testGetServiceAccountName()
    {
        $config = new Config('testname', 'accesskey', 'queuename');
        $this->assertEquals('testname', $config->getServiceAccountName());
    }
}
