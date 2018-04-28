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
// 捕获系统异常
register_shutdown_function('shutdown');
// 异常回调
function shutdown()
{
    $error = error_get_last();
    // 捕获致命异常
    if ( ! empty($error) && $error['type'] <= 4)
    {
        $data = [
            'code' => 500,
            'msg'  => '系统发生致命错误',
            'data' => []
        ];
        if (_SOA)
        {
            \api\Log::add('FRAMEWORK', [
                'TOTALTIME' => round(microtime(TRUE) - $GLOBALS['_RUNTIME']['MICROTIME'], 4)
            ]);
            \api\Log::add('ERROR', [
                'TYPE' => $error['type'],
                'MSG'  => $error['message'],
                'FILE' => $error['file'],
                'LINE' => $error['line']
            ]);
            $data['log'] = \api\Log::info();
        }
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
// 载入IOC容器
require _PHPCAN . '/core/ioc/Di.php';
// 运行容器
phpcan\Di::run();