<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/29 14:46
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 路由类
 * ===============================================
 */

namespace phpcan;

class Router{

    private static $_map = [];
    private static $_key = [];

    /**
     * 描述：分组创建路由
     * @param array $preg
     * @param string $route
     * @param array $middleware
     * @return bool
     */
    public static function group(array $preg, string $route, array $middleware = [])
    {
        if (empty($preg) || ! $route)
        {
            return FALSE;
        }
        foreach ($preg as $v)
        {
            self::$_map[$v]['router'] = $route;
        }
        // 注册中间件
        if ( ! empty($middleware))
        {
            if (isset($middleware['BEFORE']))
                Middleware::before($middleware['BEFORE']);
            if (isset($middleware['AFTER']))
                Middleware::after($middleware['AFTER']);
        }
    }

    /**
     * 描述：精准路由
     * @param array $key
     * @param array $middleware
     * @return bool
     */
    public static function set(array $key, array $middleware = [])
    {
        if (empty($key))
        {
            return FALSE;
        }
        self::$_key = array_merge(self::$_key, $key);
        // 注册中间件
        if ( ! empty($middleware))
        {
            if (isset($middleware['BEFORE']))
                Middleware::before($middleware['BEFORE']);
            if (isset($middleware['AFTER']))
                Middleware::after($middleware['AFTER']);
        }
    }

    /**
     * 描述：执行路由匹配
     * @param string $url
     * @param string $action
     * @return array
     */
    public static function run(string $url, string $action)
    {
        $result = [];
        // 先进行KEY值快速匹配
        if ( ! empty(self::$_key))
        {
            if (isset(self::$_key[$action]))
            {
                $arr = explode('/', self::$_key[$action]);
                $action = array_pop($arr);
                $result['router'] = implode('/', $arr);
            }
        }
        // 进行分组匹配
        if (empty($result) && ! empty(self::$_map))
        {
            $url = preg_replace('#/'._APP.'/#', '', $url);
            // 进行分组匹配
            foreach (self::$_map as $preg => $row)
            {
                if (preg_match('#/?'.$preg.'/?#i', $url, $data))
                {
                    if ( ! isset($data[1]))
                        error(1009);
                    $result = $row;
                    // 修正action参数
                    $action = $data[1];
                    if (strpos($action, '/') != FALSE)
                        $action = substr($data[1], (strripos($data[1], '/') + 1), strlen($data[1]));
                    break;
                }
            }
        }
        // 没有匹配到路由则404
        if ( ! $result)
        {
            error(404, [
                'action' => $action
            ]);
        }
        // 组装命名空间
        $namespace = 'spi\\'.str_replace('/', '\\', $result['router']);
        return [
            'namespace' => $namespace,
            'action' => $action
        ];
    }

    /**
     * 描述：释放路由资源
     */
    public static function release()
    {
        self::$_map = [];
        self::$_key = [];
    }

}