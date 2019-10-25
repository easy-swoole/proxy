<?php


namespace EasySwoole\Proxy;


class Proxy
{
    protected $config;
    protected $followLocation = 3; // 重定向层次
    function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function pass(Request $request, float $timeout = 3.0)
    {
        $client = new Client($this->config, $request, $timeout);
        $client->enableFollowLocation($this->getFollowLocation());
        $content = $client->exec();
        return $content;
    }

    public function setFollowLocation($followLocation) {
        $this->followLocation = $followLocation;
    }

    public function getFollowLocation() {
        return $this->followLocation;
    }
}