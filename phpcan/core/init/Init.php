<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/29 15:09
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 框架初始化
 * ===============================================
 */

namespace phpcan;

class Init{

    /**
     * 描述：执行初始化操作
     */
    public function run()
    {
        require 'welcome.php';
        if (is_file(_PHPCAN.'/runtime/init.lock'))
        {
            return TRUE;
        }
        // 创建相关目录
        $path = './work/' . conf('DEFAULT');
        if ( ! mkdir($path))
        {
            error(1003, [
                'path' => $path
            ]);
        }
        // 批量创建目录
        $dir = [
            'conf',
            'middleware',
            'model',
            'router',
            'spi'
        ];
        foreach ($dir as $d)
        {
            mkdir($path.'/'.$d);
        }
        // 创建空文件防止直接访问
        file_put_contents($path.'/index.html', '');
        // 通用头部
        $header  = "<?PHP\r\n";
        $header .= "\r\n";
        $header .= "/**\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * PHPCAN微服务框架 - docker版本\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * 版本：PHP7.0 +\r\n";
        $header .= " * 作者: \r\n";
        $header .= " * 日期: ".date('Y-m-d H:i')."\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * [msg]\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " */\r\n\r\n";
        // 批量创建配置文件
        $conf = [
            'Conf.php',
            'Mcq.php',
            'Mongodb.php',
            'Mysql.php',
            'Redis.php',
            'Zookeeper.php',
            'Influxdb.php',
            'Kafka.php'
        ];
        foreach ($conf as $file)
        {
            switch ($file)
            {
                case 'Conf.php':
                    $headers = str_replace('[msg]', '服务自定义配置文件', $header);
                break;
                case 'Mcq.php':
                    $headers = str_replace('[msg]', 'MCQ配置文件', $header);
                break;
                case 'Mongodb.php':
                    $headers = str_replace('[msg]', 'MONGODB配置文件', $header);
                break;
                case 'Mysql.php':
                    $headers = str_replace('[msg]', 'MYSQL配置文件', $header);
                break;
                case 'Redis.php':
                    $headers = str_replace('[msg]', 'REDIS配置文件', $header);
                break;
                case 'Zookeeper.php':
                    $headers = str_replace('[msg]', 'ZOOKEEPER配置文件', $header);
                break;
                case 'Influxdb.php':
                    $headers = str_replace('[msg]', 'INFLUXDB配置文件', $header);
                break;
                case 'Kafka.php':
                    $headers = str_replace('[msg]', 'KAFKA配置文件', $header);
                break;
            }
            $content  = $headers."return [\r\n";
            switch ($file)
            {
                case 'Conf.php':
                    $content .= "    // 实例的命名空间，在SOA系统中查看\r\n";
                    $content .= "    'EXAMPLE'     => 'demo',\r\n";
                    $content .= "    // 当前的环境，0 测试 1 生产\r\n";
                    $content .= "    'ENVIRONMENT' => 0,\r\n";
                    $content .= "    // 输入过滤\r\n";
                    $content .= "    'INPUTFILTER' => 'escape|xss',\r\n";
                    $content .= "    // 开启路由\r\n";
                    $content .= "    'ROUTER'      => TRUE,\r\n";
                    $content .= "    // 默认中间件\r\n";
                    $content .= "    'MIDDLEWARE' => [\r\n";
                    $content .= "        'BEFORE' => [],\r\n";
                    $content .= "        'AFTER'  => []\r\n";
                    $content .= "    ],\r\n";
                    $content .= "    // 默认缓存类型，支持 file、redis\r\n";
                    $content .= "    'CACHE'      => 'file',\r\n";
                    $content .= "    // 默认缓存时长，单位秒\r\n";
                    $content .= "    'CACHETIME'  => 600,\r\n";
                    $content .= "    // 默认输出类型\r\n";
                    $content .= "    'RESPONSE'   => 'json',\r\n";
                    $content .= "    // 当前服务版本\r\n";
                    $content .= "    'VERSION'    => '1.0.0',\r\n";
                    $content .= "    // HTTP请求最大超时时间\r\n";
                    $content .= "    'HTTP_TIMEOUT' => 3.0\r\n";
                break;
                case 'Mcq.php':
                    $content .= "    // MCQ地址\r\n";
                    $content .= "    'HOST'    => '',\r\n";
                    $content .= "    // MCQ端口\r\n";
                    $content .= "    'PORT'    => '11212',\r\n";
                    $content .= "    // KEY统一前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'  => '',\r\n";
                    $content .= "    // 连接超时时间\r\n";
                    $content .= "    'TIMEOUT' => 1\r\n";
                break;
                case 'Mongodb.php':
                    $content .= "    // MONGODB地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // MONGODB账号\r\n";
                    $content .= "    'USERNAME' => '',\r\n";
                    $content .= "    // MONGODB密码\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // 端口号\r\n";
                    $content .= "    'PORT'     => '27017',\r\n";
                    $content .= "    // 数据库名称\r\n";
                    $content .= "    'DATABASE' => '',\r\n";
                    $content .= "    // 数据表前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'   => ''\r\n";
                break;
                case 'Mysql.php':
                    $content .= "    // 数据库地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // 数据库账号\r\n";
                    $content .= "    'USERNAME' => '',\r\n";
                    $content .= "    // 数据库密码\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // 数据库端口\r\n";
                    $content .= "    'PORT'     => '3306',\r\n";
                    $content .= "    // 字符集\r\n";
                    $content .= "    'CHARSET'  => 'utf-8',\r\n";
                    $content .= "    // 数据库名称\r\n";
                    $content .= "    'DATABASE' => '',\r\n";
                    $content .= "    // 数据表前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'   => ''\r\n";
                break;
                case 'Redis.php':
                    $content .= "    // REDIS地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // REDIS密码，如果没有可为空\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // REDIS端口\r\n";
                    $content .= "    'PORT'     => '6379',\r\n";
                    $content .= "    // 连接超时时间\r\n";
                    $content .= "    'TIMEOUT'  => 3,\r\n";
                    $content .= "    // 库序号，0-15，默认0\r\n";
                    $content .= "    'DATABASE' => 0,\r\n";
                    $content .= "    // KEY统一前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'   => ''\r\n";
                break;
                case 'Zookeeper.php':
                    $content .= "    // 地址\r\n";
                    $content .= "    'HOST'    => '',\r\n";
                    $content .= "    // 端口\r\n";
                    $content .= "    'PORT'    => '2181',\r\n";
                    $content .= "    // 连接超时时间\r\n";
                    $content .= "    'TIMEOUT' => 3\r\n";
                break;
                case 'Influxdb.php':
                    $content .= "    // 数据库地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // 数据库账号\r\n";
                    $content .= "    'USERNAME' => '',\r\n";
                    $content .= "    // 数据库密码\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // 数据库端口\r\n";
                    $content .= "    'PORT'     => '8086',\r\n";
                    $content .= "    // 数据库名称\r\n";
                    $content .= "    'DATABASE' => ''\r\n";
                break;
                case 'Kafka.php':
                    $content .= "    // 地址\r\n";
                    $content .= "    'HOST' => '',\r\n";
                    $content .= "    // 端口\r\n";
                    $content .= "    'PORT' => '9092'\r\n";
                break;
            }
            $content .= "\r\n];";
            file_put_contents($path.'/conf/'.$file, $content);
        }
        // 生成默认中间件文件
        $file     = 'Middleware.php';
        $headers  = str_replace('[msg]', '中间件', $header);
        $content  = $headers;
        $content .= "namespace middleware;\r\n";
        $content .= "\r\n";
        $content .= "class Middleware{\r\n";
        $content .= "\r\n";
        $content .= "}\r\n";
        file_put_contents($path.'/middleware/'.$file, $content);
        // 生成默认路由文件
        $file     = 'Router.php';
        $headers  = str_replace('[msg]', '路由', $header);
        $content  = $headers;
        file_put_contents($path.'/router/'.$file, $content);
        // 生成Init.php文件
        $file      = 'Init.php';
        $headers   = str_replace('[msg]', '', $header);
        $content   = $headers;
        $content  .= "namespace spi;\r\n";
        $content  .= "\r\n";
        $content  .= "class Init{\r\n";
        $content  .= "\r\n";
        $content  .= "    public function __construct()\r\n";
        $content  .= "    {\r\n";
        $content  .= "        \r\n";
        $content  .= "    }\r\n";
        $content  .= "}\r\n";
        file_put_contents($path.'/spi/'.$file, $content);
        // 创建锁定文件
        file_put_contents(_PHPCAN.'/runtime/init.lock', '');
    }

}