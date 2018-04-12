<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/2/21 10:51
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 日志
 * ===============================================
 */

namespace phpcan;

class Log{

    /**
     * 描述：保存日志
     * @param string $data
     * @param string $path
     */
    public function save(string $data, string $path = 'default')
    {
        $path = _LOGDATA.'/'.$path;
        if ( ! is_dir($path))
            mkdir($path, 0777);
        $logFile  = $path.'/'.date('Ymd').'.log';
        $logData  = '日志写入时间：'.date('Y-m-d H:i:s')."\r\n";
        $logData .= $data;
        $logData .= "\r\n=====================================\r\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
    }

    /**
     * 描述：增加日志
     * @param string $name
     * @param string $data
     */
    public function add(string $name, array $data)
    {
        if ( ! isset($GLOBALS['_LOGS'][$name]))
        {
            $GLOBALS['_LOGS'][$name] = [];
        }
        array_push($GLOBALS['_LOGS'][$name], $data);
    }

    /**
     * 描述：输出LOG
     * @param string $name
     */
    public function info(string $name = '')
    {
        return ($name == '') ? $GLOBALS['_LOGS'] : $GLOBALS['_LOGS'][$name];
    }

}