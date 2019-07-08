<?php

namespace Backjob;

class Job implements JobInterface
{
    protected $params;
    protected $id;
    protected $createdAt;
    protected $popReceipt;
    protected $retryMax = 0;
    protected $currentRetry = 0;

    public function __construct(string $id = null,
                                array $params = null,
                                string $popReceipt = null,
                                int $createdAt = null)
    {
        $this->id = $id;
        $this->params = $params;
        $this->popReceipt = $popReceipt;
        $this->createdAt = $createdAt;
    }

    static public function makeJob($params)
    {
        return new static(static::makeId(), $params, null, time());
    }

    static public function makeId()
    {
        return uniqid();
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPopReceipt()
    {
        return $this->popReceipt;
    }

    public function getRetryMax(): int
    {
        return $this->retryMax;
    }

    public function getCurrentRetry(): int
    {
        return $this->currentRetry;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCurrentRetry(int $int)
    {
        $this->currentRetry = $int;
    }

    public function incrementCurrentRetry()
    {
        $this->currentRetry += 1;
        return $this->currentRetry;
    }

    public function enableRetry()
    {
        return $this->currentRetry < $this->retryMax;
    }

    public function run()
    {
        // 基底クラスなので何もしない
        // 子クラス側で実装する
    }

    protected function fail()
    {
        // 基底クラスなので何もしない
        // 子クラス側で実装する
    }

    protected function success()
    {
        // 基底クラスなので何もしない
        // 子クラス側で実装する
    }
}
