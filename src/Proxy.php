<?php


namespace EasySwoole\Proxy;


class Proxy
{
    protected $config;
    function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function pass(Request $request, float $timeout = 1)
    {
        $client = new Client($this->config, $request, $timeout);
        $content = $client->exec();
        return $content;
    }
}