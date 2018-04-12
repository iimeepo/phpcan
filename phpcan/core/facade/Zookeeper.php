<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/26 17:39
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 外观文件
 * ===============================================
 */

namespace phpcan\facade;
use phpcan\Facade;

class Zookeeper extends Facade {

    public static function getFacadeAccessor()
    {
        return 'phpcan\lib\Zookeeper';
    }

}