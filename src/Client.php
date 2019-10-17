<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 19-10-17
 * Time: 下午2:54
 */

namespace EasySwoole\Proxy;

use Swoole\Coroutine\Http\Client as HttpClient;

class Client
{
    // HTTP 1.0/1.1 标准请求方法
    const METHOD_GET = 'GET';
    const METHOD_PUT = 'PUT';
    const METHOD_POST = 'POST';
    const METHOD_HEAD = 'HEAD';
    const METHOD_TRACE = 'TRACE';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_OPTIONS = 'OPTIONS';
    // 常用POST提交请求头
    const CONTENT_TYPE_TEXT_XML = 'text/xml';
    const CONTENT_TYPE_TEXT_JSON = 'text/json';
    const CONTENT_TYPE_FORM_DATA = 'multipart/form-data';
    const CONTENT_TYPE_APPLICATION_XML = 'application/xml';
    const CONTENT_TYPE_APPLICATION_JSON = 'application/json';
    const CONTENT_TYPE_X_WWW_FORM_URLENCODED = 'application/x-www-form-urlencoded';

    protected $followLocation = 3;
    protected $redirected = 0;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Request
     */
    protected $request;

    protected $clientSetting = [];


    public function __construct(Config $config, Request $request, float $timeout = 1)
    {
        $this->config = $config;
        $this->request = $request;
        $this->setTimeout($timeout);
        $this->setConnectTimeout(5);
    }

    function enableFollowLocation(int $maxRedirect = 5):int
    {
        $this->followLocation = $maxRedirect;
        return $this->followLocation;
    }

    public function exec() {

        $cli = new HttpClient($this->config->getHost(), $this->config->getPort(), $this->config->getEnableSsl());
        $header = $this->request->getHeader();
        $cli->setHeaders($header);
        $cli->set($this->clientSetting);
        $cookies = $this->request->getCookies() ?? [];
        $cli->setCookies($cookies);

        $server = $this->request->getServer();
        $uri = $server['query_string'] ? $server['request_uri'] : $server['request_uri'].'?'.$server['query_string'];

        $method = $server['request_method'];
        $cli->setMethod($method);
        $rawData = $this->request->getRawContent();
        if($method == self::METHOD_POST){
            $files = $this->request->getFiles() ?? [];
            foreach ($files as $key => $item){
                $cli->addFile($item['tmp_name'], $key, $item['type'], $item['name']);
            }
            $cli->setData($rawData);
        } else if($rawData !== null){
            $cli->setData($rawData);
        }

        if(is_string($rawData)){
            $header['Content-Length'] = strlen($rawData);
        }

        $cli->execute($uri);

        // 如果不设置保持长连接则直接关闭当前链接
        if ($this->request->getHeader()['connection'] !== 'keep-alive') {
            $cli->close();
        }
        // 处理重定向
//        if (($cli->statusCode == 301 || $cli->statusCode == 302) && (($this->followLocation > 0) && ($this->redirected < $this->followLocation))) {
//            $this->redirected++;
//            $this->setUrl($client->headers['location']);
//        }else{
//            $this->redirected = 0;
//        }
        if ($method === Client::METHOD_HEAD) {
            $content = $cli->getHeaders();
        } else {
            $content = $cli->getBody();
        }
        return $content;
    }

    /**
     * 设置请求等待超时时间
     * @param float $timeout
     * @return Client
     */
    public function setTimeout(float $timeout)
    {
        $this->clientSetting['timeout'] = $timeout;
        return $this;
    }

    /**
     * 设置连接服务端的超时时间
     * @param float $connectTimeout 超时时间 单位秒(可传入浮点数指定毫秒)
     * @return Client
     */
    public function setConnectTimeout(float $connectTimeout)
    {
        $this->clientSetting['connect_timeout'] = $connectTimeout;
        return $this;
    }

}