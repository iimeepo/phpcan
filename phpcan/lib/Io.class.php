<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/24 15:00
 * 官网：http://www.phpcan.cn
 * ===============================================
 * 输入输出类
 * ===============================================
 */

namespace phpcan\lib;

class Io{

    /**
     * 描述：获取POST参数
     * @param string $key
     * @param bool $default
     * @param string $replace
     * @return string|array
     */
    public function post(string $key = '', bool $default = FALSE, string $replace = '')
    {
        // 如果没有设置KEY，则返回所有POST值
        if ($key == '')
        {
            $post = $_POST;
        }
        else
        {
            if ( ! isset($_POST[$key]))
            return ($default !== FALSE) ? $default : FALSE;
            $post = $_POST[$key];
        }
        // 如果是整型或者是空则直接返回
        if (isInt($post) || empty($post))
        {
            return $post;
        }
        // 如果是数组则遍历
        if (is_array($post))
        {
            $tmp = [];
            foreach ($post as $val)
            $tmp[] = (is_int($val) || empty($val)) ? $val : $this->_replace(trim($val), $replace);
            return $tmp;
        }
        else
        {
            return $this->_replace(trim($post), $replace);
        }
    }

    /**
     * 描述：获取GET参数
     * @param string $key
     * @param bool $default
     * @param string $replace
     * @return string|array
     */
    public function get(string $key = '', bool $default = FALSE, string $replace = '')
    {
        // 如果没有设置KEY，则返回所有POST值
        if ($key == '')
        {
            $get = $_GET;
        }
        else
        {
            if ( ! isset($_GET[$key]))
                return ($default !== FALSE) ? $default : FALSE;
            $get = $_GET[$key];
        }
        // 如果是整型或者空则直接返回
        if (isInt($get) || empty($get))
        {
            return $get;
        }
        // 如果是数组则遍历
        if (is_array($get))
        {
            $tmp = [];
            foreach ($get as $val)
                $tmp[] = (is_int($val) || empty($val)) ? $val : $this->_replace(trim($val), $replace);
            return $tmp;
        }
        else
        {
            return $this->_replace(trim($get), $replace);
        }
    }

    /**
     * 描述：获取HEADER参数
     * @param string $key
     * @param bool $default
     * @return array|string
     */
    public function header(string $key = '', bool $default = FALSE)
    {
        // 如果KEY为空，则返回所有
        if ($key == '')
        {
            $headers = [];
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                    $headers[str_replace('HTTP_', '', $name)] = $value;
            }
            return $headers;
        }
        $key = 'HTTP_'.strtoupper($key);
        if ( ! isset($_SERVER[$key]))
        {
            return ($default !== FALSE) ? $default : FALSE;
        }
        return trim($_SERVER[$key]);
    }

    /**
     * 描述：输出
     * @param array $content
     * @param int $code
     * @param string $type
     */
    public function out(array $content = [], int $code = 200, string $type = '')
    {
        $type = ($type == '') ? conf('RESPONSE') : $type;
        $contentType  = '';
        $data['code'] = ( ! isset($content['code'])) ? 100 : $content['code'];
        $data['msg']  = ( ! isset($content['msg'])) ? '执行成功' : $content['msg'];
        $data['data'] = ( ! isset($content['data'])) ? [] : $content['data'];
        // 如果开启SOA支持则返回日志
        if (_SOA)
        {
            \api\Log::add('FRAMEWORK', [
                'TOTALTIME' => round(microtime(TRUE) - $GLOBALS['_RUNTIME']['MICROTIME'], 4)
            ]);
            $data['log'] = \api\Log::info();
        }
        switch ($type)
        {
            case 'json':
                $contentType = 'application/json';
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                if ( ! $data && _DEBUG)
                    throw new \InvalidArgumentException(json_last_error_msg());
            break;
        }
        header('Content-type: '.$contentType);
        if ( ! headers_sent())
        {
            // 发送状态码
            http_response_code($code);
        }
        echo $data;
    }

    /**
     * 描述：执行输入过滤
     * @param $val
     * @param string $conf
     * @return string
     */
    private function _replace($val, $conf = '')
    {
        // 过滤规则
        $conf  = ($conf == '') ? conf('INPUTFILTER') : $conf;
        // 过滤配置
        $rules = explode('|', $conf);
        foreach ($rules as $rule)
        {
            if ($rule == 'escape')
            {
                $val = $this->_escape($val);
            }
            else
            {
                $val = $this->_xssClear($val);
            }
        }
        return $val;
    }

    /**
     * 描述：字符转义
     * @param $val
     * @return string
     */
    private function _escape($val)
    {
        if ( ! get_magic_quotes_gpc())
        {
            $val = addslashes($val);
        }
        return strip_tags($val);
    }

    /**
     * 描述：XSS注入清理
     * @param $val
     * @return string
     */
    private function _xssClear($val)
    {
        $val     = rawurldecode($val);
        $search  = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++)
        {
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val);
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val);
        }
        // 屏蔽危险DOM
        $dom = [
            'javascript', 'vbscript', 'expression', 'applet', 'meta',
            'xml', 'blink', 'link', 'script', 'embed',
            'object', 'iframe', 'frame', 'frameset', 'ilayer',
            'layer', 'bgsound', 'base'
        ];
        foreach ($dom as $d)
        {
            $val = preg_replace('/[<|&lt;]+'.$d.'(.*)[>|&gt;]+(.*)[<|&lt;]+\/'.$d.'[>|&gt;]+/', '', $val);
        }
        // 屏蔽事件
        $event = [
            'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate',
            'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste',
            'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce',
            'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect',
            'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete',
            'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter',
            'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror',
            'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin',
            'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup',
            'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter',
            'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup',
            'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste',
            'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend',
            'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted',
            'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart',
            'onstop', 'onsubmit', 'onunload'
        ];
        $val = preg_replace('/'.implode('|', $event).'/is', '', $val);
        // 字符串黑名单
        $black = [
            'document.cookie' => '',
            'document.write'  => '',
            '.parentNode'     => '',
            '.innerHTML'      => '',
            'window.location.href' => '',
            'location.href'   => '',
            '-moz-binding'    => '',
            'alert'           => '',
            '<!--'            => '&lt;!--',
            '-->'             => '--&gt;',
            '<![CDATA['       => '&lt;![CDATA[',
            '<comment>'       => '&lt;comment&gt;',
        ];
        // 替换黑名单字符串
        $val = str_replace(array_keys($black), array_values($black), $val);
        return $val;
    }

}