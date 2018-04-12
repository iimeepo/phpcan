<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/25 15:11
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 依赖注入容器
 * ===============================================
 */

namespace phpcan;

use ReflectionClass;
use phpcan\Controller as Controller;

final class Di {

    private static $service = [];

    /**
     * 描述：执行容器并注册自动加载文件
     */
    public static function run()
    {
        require _PHPCAN.'/core/loader/Autoload.php';
        // 注册自动加载
        spl_autoload_register('phpcan\Autoload::run');
        // 重置容器
        self::$service = [];
        // 载入助手函数包
        require _PHPCAN . '/core/helper/Helper.php';
        // 如果过是CLI模式则停止
        if ( ! defined('_CLI'))
        {
            // 如果是RPC调用则不执行后续控制器部分
            if (_RPC)
            {
                \api\Rpc::miss();
                $func = api\Conf::get('RPC_FUNC');
                if ($func != FALSE)
                {
                    foreach ($func as $name)
                        \api\Rpc::add($name);
                }
                \api\Rpc::start();
            }
            else
            {
                // 运行控制器
                $Controller = new Controller();
                $Controller->run();
            }
        }
    }

    /**
     * 描述：为容器设置值
     * @param $name
     * @param $value
     */
    public static function set($name, $value)
    {
        self::$service[$name] = $value;
    }

    /**
     * 描述：从容器中获取值
     * @param $name
     * @return object
     */
    public static function get($name)
    {
        // 从容器中获取实例，如果没有则创建实例并注册到容器中
        if (isset(self::$service[$name]))
        {
            return self::$service[$name];
        }
        else
        {
            return self::build($name);
        }
    }

    /**
     * 描述：构建容器
     * @param $name
     * @return object
     * @throws
     */
    public static function build($name)
    {
        // 如果是匿名函数
        if ($name instanceof \Closure)
        {
            // 执行闭包函数并将结果返回
            is_callable($name) or error(1002, ['function' => $name]);
            return $name();
        }
        $reflector = new ReflectionClass($name);
        // 检查类是否可实例化
        if ( ! $reflector->isInstantiable())
        {
            error(1007);
        }
        // 获取类的构造函数
        $constructor = $reflector->getConstructor();
        // 若无构造函数，直接实例化并返回
        if (is_null($constructor))
        {
            $class = new $name;
            self::set($name, $class);
            return $class;
        }
        // 取构造函数参数,通过 ReflectionParameter 数组返回参数列表
        $params = $constructor->getParameters();
        if ( ! empty($params))
        {
            // 递归解析构造函数的参数
            $depend = self::_getDepend($params);
            // 创建一个类的新实例,给出的参数将传递到类的构造函数。
            $class  = $reflector->newInstanceArgs($depend);
        }
        else
        {
            $class = new $name();
        }
        self::set($name, $class);
        return $class;
    }

    /**
     * 描述：递归解析构造参数
     * @param $params
     * @return array
     */
    private static function _getDepend($params)
    {
        $depend = [];
        foreach ($params as $val)
        {
            $class = $params[0]->getClass();
            if (is_null($class))
            {
                // 是变量,有默认值则设置默认值
                $depend[] = self::_resolveNonClass($val);
            }
            else
            {
                // 是一个类,递归解析
                $depend[] = self::build($class->name);
            }
        }
        return $depend;
    }

    /**
     * 描述：设置默认值
     * @param $param
     * @return string
     */
    private static function _resolveNonClass($param)
    {
        // 有默认值则返回默认值
        if ($param->isDefaultValueAvailable())
        {
            return $param->getDefaultValue();
        }
    }

}