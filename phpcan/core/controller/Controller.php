<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/29 14:27
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 控制器基类
 * ===============================================
 */

namespace phpcan;
use phpcan\Init as Init;
use phpcan\Middleware as Middleware;
use ReflectionClass;

class Controller{

    // 当前URL
    protected $_url;
    // URL拆分的数组
    protected $_urlArr;

    /**
     * 描述：执行控制器
     */
    public function run()
    {
        $PHP_SELF = $_SERVER['PHP_SELF'];
        preg_match('/(.+)\/index.php(.*)/', $PHP_SELF, $match);
        // 判断是不是CLI模式
        if (php_sapi_name() == 'cli')
        {
            global $argv;
            if ( ! isset($argv[1]))
                error(101, '请指定访问的服务，例如：/demo/demo/');
            $this->_url = $argv[1];
        }
        else
        {
            // URL地址
            $this->_url = $_SERVER['REQUEST_URI'];
        }
        // 项目不在根目录则过滤
        if ( ! empty($match))
        {
            $this->_url = str_replace($match[1], '', $this->_url);
        }
        // 执行框架初始化
        if ($this->_url == '/')
        {
            $Init = new Init();
            $Init->run();
            return TRUE;
        }
        // 过滤参数
        $this->_url = preg_replace('/\?.*/', '', $this->_url);
        // 解析应用目录
        $this->_urlArr = explode('/', trim($this->_url, '/'));
        // 定义项目目录
        define('_APP', !in_array($this->_urlArr[0], conf('ALLOW')) ? conf('DEFAULT') : $this->_urlArr[0]);
        // 加载项目内配置文件，覆盖通用配置文件内容
        $appConfFile = _WORKPATH.'/'._APP.'/conf/Conf.php';
        if ( ! is_file($appConfFile))
        {
            error(1016, [
                'config' => $appConfFile
            ]);
        }
        $appConf = require $appConfFile;
        \api\Conf::merge($appConf);
        // 申明项目ID和当前环境常量，方便后续使用
        if (_SOA)
        {
            $eid = conf('EXAMPLE');
            if ( ! $eid || $eid == '')
            {
                error(1014);
            }
            define('EID', $eid);
            $env = conf('ENVIRONMENT');
            if ($env === FALSE || ! in_array($env, [0, 1]))
            {
                error(1015);
            }
            define('ENV', $env);
        }
        // 定义请求模式
        define('_METHOD', ( ! isset($_SERVER['REQUEST_METHOD'])) ? 'GET' : $_SERVER['REQUEST_METHOD']);
        // 判断有没有开启路由
        if ( ! conf('ROUTER'))
        {
            $this->_pathinfo();
        }
        else
        {
            // 注册路由
            require _WORKPATH.'/'._APP.'/router/Router.php';
            // 执行路由匹配
            $router = Router::run($this->_url, end($this->_urlArr));
            // 执行控制器
            $this->_do($router['namespace'], $router['action']);
            // 释放路由资源
            Router::release();
        }
    }

    /**
     * 描述：根据路径解析控制器
     */
    private function _pathinfo()
    {
        $action = array_pop($this->_urlArr);
        if (count($this->_urlArr) == 2)
        {
            $spi = ucfirst(array_pop($this->_urlArr));
        }
        else
        {
            $spi = $doc = '';
            $count = count($this->_urlArr);
            foreach ($this->_urlArr as $k => $url)
            {
                if ($k == 0) continue;
                $spi .= $doc;
                $spi .= ($k + 1 == $count) ? ucfirst($url) : $url;
                $doc  = '\\';
            }
        }
        $namespace = 'spi\\' . $spi;
        // 执行控制器
        $this->_do($namespace, $action);
    }

    /**
     * 描述：执行控制器
     * @param string $namespace
     * @param string $action
     * @throws
     */
    private function _do(string $namespace, string $action)
    {
        // 检测默认中间件
        $middleware = conf('MIDDLEWARE');
        if ( ! empty($middleware))
        {
            if (isset($middleware['BEFORE']))
                Middleware::default($middleware['BEFORE'], 1);
            if (isset($middleware['AFTER']))
                Middleware::default($middleware['AFTER'], 0);
        }
        // 获取控制器类的实例化
        $class = new ReflectionClass($namespace);
        if ( ! $class->hasMethod($action))
        {
            error(404, [
                'action' => $action
            ]);
        }
        // 获取构造函数，优先级高于函数
        $construct = $class->getConstructor();
        if ($construct != FALSE)
        {
            self::_getMiddleWareByDoc($construct->getDocComment());
        }
        $do = $class->getMethod($action);
        // 获取函数中间件
        self::_getMiddleWareByDoc($do->getDocComment());
        // 执行前置中间件
        $return = Middleware::runBefore($this->_url, $class, $namespace);
        if (is_bool($return))
        {
            if ($return)
            {
                return $return;
            }
            else
            {
                // 没有前置中间件
                $instance = $class->newInstance();
                $do->invoke($instance);
            }
        }
        elseif (is_array($return))
        {
            $class = $return['class'];
            $body  = $return['return'];
            $return = $class->$action($body);
        }
        // 执行后置中间件
        if (is_array($return))
        {
            Middleware::runAfter($this->_url, $return);
        }
        else
        {
            Middleware::runAfter($this->_url);
        }
    }

    /**
     * 描述：从注释中提取中间件
     * @param string $doc
     */
    private function _getMiddleWareByDoc(string $doc)
    {
        if ($doc != FALSE)
        {
            preg_match_all('#@([before|after]+):(.*)#', $doc, $data);
            if ( ! empty($data[1]))
            {
                foreach ($data[1] as $k => $v)
                    Middleware::$v($data[2][$k]);
            }
        }
    }

}