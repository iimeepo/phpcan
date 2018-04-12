<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/25 10:51
 * 官网：http://www.phpcan.cn
 * ===============================================
 * INFLUXDB类库
 * ===============================================
 */

namespace phpcan\lib;

class Influxdb{

    // 地址
    private $_link;
    // 配置信息
    private $_conf;
    // 操作的数据表
    private $_table;
    // 提交的数据
    private $_message;

    /**
     * Influxdb constructor.
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        // 初始化数据库链接配置信息
        $this->_initConfig($conf);
        $this->_table   = '';
        $this->_message = '';
    }

    /**
     * 描述：设置操作的表
     * @param string $table
     * @return object
     */
    public function from(string $table = '')
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * 描述：添加写入任务
     * @param array $data
     * @param array $tag
     * @return object
     */
    public function add(array $tag = [], array $data = [])
    {
        if ($this->_table == '')
        {
            error(7003);
        }
        if (empty($tag))
        {
            error(7004);
        }
        if (empty($data))
        {
            error(7005);
        }
        // 组装值
        if ($this->_message != '') $this->_message .= "\n";
        $this->_message .= $this->_table.',';
        $doc = '';
        foreach($tag as $key => $val)
        {
            $this->_message .= $doc.$key.'='.$val;
            $doc = ',';
        }
        $this->_message .= ' ';
        $doc = '';
        foreach ($data as $key => $val)
        {
            $this->_message .= $doc.$key.'='.$val;
            $doc = ',';
        }
        return $this;
    }

    /**
     * 描述：执行写入操作
     */
    public function run()
    {
        // 创建连接
        $this->_initLink('write');
        $data = \api\Http::stream($this->_link, $this->_message);
        $this->_message = '';
        return $data;
    }

    /**
     * 描述：查询数据保留策略
     * @return array
     */
    public function policies()
    {
        $this->_initLink('query');
        $data = \api\Http::get($this->_link, [
            'query' => [
                'q' => 'SHOW RETENTION POLICIES ON "'.$this->_conf['DATABASE'].'"'
            ]
        ]);
        $tmp = [];
        if (empty($data['results'][0]))
        {
            return $tmp;
        }
        $data = $data['results'][0]['series'][0];
        foreach ($data['values'] as $key => $row)
        {
            $tmp[$key]['name'] = $row[0];
            $tmp[$key]['duration'] = $row[1];
            $tmp[$key]['shardGroupDuration'] = $row[2];
            $tmp[$key]['replicaN'] = $row[3];
            $tmp[$key]['default'] = $row[4];
        }
        return $tmp;
    }

    /**
     * 描述：创建数据保留策略
     * @param array $params
     * @return array
     */
    public function createPolicies(array $params = [])
    {
        if ( ! isset($params['name']) || $params['name'] == '')
        {
            error(7006);
        }
        $duration = ( ! isset($params['duration'])) ? '30d' : $params['duration'];
        $default  = ( ! isset($params['default']) || ! $params['default']) ? '' : 'DEFAULT';
        $this->_initLink('query');
        $data = \api\Http::post($this->_link, [
            'q' => 'CREATE RETENTION POLICY "'.$params['name'].'" ON "'.$this->_conf['DATABASE'].'" DURATION '.$duration.' REPLICATION 1 '.$default
        ]);
        $data = $data['results'][0];
        return ( ! empty($data)) ? $data['messages'][0]['text'] : TRUE;
    }

    /**
     * 描述：删除数据保存策略
     * @param string $name
     * @return array
     */
    public function delPolicies(string $name = '')
    {
        if ($name == '')
        {
            error(7007);
        }
        $this->_initLink('query');
        $data = \api\Http::post($this->_link, [
            'q' => 'DROP RETENTION POLICY "'.$name.'" ON "'.$this->_conf['DATABASE'].'"'
        ]);
        $data = $data['results'][0];
        return ( ! empty($data)) ? $data['messages'][0]['text'] : TRUE;
    }

    /**
     * 描述：查询
     * @param string $sql
     * @return array
     */
    public function query(string $sql = '')
    {
        if ($sql == '')
        {
            error(7008);
        }
        $this->_initLink('query');
        $data = \api\Http::get($this->_link, [
            'query' => [
                'q' => $sql
            ]
        ]);
        if (isset($data['error']))
            return [
                'code' => 0,
                'msg'  => $data['error']
            ];
        if (empty($data['results'][0]))
            return [
                'code' => 100,
                'msg'  => '查询成功'
            ];
        $data = $data['results'][0]['series'][0];
        $tmp  = [];
        foreach ($data['values'] as $key => $row)
        {
            foreach ($row as $k => $v)
                $tmp[$key][$data['columns'][$k]] = $v;
        }
        return [
            'code' => 100,
            'msg'  => '查询成功',
            'data' => $tmp
        ];
    }

    /**
     * 描述：初始化配置信息
     * @param array $conf
     */
    private function _initConfig($conf = [])
    {
        if (empty($conf))
        {
            //加载配置
            $conf = conf('INFLUXDB');
            if ( ! $conf)
                error(7001);
        }
        if ( ! isset($conf['HOST']))
        {
            error(7002, [
                'config' => 'HOST'
            ]);
        }
        if ( ! isset($conf['DATABASE']))
        {
            error(7002, [
                'config' => 'DATABASE'
            ]);
        }
        $conf['USERNAME'] = ( ! isset($conf['USERNAME']) || ! $conf['USERNAME']) ? '' : $conf['USERNAME'];
        $conf['PASSWORD'] = ( ! isset($conf['PASSWORD']) || ! $conf['PASSWORD']) ? '' : $conf['PASSWORD'];
        $conf['PORT'] = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 8086 : $conf['PORT'];
        $this->_conf = $conf;
    }

    /**
     * 描述：构建数据库地址
     * @param string $action
     */
    private function _initLink(string $action = 'query')
    {
        $this->_link = '';
        $this->_link = 'http://'.$this->_conf['HOST'].':'.$this->_conf['PORT'].'/'.$action;
        if ($this->_conf['USERNAME'] != '' && $this->_conf['PASSWORD'] != '')
            $this->_link .= '?u='.$this->_conf['USERNAME'].'&p='.$this->_conf['PASSWORD'];
        $this->_link .= '&db='.$this->_conf['DATABASE'];
    }

}