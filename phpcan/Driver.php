<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/24 13:41
 * 官网：http://www.phpcan.cn
 * ===============================================
 * PHPCAN驱动文件，如无必要请不要修改此文件
 * ===============================================
 */

/*
 * 初始化GLOBALS
 */

// 运行时产生的一些数据
$GLOBALS['_RUNTIME'] = [
    'MICROTIME' => microtime(TRUE)
];
// 日志埋点的一些数据
$GLOBALS['_LOGS'] = [];
// 配置信息
$GLOBALS['_CONF'] = [];
// 语言
$GLOBALS['_LANG'] = [];

// 载入系统常量
require ROOTPATH . '/phpcan/conf/Define.php';
// 载入公用函数
require _WORKPATH . '/Global.php';

// 设置时区
date_default_timezone_set('PRC');
// 开启报错信息
error_reporting(_DEBUG ? E_ALL : 0);

// 载入IOC容器
require _PHPCAN . '/core/ioc/Di.php';
// 运行容器
phpcan\Di::run();