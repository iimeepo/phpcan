<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/26 11:23
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 配置文件类
 * ===============================================
 */

namespace phpcan;

class Conf{

    public function __construct()
    {
        if ( ! $GLOBALS['_CONF'])
            $GLOBALS['_CONF'] = require _PHPCAN.'/conf/Conf.php';
    }

    /**
     * 描述：获取配置信息
     * @param string $key
     * @return bool
     */
    public function get(string $key = '')
    {
        if ($key == '')
        {
            return $GLOBALS['_CONF'];
        }
        // 如果已经存在则直接返回
        if (isset($GLOBALS['_CONF'][$key]))
        {
            return $GLOBALS['_CONF'][$key];
        }
        // 如果开启SOA支持则先从配置中心读取配置
        if ( ! defined('_CLI') && _SOA)
        {
            $file = _SOADATA.'/soa-'.EID.'-'.ENV.'.php';
            if (is_file($file))
            {
                $conf = require $file;
                if (is_array($conf))
                $this->merge($conf);
            }
            // 如果SOA配置中存在KEY值则返回
            if (isset($GLOBALS['_CONF'][$key]))
            {
                return $GLOBALS['_CONF'][$key];
            }
        }
        // SOA中没有找到配置信息则从本地配置文件中读取
        $file = './work/'._APP.'/conf/'.(ucfirst(strtolower($key))).'.php';
        if (is_file($file))
        {
            $conf = require $file;
            $GLOBALS['_CONF'][strtoupper($key)] = $conf;
            return $conf;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * 描述：设置配置信息
     * @param string $key
     * @param $val
     */
    public function set(string $key, $val)
    {
        $GLOBALS['_CONF'][strtoupper($key)] = $val;
    }

    /**
     * 描述：合并配置信息
     * @param array $conf
     */
    public function merge(array $conf)
    {
        if ( ! empty($conf))
            $GLOBALS['_CONF'] = array_merge($GLOBALS['_CONF'], $conf);
    }

}