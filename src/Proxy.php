<?php


namespace EasySwoole\Proxy;


class Proxy
{
    protected $config;
    function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function pass(float $timeout = null)
    {
        return '';
    }
}