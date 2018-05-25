<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/26 9:54
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 文件映射关系
 * ===============================================
 */

define('AUTOLOAD_MAP', 1);

return [
    'phpcan\Facade' => 'core/facade/Facade.php',
    'phpcan\Conf' => 'core/conf/Conf.php',
    'phpcan\facade\Conf' => 'core/facade/Conf.php',
    'phpcan\Router' => 'core/router/Router.php',
    'phpcan\facade\Model' => 'core/facade/Model.php',
    'phpcan\Model' => 'core/model/Model.php',
    'phpcan\Http' => 'core/http/Http.php',
    'phpcan\facade\Http' => 'core/facade/Http.php',
    'phpcan\Rpc' => 'core/rpc/Rpc.php',
    'phpcan\facade\Rpc' => 'core/facade/Rpc.php',
    'phpcan\Log' => 'core/log/Log.php',
    'phpcan\facade\Log' => 'core/facade/Log.php',
    'phpcan\Middleware' => 'core/middleware/Middleware.php',
    'phpcan\facade\Middleware' => 'core/facade/Middleware.php',
    'phpcan\Controller' => 'core/controller/Controller.php',
    'phpcan\Init' => 'core/init/Init.php',
    'phpcan\facade\Io' => 'core/facade/Io.php',
    'phpcan\lib\Io' => 'lib/Io.class.php',
    'phpcan\facade\Mysql' => 'core/facade/Mysql.php',
    'phpcan\lib\Mysql' => 'lib/Mysql.class.php',
    'phpcan\facade\Redis' => 'core/facade/Redis.php',
    'phpcan\lib\Redis' => 'lib/Redis.class.php',
    'phpcan\facade\Kafka' => 'core/facade/Kafka.php',
    'phpcan\lib\Kafka' => 'lib/Kafka.class.php',
    'phpcan\facade\Mcq' => 'core/facade/Mcq.php',
    'phpcan\lib\Mcq' => 'lib/Mcq.class.php',
    'phpcan\facade\Rmq' => 'core/facade/Rmq.php',
    'phpcan\lib\Rmq' => 'lib/Rmq.class.php',
    'phpcan\facade\Mongodb' => 'core/facade/Mongodb.php',
    'phpcan\lib\Mongodb' => 'lib/Mongodb.class.php',
    'phpcan\facade\Influxdb' => 'core/facade/Influxdb.php',
    'phpcan\lib\Influxdb' => 'lib/Influxdb.class.php',
    'phpcan\facade\Zookeeper' => 'core/facade/Zookeeper.php',
    'phpcan\lib\Zookeeper' => 'lib/Zookeeper.class.php',
    'phpcan\facade\Es' => 'core/facade/Es.php',
    'phpcan\lib\Es' => 'lib/Es.class.php',
    'phpcan\facade\Upload' => 'core/facade/Upload.php',
    'phpcan\lib\Upload' => 'lib/Upload.class.php',
    'phpcan\Cache' => 'core/cache/Cache.php',
    'phpcan\facade\Cache' => 'core/facade/Cache.php',
    'phpcan\Soa' => 'core/soa/Soa.php',
    'phpcan\facade\Soa' => 'core/facade/Soa.php',
];