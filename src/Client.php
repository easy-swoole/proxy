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

    /**
     * @var HttpClient
     */
    protected $client;

    protected $uri;

    protected $method;

    public function __construct(Config $config, Request $request, float $timeout = 3.0)
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

    public function exec():Response {
        $this->client = $this->getClient();
        $this->client->setMethod( $this->method );
        $rawData = $this->request->getRawContent();
        if( $this->method  == self::METHOD_POST){
            $files = $this->request->getFiles() ?? [];
            foreach ($files as $key => $item){
                $this->client->addFile($item['tmp_name'], $key, $item['type'], $item['name']);
            }
            $this->client->setData($rawData);
        } else if($rawData !== null){
            $this->client->setData($rawData);
        }

        $this->client->execute($this->uri);

        // 如果不设置保持长连接则直接关闭当前链接
        if ($this->request->getHeader()['connection'] !== 'keep-alive') {
            $this->client->close();
        }
        // 处理重定向
        if (($this->client->statusCode == 301 || $this->client->statusCode == 302) && $this->followLocation > 0 && $this->redirected < $this->followLocation) {
            $this->redirected ++;
            $this->uri = $this->client->headers['location'];
            return $this->exec();
        } else {
            $this->redirected = 0;
        }
        $response = new Response();
        // 取出header里面的set-cookie
        $heads = $this->client->getHeaders();
        unset($heads['set-cookie']);
        $cookies = $this->client->set_cookie_headers;
        $cookies = empty($cookies) ? [] : $cookies;
        $newCookies = $this->getCookies($cookies);
        $code = $this->client->getStatusCode();
        $content = $this->client->getBody();
        $response->setStatus($code);
        $response->setCookies($newCookies);
        $response->setHeader($heads);
        $response->setBody($content);
        return $response;
    }

    public function getClient(): HttpClient
    {
        if ($this->client instanceof HttpClient) {
            return $this->client;
        }
        $this->client = new HttpClient($this->config->getHost(), $this->config->getPort(), $this->config->getEnableSsl());
        $this->client->set($this->clientSetting);
        $header = $this->request->getHeader();
        $this->client->setHeaders($header);
        $cookies = $this->request->getCookies();
        if (!empty($cookies)) {
            $this->client->setCookies($cookies);
        }
        $server = $this->request->getServer();
        $this->uri = empty($server['query_string']) ? $server['request_uri'] : $server['request_uri'].'?'.$server['query_string'];
        $this->method = $server['request_method'];
        return $this->client;
    }

    private function getCookies($cookies): array {
        $newCookies = [];
        foreach ($cookies as $key => $cookie) {
            $arr = explode(';', $cookie);
            foreach ($arr as $index => $item) {
                $item = trim($item);
                $items = explode("=", $item);
                $pos = $items[0];
                if ($index === 0) {
                    $newCookies[$key]["name"] = $pos;
                    $newCookies[$key]["value"] = urldecode($items[1]);
                } else {
                    if ($pos === "expires") {
                        $items[1] = $items[1] ? strtotime($items[1]) : time();
                    }
                    $newCookies[$key][$pos] = $items[1] ?? true;
                }
            }
        }
        return $newCookies;
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