<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/26 17:06
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 外观
 * ===============================================
 */

namespace phpcan;

class Facade{

    /**
     * 描述：获取实例
     * @param $class
     * @return object
     */
    public static function getInstance($class)
    {
        return Di::get($class);
    }

    /**
     * 描述：作为子类重载传值的入口
     */
    public static function getFacadeAccessor()
    {

    }

    /**
     * 描述：返回静态方法
     * @param $method
     * @param $args
     * @return object
     */
    public static function __callstatic($method, $args)
    {
        $instance = static::getInstance(static::getFacadeAccessor());
        $handler  = [$instance, $method];
        if (is_callable($handler))
        {
            return call_user_func_array($handler, $args);
        }
        else
        {
            error(1008, [
                'function' => $method
            ]);
        }
    }

}