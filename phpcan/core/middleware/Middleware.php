<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/31 17:39
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 中间件
 * ===============================================
 */

namespace phpcan;

class Middleware{

    // 前置中间件
    private static $_before = [];
    // 后置中间件
    private static $_after  = [];
    // 参数
    private static $_args   = null;
    // 中间件返回值
    private static $_return = [];
    // 控制器的实例
    private static $_class;
    // 项目中间件
    private static $_middleware;

    /**
     * 描述：注册前置中间件
     * @param $name
     * @return bool
     */
    public static function before($name)
    {
        if (empty($name))
        {
            return FALSE;
        }
        if (is_array($name))
        {
            self::$_before = array_merge(self::$_before, $name);
        }
        else
        {
            array_push(self::$_before, $name);
        }
    }

    /**
     * 描述：注册后置中间件
     * @param $name
     * @return bool
     */
    public static function after($name)
    {
        if (empty($name))
        {
            return FALSE;
        }
        if (is_array($name))
        {
            self::$_after = array_merge(self::$_after, $name);
        }
        else
        {
            array_push(self::$_after, $name);
        }
    }

    /**
     * 描述：默认中间件
     * @param array $name
     * @param int $type
     * @return bool
     */
    public static function default(array $name, int $type)
    {
        if (empty($name))
        {
            return FALSE;
        }
        // 前置中间件反转
        $name = array_reverse($name);
        foreach ($name as $middleware)
        {
            if ($type == 1)
            {
                array_unshift(self::$_before, $middleware);
            }
            else
            {
                array_unshift(self::$_after, $middleware);
            }
        }
    }

    /**
     * 描述：执行前置中间件
     * @param string $url
     * @param $class
     * @param $namespace
     * @return bool|array
     */
    public static function runBefore(string $url, $class, $namespace)
    {
        if (empty(self::$_before))
        {
            return FALSE;
        }
        // 实例化控制器
        self::$_class = new $namespace();
        // 实例化中间件
        self::$_middleware = new \middleware\Middleware();
        // 遍历并执行前置
        foreach (self::$_before as $name)
        {
            $name = trim($name);
            if (self::_args($name, $class))
            {
                $name = str_replace('(...)', '', $name);
                if ( ! method_exists(self::$_middleware, $name))
                error(1006, [
                    'middleware' => $name
                ]);
                $return = self::$_middleware->$name(md5($url), self::$_args);
            }
            else
            {
                if ( ! method_exists(self::$_middleware, $name))
                error(1006, [
                    'middleware' => $name
                ]);
                $return = self::$_middleware->$name(md5($url));
            }
            if (is_bool($return) && $return)
            {
                return $return;
            }
            elseif (is_array($return))
            {
                self::$_return = array_merge(self::$_return, $return);
            }
        }
        return [
            'class'  => self::$_class,
            'return' => self::$_return
        ];
    }

    /**
     * 描述：执行后置中间件
     * @param string $url
     * @param array $args
     * @return bool
     */
    public static function runAfter(string $url, array $args = [])
    {
        if (empty(self::$_after))
        {
            return FALSE;
        }
        self::$_after = array_reverse(self::$_after);
        foreach (self::$_after as $name)
        {
            $name = trim($name);
            if (strpos($name, '...') !== FALSE)
            {
                $name = str_replace('(...)', '', $name);
                if ( ! method_exists(self::$_middleware, $name))
                error(1006, [
                    'middleware' => $name
                ]);
                $return = self::$_middleware->$name(md5($url), self::$_args, $args);
            }
            else
            {
                if ( ! method_exists(self::$_middleware, $name))
                error(1006, [
                    'middleware' => $name
                ]);
                $return = self::$_middleware->$name(md5($url), [], $args);
            }
            if (is_bool($return) && $return)
                return $return;
        }
        // 释放资源
        self::$_before = [];
        self::$_after  = [];
        self::$_args   = null;
    }

    /**
     * 描述：检查中间件参数
     * @param string $name
     * @param $class
     * @return bool
     */
    private static function _args(string $name, $class)
    {
        if (strpos($name, '...') !== FALSE)
        {
            if ( ! is_null(self::$_args))
            {
                return TRUE;
            }
            $args = $class->getProperties();
            if (empty($args))
            {
                self::$_args = [];
                return TRUE;
            }
            self::$_args = [];
            foreach ($args as $arg)
            {
                $fieldName = $arg->getName();
                $field = $class->getProperty($fieldName);
                if ( ! $field->isPublic())
                    $field->setAccessible(true);
                $val = $field->getValue(self::$_class);
                self::$_args[$fieldName] = $val;
            }
            return TRUE;
        }
        return FALSE;
    }

}