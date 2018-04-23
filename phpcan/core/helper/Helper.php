<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/25 15:22
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 助手函数
 * ===============================================
 */

/**
 * 描述：输出错误信息
 * @param $code
 * @param $msg
 * @param array $param
 */
function error($code, $msg = '', array $param = [])
{
    if ( ! $GLOBALS['_LANG'])
    {
        $GLOBALS['_LANG'] = require _PHPCAN.'/lang/Zh-cn.php';
    }
    // 如果是错误编号则从语言包获取错误内容
    $errMsg = (isset($GLOBALS['_LANG'][$code])) ? $GLOBALS['_LANG'][$code] : $msg;
    if (is_array($msg))
    {
        $param = $msg;
    }
    // 替换参数
    if ( ! empty($param))
    {
        foreach($param as $k => $v)
        {
            $errMsg = preg_replace("/\[$k\]/", $v, $errMsg);
        }
    }
    header('Content-type: application/json');
    $err = [
        'code' => $code,
        'msg'  => $errMsg
    ];
    exit(json_encode($err, JSON_UNESCAPED_UNICODE));
}

/**
 * 描述：输出JSON格式数据
 * @param $data
 */
function json($code = 100, $msg = '执行成功', $data = [], $httpcode = 200)
{
    $cont = [];
    if (is_array($code))
    {
        $cont['data'] = $code;
    }
    elseif (is_array($msg))
    {
        $cont['code'] = $code;
        $cont['data'] = $data;
    }
    else
    {
        $cont['code'] = $code;
        $cont['msg']  = $msg;
        $cont['data'] = $data;
    }
    \api\Io::out($cont, $httpcode, 'json');
}

/**
 * 描述：载入核心文件
 * @param $file
 * @return bool
 */
function import($files = [])
{
    if (empty($files))
    {
        return FALSE;
    }
    foreach ($files as $file)
        require ROOTPATH.'/phpcan/'.$file;
}

/**
 * 描述：CLI模式保持进程
 */
function hold()
{
    while(TRUE) sleep(1);
}

/**
 * 描述：解析JSON数据
 * @param string $response
 * @return array
 */
function jsonDecode($response)
{
    $data = json_decode($response, TRUE);
    if (JSON_ERROR_NONE !== json_last_error())
    {
        throw new \InvalidArgumentException(
            'error:'.json_last_error_msg()
        );
    }
    return $data;
}

/**
 * 描述：获取框架版本信息
 */
function version()
{
    echo 'PHPCAN：'._VERSION;
}

/**
 * 描述：生成随机字符串
 * @param string $type
 * @param int $len
 * @return string
 */
function random($type = 'numletter', $len = 4)
{
    //初始化字符串池
    $pool = '';
    //判断类型
    switch ($type)
    {
        //字母数字混合
        case 'numletter':
            $pool = '0123456789abcdefghijklmnopqrstuvwxyz';
            break;
        //纯数字
        case 'num':
            $pool = '0123456789';
            break;
        //纯数字但是不包括0
        case 'numnozero':
            $pool = '123456789';
            break;
        //纯字母
        case 'letter':
            $pool = 'abcdefghijklmnopqrstuvwxyz';
            break;
    }
    //初始化字符串
    $str = '';
    for ($i = 0; $i < $len; $i++)
    {
        $str .= substr($pool, mt_rand(0, strlen($pool) - 1), 1);
    }
    return $str;
}

/**
 * 描述：字符串加密
 * @param string $string
 * @param string $key
 * @return string
 */
function encode($string = '', $key = '67e7f45b468a56f5942df0f1c91a0e2d')
{
    //编码字符串
    $encode_str = base64_encode($string);
    //编码KEY
    $encode_key = base64_encode($key);
    //取得KEY的长度
    $key_length = strlen($encode_key);
    //加密后返回的字符串
    $return_str = '';
    //循环字符串并生成新的加密字符串
    for($i = 0; $i < strlen($encode_str); $i++)
    {
        $return_str .= ($i < $key_length) ? $encode_str[$i].$encode_key[$i] : $encode_str[$i];
    }
    //替换"="，避免还原出错
    return str_replace('=', '@||@', $return_str);
}

/**
 * 描述：字符串解密
 * @param string $string
 * @param string $key
 * @return string
 */
function decode($string = '', $key = '67e7f45b468a56f5942df0f1c91a0e2d')
{
    //还原
    $string = str_split(str_replace('@||@', '=', $string));
    //编码KEY
    $encode_key = str_split(base64_encode($key));
    //取得KEY的长度
    $key_length = count($encode_key);
    //遍历已加密字符
    foreach ($string as $k => $v)
    {
        if ($k >= $key_length)
        {
            break;
        }
        if ( ! isset($string[$k+$k+1]))
        {
            break;
        }
        if ($string[$k+$k+1] == $encode_key[$k])
        {
            unset($string[$k + $k + 1]);
        }
    }
    //反编译
    return base64_decode(implode('', $string));
}

/**
 * 描述：安全的判断数字类型
 * @param $val
 * @return bool
 */
function isInt($val)
{
    return gettype($val) == 'integer';
}

/**
 * 描述：操作配置
 * @param string $key
 * @param null $val
 */
function conf($key = '', $val = null)
{
    if ($key == '')
    {
        return \api\Conf::get();
    }
    if ($key != '' && ! is_null($val))
    {
        \api\Conf::set($key, $val);
        return TRUE;
    }
    return \api\Conf::get($key);
}

/**
 * 描述：写日志
 * @param string $data
 * @param string $path
 */
function logs($data = '', $path = 'default')
{
    \api\Log::save($data, $path);
}

/**
 * 描述：操作缓存
 * @param string $key
 * @param string $val
 * @param int $time
 * @return mixed
 */
function cache($key = '', $val = '', $time = 0)
{
    // 读取缓存
    if ($val === '')
    {
        return \api\Cache::get($key);
    }
    // 删除缓存
    elseif (is_null($val))
    {
        return \api\Cache::del($key);
    }
    // 设置缓存
    else
    {
        return \api\Cache::set($key, $val, $time);
    }
}

/**
 * 描述：获取GET参数
 * @param string $key
 * @param bool $default
 * @param string $replace
 * @return mixed
 */
function get($key = '', $default = FALSE, $replace = '')
{
    return \api\Io::get($key, $default, $replace);
}

/**
 * 描述：获取POST参数
 * @param string $key
 * @param bool $default
 * @param string $replace
 * @return mixed
 */
function post($key = '', $default = FALSE, $replace = '')
{
    return \api\Io::post($key, $default, $replace);
}

/**
 * 描述：获取HEADER参数
 * @param string $key
 */
function httpHeader($key = '', $default = FALSE)
{
    return \api\Io::header($key, $default);
}

/**
 * 描述：返回MYSQL实例
 * @param string $table
 * @return mixed
 */
function mysql($table = '')
{
    return \api\Mysql::from($table);
}

/**
 * 描述：返回MONGODB实例
 * @param string $table
 * @return mixed
 */
function mongodb($table = '')
{
    return \api\Mongodb::from($table);
}

/**
 * 描述：返回REDIS实例
 * @param string $key
 * @param string $action
 */
function redis($key = '', $action = '')
{
    return \api\Redis::key($key, $action);
}