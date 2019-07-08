<?php

use PHPUnit\Framework\TestCase;
use Backjob\Job;

class JobTest extends TestCase
{
    public function testConstruct()
    {
        $job = new Job(null, ['a' => 1, 'b' => 'hello world']);
        $this->assertInstanceOf('Backjob\Job', $job);
    }

    public function testMakeJob()
    {
        $sampleJob = SampleJob::makeJob(['a' => 1, 'b' => 2]);
        $this->assertInstanceOf('SampleJob', $sampleJob);
        $this->assertIsString($sampleJob->getId());
        $this->assertIsInt($sampleJob->getCreatedAt());
    }
}
