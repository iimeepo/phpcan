<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/3/12 15:24
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 缓存类
 * ===============================================
 */

namespace phpcan;

class Cache{

    // 缓存类型
    private $_cacheType;
    // 缓存时长
    private $_cacheTime;

    public function __construct()
    {
        $this->_cacheType = conf('CACHE');
        $this->_cacheTime = conf('CACHETIME');
    }

    /**
     * 描述：读取缓存
     * @param string $key
     */
    public function get(string $key = '')
    {
        $cache = ($this->_cacheType == 'file') ? $this->_file($key) :
            $this->_redis($key);
        // 尝试json解析
        try
        {
            return jsonDecode($cache);
        }
        catch(\InvalidArgumentException $exception)
        {
            return $cache;
        }
    }

    /**
     * 描述：设置缓存
     * @param string $key
     * @param $val
     * @param $time
     * @return string
     */
    public function set(string $key = '', $val = '', $time = FALSE)
    {
        $time = ($time === FALSE) ? $this->_cacheTime : $time;
        $val  = (is_array($val)) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
        return ($this->_cacheType == 'file') ? $this->_file($key, $val, $time) :
            $this->_redis($key, $val, $time);
    }

    /**
     * 描述：删除缓存
     * @param string $key
     */
    public function del(string $key = '')
    {
        return ($this->_cacheType == 'file') ? $this->_file($key, null) :
            $this->_redis($key, null);
    }

    /**
     * 描述：清空缓存
     */
    public function clear(string $keys = '*')
    {
        if ($this->_cacheType == 'file')
        {
            error(1010);
        }
        \api\Redis::key($keys)->clear();
        return TRUE;
    }

    /**
     * 描述：
     * @param string $key
     * @param $val
     * @param int $time
     * @return bool|string
     */
    private function _file(string $key = '', $val = '', int $time = 600)
    {
        // 缓存目录
        if ($key == '')
        {
            return FALSE;
        }
        $doc = '';
        // HASH缓存目录
        $pathArr = array_slice(str_split($hash = md5($key), 2), 0, 2);
        $cache = _CACHE.'/';
        foreach ($pathArr as $p)
        {
            $cache .= $doc.$p;
            if ( ! is_dir($cache)) mkdir($cache, 0777);
            $doc = '/';
        }
        $cache .= '/'.$hash.'.php';
        // 读取缓存
        if ($val === '')
        {
            $stime = microtime(TRUE);
            if ( ! is_file($cache))
            {
                return FALSE;
            }
            $data = include($cache);
            $cacheTime = $data[1];
            $etime = microtime(TRUE);
            // 日志
            \api\Log::add('FILE', [
                'KEY'  => $key,
                'FILE' => $cache,
                'TIME' => round($etime - $stime, 4),
                'TYPE' => 'read'
            ]);
            if ($cacheTime == 0)
            {
                return $data[0];
            }
            if (time() >= $cacheTime)
            {
                return FALSE;
            }
            return $data[0];
        }
        // 删除缓存
        elseif (is_null($val))
        {
            if ( ! is_file($cache))
            {
                return FALSE;
            }
            @unlink($cache);
            return $cache;
        }
        // 设置缓存
        else
        {
            if ($val == '' OR empty($val))
            {
                return FALSE;
            }
            if (is_array($val))
            {
                $data = [];
                foreach ($val as $k => $v)
                {
                    if (strlen($v) > 255)
                        $data[$k] = '该文本超过255字节，不写入日志';
                    else
                        $data[$k] = $v;
                }
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            $logData = [
                'TYPE'  => 'write',
                'DATA'  => isset($data) ? $data : $val,
                'KEY'   => $key,
                'CTIME' => $time
            ];
            $stime = microtime(TRUE);
            $time = ($time == 0) ? 0 : time() + $time;
            $val = '<?PHP return '.var_export([$val, $time], TRUE).';';
            file_put_contents($cache, $val);
            $etime = microtime(TRUE);
            $logData['TIME'] = round($etime - $stime, 4);
            // 日志
            \api\Log::add('FILE', $logData);
            unset($logData);
            return $cache;
        }
    }

    /**
     * 描述：redis缓存
     * @param string $key
     * @param $val
     * @param int $time
     * @return bool
     */
    private function _redis(string $key = '', $val = '', $time = 600)
    {
        // 读取缓存
        if ($val === '')
        {
            return \api\Redis::key($key)->get();
        }
        // 删除缓存
        elseif (is_null($val))
        {
            return \api\Redis::key($key)->del();
        }
        // 设置缓存
        else
        {
            if ($time === 0)
            {
                return \api\Redis::key($key)->set($val);
            }
            else
            {
                return \api\Redis::key($key, 'setex')->set($time, $val);
            }
        }
    }

}