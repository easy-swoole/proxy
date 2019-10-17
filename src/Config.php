<?php


namespace EasySwoole\Proxy;


class Config
{
    protected $host;
    protected $port;
    protected $enableSsl = false;

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port): void
    {
        $this->port = $port;
    }

    /**
     * @return bool
     */
    public function getEnableSsl(): bool
    {
        return $this->enableSsl;
    }

    /**
     * @param bool $enableSsl
     */
    public function setEnableSsl(bool $enableSsl): void
    {
        $this->enableSsl = $enableSsl;
    }

}