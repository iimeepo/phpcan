<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/24 14:31
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 提供全局的配置信息
 * ===============================================
 */

return [
    // 默认项目
    'DEFAULT'      => 'demo',
    // 允许访问的项目，如果访问的项目不在以下设置内则使用默认项目，防止被重试
    'ALLOW'        => [
        'demo'
    ],
    // 输入过滤
    'INPUTFILTER'    => 'escape|xss',
    // 开启路由
    'ROUTER'         => FALSE,
    // 是否启用REST自动路由，启用后针对POST、PUT、DELETE可以实现自动路由，无需配置路由规则
    'REST_ROUTER'    => TRUE,
    // REST类型路由操作别名
    'REST_ALIAS'     => [
        'POST'   => 'Add',
        'PUT'    => 'Edit',
        'DELETE' => 'Delete'
    ],
    // 默认缓存类型
    'CACHE'          => 'file',
    // 默认缓存时长，单位秒
    'CACHETIME'      => 600,
    // 默认输出类型
    'RESPONSE'       => 'json',
    // 默认允许的上传文件类型
    'UPLOAD'         => ['jpg', 'gif', 'jpeg', 'png'],
    // HTTP请求最大超时时间
    'HTTP_TIMEOUT'   => 3,
    // HTTP请求最大并发数
    'HTTP_MAXTHREAD' => 10,
    // 提供RPC调用的函数名，函数定义在work/Global.php文件中
    'RPC_FUNC'       => []
];