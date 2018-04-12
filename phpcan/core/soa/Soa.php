<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/3/12 17:29
 * 官网：http://www.phpcan.cn
 * ===============================================
 * SOA服务交互
 * ===============================================
 */

namespace phpcan;
use phpcan\Client as Client;

class Soa{

    private $_Client;

    public function __construct()
    {
        if ( ! _SOA)
        {
            error(1011);
        }
        if (_GATEWAY == '')
        {
            error(1012);
        }
        $this->_Client = new Client();
    }

    /**
     * 描述：向网关发送GET请求
     * @param string $url
     * @param array $param
     * @return array
     */
    public function get(string $url = '', array $param = [])
    {
        if ($url == '')
        {
            error(1013);
        }
        return $this->_Client->get($url, $param);
    }

    /**
     * 描述：向网关发送POST请求
     * @param string $url
     * @param array $data
     * @param array $params
     * @return array
     */
    public function post(string $url = '', array $data = [], array $params = [])
    {
        if ($url == '')
        {
            error(1013);
        }
        if (empty($data))
        {
            error(1017);
        }
        return $this->_Client->post($url, $data, $params);
    }

    /**
     * 描述：添加并发任务
     * @param string $key
     * @param array $params
     * @return object
     */
    public function add(string $key = '', array $params = [])
    {
        $this->_Client->add($key, $params);
        return $this;
    }

    /**
     * 描述：执行并发任务
     * @return array
     */
    public function run()
    {
        return $this->_Client->run();
    }

    /**
     * 描述：设置超时时间
     * @param int $timeOut
     * @return object
     */
    public function timeout(int $timeOut)
    {
        $this->_Client->timeout($timeOut);
        return $this;
    }

    /**
     * 描述：设置HEADER头信息
     * @param array $header
     * @return $this
     */
    public function header(array $header = [])
    {
        $this->_Client->header($header);
        return $this;
    }

}