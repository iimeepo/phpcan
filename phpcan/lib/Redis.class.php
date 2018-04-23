<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/24 14:59
 * 官网：http://www.phpcan.cn
 * ===============================================
 * REDIS类库
 * ===============================================
 */

namespace phpcan\lib;

class Redis{

    // 连接句柄
    private $_conn;
    // 配置信息
    private $_conf;
    // 操作的索引KEY
    private $_key;
    // 操作类型
    private $_action;

    public function __construct($conf = [])
    {
        // 初始化数据库链接配置信息
        $this->_initConfig($conf);
        // 链接数据库
        $this->_linkDb();
        $this->_key = '';
        $this->_action = '';
    }

    /**
     * 描述：设置KEY
     * @param $key
     * @param string $action
     * @return object
     */
    public function key($key = '', string $action = '')
    {
        if (is_array($key))
        {
            foreach ($key as $v)
                $this->_key[] = $this->_conf['PREFIX'].$v;
        }
        else
        {
            $this->_key = $this->_conf['PREFIX'].$key;
        }
        $this->_action = $action;
        return $this;
    }

    /**
     * 描述：获取数据
     * @param array $params
     * @return string|int|array
     */
    public function get(...$params)
    {
        $stime = microtime(TRUE);
        if ($this->_action == '') $this->_action = 'get';
        $handle = [$this->_key];
        $handle = array_merge($handle, $params);
        $data   = call_user_func_array([
            $this->_conn,
            $this->_action
        ], $handle);
        $etime = microtime(TRUE);
        \api\Log::add('REDIS', [
            'TYPE'   => 'read',
            'ACTION' => $this->_action,
            'KEY'    => $this->_key,
            'TIME'   => round($etime - $stime, 4)
        ]);
        $this->_action = '';
        return $data;
    }

    /**
     * 描述：写入数据
     * @param array $params
     */
    public function set(...$params)
    {
        $stime = microtime(TRUE);
        if ($this->_action == '') $this->_action = 'set';
        // 根据类型获取写入值
        if (in_array($this->_action, [
            'zadd', 'hincrby', 'lset', 'zadd', 'hset'
        ]))
        {
            $data = $params[1];
        }
        elseif (in_array($this->_action, [
            'decr', 'incr'
        ]))
        {
            $data = 1;
        }
        else
        {
            $data = $params[0];
        }
        $handle = [$this->_key];
        $handle = array_merge($handle, $params);
        call_user_func_array([
            $this->_conn,
            $this->_action
        ], $handle);
        $etime = microtime(TRUE);
        \api\Log::add('REDIS', [
            'TYPE'   => 'write',
            'ACTION' => $this->_action,
            'KEY'    => $this->_key,
            'DATA'   => $data,
            'TIME'   => round($etime - $stime, 4)
        ]);
        $this->_action = '';
    }

    /**
     * 描述：删除数据
     * @param array $params
     */
    public function del(...$params)
    {
        $stime = microtime(TRUE);
        if ($this->_action == '') $this->_action = 'del';
        if ($this->_action == 'del' && $this->_key == '')
        {
            $handle = [];
        }
        else
        {
            $handle = [$this->_key];
        }
        $handle = array_merge($handle, $params);
        call_user_func_array([
            $this->_conn,
            $this->_action
        ], $handle);
        $etime = microtime(TRUE);
        \api\Log::add('REDIS', [
            'TYPE'   => 'del',
            'ACTION' => $this->_action,
            'KEY'    => $this->_key,
            'TIME'   => round($etime - $stime, 4)
        ]);
        $this->_action = '';
    }

    /**
     * 描述：清空
     */
    public function clear()
    {
        $this->_conn->delete($this->_conn->keys($this->_key));
    }

    /**
     * 描述：返回KEY数量
     */
    public function dbSize()
    {
        return $this->_conn->dbSize();
    }

    /**
     * 描述：返回REDIS相关信息
     */
    public function info()
    {
        return $this->_conn->info();
    }

    /**
     * 描述：清空库
     */
    public function flush()
    {
        $this->_conn->flushDB();
    }

    /**
     * 描述：同步数据到硬盘
     */
    public function save()
    {
        $this->_conn->save();
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
            $conf = conf('REDIS');
            if ( ! $conf)
                error(3001);
        }
        if ( ! isset($conf['HOST']))
        {
            error(3002, [
                'config' => 'HOST'
            ]);
        }
        $conf['PASSWORD'] = ( ! isset($conf['PASSWORD']) || ! $conf['PASSWORD']) ? '' : $conf['PASSWORD'];
        $conf['TIMEOUT']  = ( ! isset($conf['TIMEOUT']) || ! $conf['TIMEOUT']) ? 1 : $conf['TIMEOUT'];
        $conf['DATABASE'] = ( ! isset($conf['DATABASE']) || ! $conf['DATABASE']) ? 0 : $conf['DATABASE'];
        $conf['PORT']     = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 6379 : $conf['PORT'];
        $conf['PREFIX']   = ( ! isset($conf['PREFIX']) || ! $conf['PREFIX']) ? '' : $conf['PREFIX'];
        $this->_conf = $conf;
    }

    /**
     * 描述：连接REDIS
     */
    private function _linkDb()
    {
        $this->_conn = new \Redis();
        if ( ! $this->_conn->pconnect($this->_conf['HOST'], $this->_conf['PORT'], $this->_conf['TIMEOUT']))
        {
            error(3003);
        }
        if ($this->_conf['PASSWORD'] != '')
        {
            if ( ! $this->_conn->auth($this->_conf['PASSWORD']))
                error(3004);
        }
        $this->_conn->select($this->_conf['DATABASE']);
    }

}