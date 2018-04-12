<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/24 15:00
 * 官网：http://www.phpcan.cn
 * ===============================================
 * MCQ队列类库
 * ===============================================
 */

namespace phpcan\lib;

class Mcq{

    // 连接句柄
    private $_conn;
    // 配置信息
    private $_conf;

    /**
     * Mcq constructor.
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        // 初始化数据库链接配置信息
        $this->_initConfig($conf);
        // 链接数据库
        $this->_linkDb();
    }

    /**
     * 描述：获取队列值
     * @param string $key
     * @return array|string
     */
    public function get(string $key = '')
    {
        $stime = microtime(TRUE);
        $queue = $this->_conn->get($this->_conf['PREFIX'].$key);
        $etime = microtime(TRUE);
        \api\Log::add('MCQ', [
            'KEY'  => $key,
            'TYPE' => 'read',
            'TIME' => round($etime - $stime, 4),
            'DATA' => $queue
        ]);
        return $queue;
    }

    /**
     * 描述：加入队列
     * @param string $key
     * @param $val
     */
    public function set(string $key = '', $val)
    {
        $stime = microtime(TRUE);
        // 格式化队列值
        $val = (is_object($val) || is_array($val)) ? json_encode($val) : $val;
        $this->_conn->set($this->_conf['PREFIX'].$key, $val, MEMCACHE_COMPRESSED, 0);
        $etime = microtime(TRUE);
        \api\Log::add('MCQ', [
            'KEY'  => $key,
            'TYPE' => 'write',
            'TIME' => round($etime - $stime, 4),
            'DATA' => $val
        ]);
    }

    /**
     * 描述：删除队列
     * @param string $key
     */
    public function del(string $key = '')
    {
        $stime = microtime(TRUE);
        $this->_conn->delete($key);
        $etime = microtime(TRUE);
        \api\Log::add('MCQ', [
            'KEY'  => $key,
            'TYPE' => 'del',
            'TIME' => round($etime - $stime, 4)
        ]);
    }

    /**
     * 描述：初始化配置信息
     * @param array $conf
     */
    private function _initConfig($conf = [])
    {
        if (empty($conf))
        {
            //加载配置
            $conf = conf('MCQ');
            if ( ! $conf)
                error(4001);
        }
        if ( ! isset($conf['HOST']))
        {
            error(4002, [
                'config' => 'HOST'
            ]);
        }
        $conf['PORT']   = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 11211 : $conf['PORT'];
        $conf['PREFIX'] = ( ! isset($conf['PREFIX']) || ! $conf['PREFIX']) ? '' : $conf['PREFIX'];
        $this->_conf = $conf;
    }

    /**
     * 描述：连接REDIS
     */
    private function _linkDb()
    {
        $this->_conn = new \Memcache();
        if ( ! $this->_conn->connect($this->_conf['HOST'], $this->_conf['PORT'], $this->_conf['TIMEOUT']))
        {
            error(4003);
        }
    }

}