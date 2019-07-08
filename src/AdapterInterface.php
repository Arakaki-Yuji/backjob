<?php

namespace Backjob;

interface AdapterInterface {

    public function dequeue();
    public function enqueue(Job $job);
}
