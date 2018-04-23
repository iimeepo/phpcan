<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/2/21 10:00
 * 官网：http://www.phpcan.cn
 * ===============================================
 * HTTP操作
 * ===============================================
 */

namespace phpcan;
use Ares333\Curl\Toolkit;

final class Http{

    // 句柄
    private $_client;
    // 请求头
    private $_header = [];
    // 最大超时时间
    private $_timeOut;
    // 并发请求地址
    private $_multi;

    /**
     * Http constructor.
     */
    public function __construct()
    {
        $this->_client = new Toolkit();
        $this->_client = $this->_client->getCurl();
        $this->_client->onInfo = null;
        // 设置最大并发数
        $this->_client->maxThread = conf('HTTP_MAXTHREAD');
        // 初始化相关信息
        $this->_header  = [];
        $this->_timeOut = conf('HTTP_TIMEOUT');
    }

    /**
     * 描述：提供GET操作
     * @param string $url
     * @param array $params
     * @return string|array
     */
    public function get(string $url = '', array $params = [])
    {
        if ($url == '')
        {
            error(6001);
        }
        // 处理BASEURL
        $url = $this->_baseUrl($url, (isset($params['query'])) ? $params['query'] : []);
        // 设置超时时间
        $timeOut = (isset($params['timeout'])) ? $params['timeout'] : $this->_timeOut;
        // 自定义HEADER头
        $header  = (isset($params['header'])) ? $this->_setHeader($params['header']) : $this->_setHeader();
        // 设置调试信息
        $debug = (isset($params['debug'])) ? $params['debug'] : FALSE;
        // 初始化结果
        $response = [];
        // 发送请求
        $this->_client->add([
            'opt' => [
                CURLOPT_URL => $url,
                CURLOPT_TIMEOUT => $timeOut,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_FOLLOWLOCATION => FALSE,
            ]
        ],
        function($result) use (&$response, $debug, $header){
            $response = $this->_response($result['body']);
            // 日志
            \api\Log::add('HTTP', [
                'URL'    => $result['info']['url'],
                'TIME'   => $result['info']['total_time'],
                'METHOD' => 'GET',
                'CODE'   => $result['info']['http_code'],
                'HEADER' => $header
            ]);
            // 调试
            if ($debug) $this->_debug($result, $header);
        },
        function($result) use (&$response){
            // 错误
            $response = $this->_error($result);
        })->start();
        $this->_header = [];
        // 返回数据
        return $response;
    }

    /**
     * 描述：提供POST操作
     * @param string $url
     * @param array $data
     * @param array $params
     * @return string|array
     */
    public function post(string $url = '', array $data = [], array $params = [])
    {
        if ($url == '')
        {
            error(6001);
        }
        // 处理BASEURL
        $url = $this->_baseUrl($url, (isset($params['query'])) ? $params['query'] : []);
        // 设置超时时间
        $timeOut = (isset($params['timeout'])) ? $params['timeout'] : $this->_timeOut;
        // 自定义HEADER头
        $header  = (isset($params['header'])) ? $this->_setHeader($params['header']) : $this->_setHeader();
        // 设置调试信息
        $debug = (isset($params['debug'])) ? $params['debug'] : FALSE;
        // 初始化结果
        $response = [];
        // 发送请求
        $this->_client->add([
            'opt' => [
                CURLOPT_URL => $url,
                CURLOPT_TIMEOUT => $timeOut,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_FOLLOWLOCATION => FALSE,
                CURLOPT_POST => TRUE,
                CURLOPT_POSTFIELDS => http_build_query($data)
            ]
        ],
        function($result) use (&$response, $debug, $header, $data){
            // 格式化POST提交的数据
            foreach ($data as $key => $vval)
            {
                if (strlen($vval) > 255)
                    $data[$key] = '该文本超过255字节，不写入日志';
            }
            $response = $this->_response($result['body']);
            // 日志
            \api\Log::add('HTTP', [
                'URL'    => $result['info']['url'],
                'TIME'   => $result['info']['total_time'],
                'METHOD' => 'POST',
                'CODE'   => $result['info']['http_code'],
                'DATA'   => $data,
                'HEADER' => $header
            ]);
            // 调试
            if ($debug) $this->_debug($result, $header);
        },
        function($result) use (&$response){
            // 错误
            $response = $this->_error($result);
        })->start();
        $this->_header = [];
        // 返回数据
        return $response;
    }

    /**
     * 描述：发送流数据
     * @param string $url
     * @param string $data
     * @param array $params
     * @return string|array
     */
    public function stream(string $url = '', string $data = '', array $params = [])
    {
        if ($url == '')
        {
            error(6001);
        }
        // 处理BASEURL
        $url = $this->_baseUrl($url, (isset($params['query'])) ? $params['query'] : []);
        // 设置超时时间
        $timeOut = (isset($params['timeout'])) ? $params['timeout'] : $this->_timeOut;
        // 自定义HEADER头
        $header  = (isset($params['header'])) ? $this->_setHeader($params['header']) : $this->_setHeader();
        // 设置调试信息
        $debug = (isset($params['debug'])) ? $params['debug'] : FALSE;
        // 初始化结果
        $response = [];
        // 构建流数据
        $stream = fopen('php://temp','r+');
        fwrite($stream, $data);
        $length = ftell($stream);
        rewind($stream);
        // 发送请求
        $this->_client->add([
            'opt' => [
                CURLOPT_URL => $url,
                CURLOPT_TIMEOUT => $timeOut,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_INFILE => $stream,
                CURLOPT_FOLLOWLOCATION => FALSE,
                CURLOPT_INFILESIZE => $length,
                CURLOPT_UPLOAD => 1
            ]
        ],
        function($result) use (&$response, $debug, $header){
            $response = $this->_response($result['body']);
            // 调试
            if ($debug) $this->_debug($result, $header);
        },
        function($result) use (&$response){
            // 错误
            $response = $this->_error($result);
        })->start();
        $this->_header = [];
        // 返回数据
        return $response;
    }

    /**
     * 描述：添加并发任务
     * @param string $key
     * @param array $params
     * @return object
     */
    public function add(string $key = '', array $params = [])
    {
        if ( ! isset($params))
        {
            error(6001);
        }
        $this->_multi[$key] = [
            'url'     => $params['url'],
            'query'   => (isset($params['query'])) ? $params['query'] : [],
            'post'    => (isset($params['post'])) ? $params['post'] : [],
            'timeout' => (isset($params['timeout'])) ? $params['timeout'] : $this->_timeOut,
            'header'  => (isset($params['header'])) ? $params['header'] : []
        ];
        return $this;
    }

    /**
     * 描述：执行并发任务
     */
    public function run()
    {
        if (empty($this->_multi))
        {
            error(6003);
        }
        if (count($this->_multi) == 1)
        {
            error(6004);
        }
        $response = [];
        foreach ($this->_multi as $key => $row)
        {
            $method = (isset($row['post']) && ! empty($row['post'])) ? 'POST' : 'GET';
            $opt = [];
            $opt['opt'] = [
                CURLOPT_URL => $this->_baseUrl($row['url'], $row['query']),
                CURLOPT_TIMEOUT => $row['timeout'],
                CURLOPT_FOLLOWLOCATION => FALSE,
                CURLOPT_HTTPHEADER => $this->_setHeader($row['header']),
                CURLOPT_CUSTOMREQUEST => $method
            ];
            if ($method == 'POST')
            {
                $opt['opt'][CURLOPT_POST] = TRUE;
                $opt['opt'][CURLOPT_POSTFIELDS] = http_build_query($row['post']);
            }
            $this->_client->add($opt,
                function($result) use (&$response, $key){
                    $response[$key] = $this->_response($result['body']);
                },
                function($result){
                    $this->_error($result);
                });
        }
        $this->_client->start();
        $this->_multi = [];
        return $response;
    }

    /**
     * 描述：设置HEADER头
     * @param array $header
     * @return array
     */
    private function _setHeader(array $header = [])
    {
        $arr = $this->_header;
        if ( ! empty($header))
        {
            $arr = array_merge($arr, $header);
        }
        $tmp = [];
        foreach ($arr as $k => $v)
            $tmp[] = $k.':'.$v;
        unset($arr);
        return $tmp;
    }

    /**
     * 描述：处理根域名
     * @param string $url
     * @param array $query
     * @return string
     */
    private function _baseUrl(string $url = '', array $query = [])
    {
        // 解析参数
        if ( ! empty($query))
        {
            $doc = (strpos($url, '?') !== FALSE) ? '&' : '?';
            foreach($query as $k => $v)
            {
                $url .= $doc.$k.'='.$v;
                $doc = '&';
            }
        }
        return $url;
    }

    /**
     * 描述：输出结果
     * @param $response
     * @return string
     */
    private function _response($response)
    {
        if (conf('RESPONSE') == 'html')
        {
            return $response;
        }
        try{
            $data = jsonDecode($response);
        }
        catch (\Exception $e)
        {
            $data = [];
            $data['code'] = 100;
            $data['msg']  = '不是标准的JSON数据，已原样输出';
            $data['data'] = $response;
        }
        return $data;
    }

    /**
     * 描述：输出调试信息
     * @param array $result
     * @param array $header
     */
    private function _debug(array $result, array $header)
    {
        // 创建输出格式
        $html  = '<style>';
        $html .= '.phpcan-http-debug{background: #F5F5F5; padding: 10px; border: 1px #DDD solid;}';
        $html .= '.phpcan-http-debug p{font-size:12px; margin:5px 0;}';
        $html .= '.phpcan-http-debug h4,h6{margin:10px 0 0 0;}';
        $html .= '</style>';
        $html .= '<div class="phpcan-http-debug">';
        $html .= '<h4>HTTP请求调试：'.$result['info']['url'].'</h4>';
        $html .= '<p>文档类型：'.$result['info']['content_type'].'</p>';
        $html .= '<p>状态码：'.$result['info']['http_code'].'</p>';
        $html .= '<p>请求耗时：'.(($result['info']['total_time'] == 0) ? '0.001' : $result['info']['total_time']).' ms</p>';
        $html .= '<p>文档大小：'.$result['info']['size_download'].' 字节</p>';
        $html .= '<p>返回结果：'.html_entity_decode($result['body']).'</p>';
        $html .= '<h6>HEADER头信息</h6>';
        foreach ($header as $val)
            $html .= '<p>'.$val.'</p>';
        $html .= '</div>';
        echo $html;
    }

    /**
     * 描述：错误处理
     * @param array $result
     * @return string
     */
    private function _error(array $result = [])
    {
        $result = [
            'error' => $result['errorCode'],
            'msg'   => $result['errorMsg']
        ];
        return $this->_response(json_encode($result));
    }

}