<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/25 15:31
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 自动载入文件
 * ===============================================
 */

namespace phpcan;

final class Autoload{

    private static $map = [];
    // 定义别名
    private static $aliases = [
        'Router'        => 'phpcan\Router',
        'Model'         => 'phpcan\facade\Model',
        'api\Conf'      => 'phpcan\facade\Conf',
        'api\Rpc'       => 'phpcan\facade\Rpc',
        'api\Log'       => 'phpcan\facade\Log',
        'api\Http'      => 'phpcan\facade\Http',
        'api\Io'        => 'phpcan\facade\Io',
        'api\Mysql'     => 'phpcan\facade\Mysql',
        'api\Redis'     => 'phpcan\facade\Redis',
        'api\Mcq'       => 'phpcan\facade\Mcq',
        'api\Mongodb'   => 'phpcan\facade\Mongodb',
        'api\Influxdb'  => 'phpcan\facade\Influxdb',
        'api\Zookeeper' => 'phpcan\facade\Zookeeper',
        'api\Cache'     => 'phpcan\facade\Cache',
        'api\Soa'       => 'phpcan\facade\Soa',
        'api\Kafka'     => 'phpcan\facade\Kafka',
    ];

    /**
     * 描述：自动加载
     * @param string $class
     * @return bool
     */
    public static function run(string $class)
    {
        // 检测是否是别名
        if (isset(self::$aliases[$class]))
        {
            class_alias(self::$aliases[$class], $class);
            return TRUE;
        }
        // 载入文件映射关系
        if ( ! defined('AUTOLOAD_MAP'))
        {
            self::$map = require 'Map.php';
        }
        // 获取根空间
        $root = (strpos($class, '\\') !== FALSE) ? strstr($class, '\\', TRUE) : $class;
        // 判断是不是框架文件
        if ($root == 'phpcan')
        {
            self::_loadFm($class);
        }
        else
        {
            self::_loadWork($class);
        }
        // 载入vendor自动载入文件
        self::_loadVendor();
    }

    /**
     * 描述：加载框架文件
     * @param string $class
     */
    private static function _loadFm(string $class)
    {
        // 根据映射载入
        if (isset(self::$map[$class]))
        {
            $realFile = _PHPCAN.'/'.self::$map[$class];
        }
        else
        {
            $realFile = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
        }
        if (is_file($realFile))
        {
            require $realFile;
        }
        else
        {
            error(1001, [
                'file' => $realFile,
                'tip'  => '该文件并没有系统映射，请检查书写是否正确'
            ]);
        }
    }

    /**
     * 描述：加载应用文件
     * @param string $class
     */
    private static function _loadWork(string $class)
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        // 设置完整路径
        $realFile = _WORKPATH.DIRECTORY_SEPARATOR._APP.DIRECTORY_SEPARATOR.$class.'.php';
        if (is_file($realFile))
        {
            require $realFile;
        }
        else
        {
            error(1004, [
                'file' => $realFile
            ]);
        }
    }

    /**
     * 描述：载入vendor自动加载文件
     */
    private static function _loadVendor()
    {
        // 载入VENDOR自动载入脚本文件
        if (is_file(_PHPCAN.'/vendor/autoload.php'))
        require _PHPCAN.'/vendor/autoload.php';
    }

}