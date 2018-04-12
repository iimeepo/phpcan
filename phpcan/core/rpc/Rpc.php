<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/2/21 10:51
 * 官网：http://www.phpcan.cn
 * ===============================================
 * RPC
 * ===============================================
 */

namespace phpcan;
use Hprose\Http\Server;
use Hprose\Http\Client;

class Rpc{

    private $_server;
    private $_client;

    /**
     * Rpc constructor.
     */
    public function __construct()
    {
        $this->_server = new Server();
        $this->_client = [];
    }

    /**
     * 描述：提供方法
     * @param string $name
     * @return object
     */
    public function add(string $name = '')
    {
        try
        {
            $this->_server->addFunction($name);
        }
        catch (\Exception $exception)
        {
            error($exception->getCode(), $exception->getMessage());
        }
        return $this;
    }

    /**
     * 描述：执行RPC服务
     */
    public function start()
    {
        $this->_server->start();
    }

    /**
     * 描述：调用失败默认返回
     */
    public function miss()
    {
        $this->_server->addMissingFunction(function($name){
            return json_encode([
                '404' => '远程函数'.$name.'不存在'
            ], JSON_UNESCAPED_UNICODE);
        });
    }

    /**
     * 描述：创建客户端
     * @param string $url
     * @return object
     */
    public function client(string $url)
    {
        $hash = md5($url);
        if ( ! isset($this->_client[$hash]))
        {
            $this->_client[$hash] = new Client($url, FALSE);
        }
        return $this->_client[$hash];
    }

}