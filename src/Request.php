<?php


namespace EasySwoole\Proxy;


class Request
{
    protected $host = '';
    protected $header = [];
    protected $get = [];
    protected $post = [];
    protected $cookies = [];
    protected $files = [];
    protected $rawContent = null;

}