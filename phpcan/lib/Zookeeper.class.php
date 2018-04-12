<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/26 17:03
 * 官网：http://www.phpcan.cn
 * ===============================================
 * zookeeper类库
 * ===============================================
 */

namespace phpcan\lib;

class Zookeeper{

    private $_conf;
    private $_conn;
    private $_callback;

    public function __construct($conf = [])
    {
        // 初始化配置信息
        $this->_initConfig($conf);
        // 连接数据库
        $this->_linkDb();
        // 回调
        $this->_callback = [];
    }

    /**
     * 描述：创建节点
     * @param string $path
     * @param string $val
     * @param array $acl
     * @param int $flags
     * @return string
     */
    public function create(string $path = '', string $val = null, array $acl = [], int $flags = 2)
    {
        if ($path == '')
        {
            error(8004);
        }
        if (empty($acl))
        {
            $acl = [
                [
                    'perms'  => \Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id'     => 'anyone',
                ]
            ];
        }
        // 判断节点是不是已经存在
        if ($this->_conn->exists($path))
        {
            return FALSE;
        }
        try
        {
            return $this->_conn->create($path, $val, $acl, $flags);
        }
        catch(\ZookeeperException $exception)
        {
            error(0, $exception->getMessage());
        }
    }

    /**
     * 描述：删除一个节点
     * @param string $path
     * @return bool
     */
    public function delete(string $path = '')
    {
        if ($path == '')
        {
            error(8004);
        }
        try
        {
            return $this->_conn->delete($path);
        }
        catch(\ZookeeperException $exception)
        {
            error(0, $exception->getMessage());
        }
    }

    /**
     * 描述：从节点读取数据
     * @param string $path
     * @param callable|null $cb
     * @param bool $acl
     * @return array
     */
    public function get(string $path = '', callable $cb = null, bool $acl = FALSE)
    {
        if ($path == '')
        {
            error(8004);
        }
        try
        {
            $return = [];
            $data   = $this->_conn->get($path, $cb);
            $return['data'] = $data;
            if ( ! $acl)
                return $return;
            $aclData = $this->_conn->getAcl($path);
            $return['acl']  = $aclData;
            return $return;
        }
        catch(\ZookeeperException $exception)
        {
            error(0, $exception->getMessage());
        }
    }

    /**
     * 描述：获取所有子节点
     * @param string $path
     * @param callable|null $cb
     * @return array
     */
    public function children(string $path = '', callable $cb = null)
    {
        if ($path == '')
        {
            error(8004);
        }
        try
        {
            return $this->_conn->getChildren($path, $cb);
        }
        catch(\ZookeeperException $exception)
        {
            error(0, $exception->getMessage());
        }
    }

    /**
     * 描述：写入节点数据
     * @param string $path
     * @param string $val
     * @return bool
     */
    public function set(string $path = '', string $val = '')
    {
        if ($path == '')
        {
            error(8004);
        }
        try
        {
            return $this->_conn->set($path, $val);
        }
        catch(\ZookeeperException $exception)
        {
            error(0, $exception->getMessage());
        }
    }

    /**
     * 描述：添加监视
     * @param string $path
     * @param callable|null $cb
     * @param bool $child
     * @return bool
     */
    public function watch(string $path, callable $cb = null, bool $child = FALSE)
    {
        if ( ! is_callable($cb))
        {
            return FALSE;
        }
        if ( ! $this->_conn->exists($path))
        {
            return FALSE;
        }
        if ( ! in_array($cb, $this->_callback))
        {
            $this->_callback[$path] = [
                'cb'    => $cb,
                'child' => $child
            ];
        }
        ($child) ? $this->children($path, [$this, 'watchCallback']) :
            $this->get($path, [$this, 'watchCallback']);
    }

    /**
     * 描述：监听回调
     * @param int $type
     * @param int $stat
     * @param string $path
     * @return bool
     */
    public function watchCallback(int $type, int $stat, string $path)
    {
        if ( ! isset($this->_callback[$path]))
        {
            return FALSE;
        }
        $callback = $this->_callback[$path];
        if ($callback['child'])
        {
            $this->children($path, [$this, 'watchCallback']);
        }
        else
        {
            $this->get($path, [$this, 'watchCallback']);
        }
        // 调用回调
        call_user_func($callback['cb'], $path, $type, $stat);
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
            $conf = conf('ZOOKEEPER');
            if ( ! $conf)
                error(8001);
        }
        if ( ! isset($conf['HOST']))
        {
            error(8002, [
                'config' => 'HOST'
            ]);
        }
        $conf['TIMEOUT'] = ( ! isset($conf['TIMEOUT']) || ! $conf['TIMEOUT']) ? 3 : $conf['TIMEOUT'];
        $conf['PORT']    = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 2181 : $conf['PORT'];
        $this->_conf = $conf;
    }

    /**
     * 描述：连接ZOOKEEPER
     */
    private function _linkDb()
    {
        $this->_conn = new \Zookeeper();
        try
        {
            $this->_conn->connect($this->_conf['HOST'].':'.$this->_conf['PORT'], null, $this->_conf['TIMEOUT'] * 1000);
            \Zookeeper::setDebugLevel(\Zookeeper::LOG_LEVEL_ERROR);
        }
        catch (\ZookeeperException $exception)
        {
            error(8003, [
                'address' => $this->_conf['HOST'].':'.$this->_conf['PORT']
            ]);
        }
    }

}