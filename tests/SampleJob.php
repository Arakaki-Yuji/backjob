<?php

namespace Backjob;

class SampleJob extends Job
{

    public function run()
    {
        return 'HelloWorld';
    }
}
