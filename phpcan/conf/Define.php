<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/24 14:52
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 系统预定义常量文件，系统运行时所需要的常量均在此定义
 * ===============================================
 */

// 框架版本
define('_VERSION', '1.0.1');

// 框架目录
define('_PHPCAN', ROOTPATH.'/phpcan');

// 调试模式
define('_DEBUG', TRUE);

// 开启SOA支持
define('_SOA', TRUE);

// 网关地址，“/” 结尾
define('_GATEWAY', '');

// 工作目录名称
define('_WORKPATH', ROOTPATH.'/work');

// 缓存目录
define('_CACHE', _PHPCAN.'/runtime/cache');

// 日志目录
define('_LOGDATA', _PHPCAN.'/runtime/log');

// SOA数据存储目录
define('_SOADATA', _PHPCAN.'/runtime/soa');

// 当前是不是RPC调用
define('_RPC', (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] == 'application/hprose') ? TRUE : FALSE);
