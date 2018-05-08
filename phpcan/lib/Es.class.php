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
 * ES类库
 * ===============================================
 */

namespace phpcan\lib;

class Es{

    // 配置信息
    private $_conf;
    // 句柄
    private $_client;

    private $_select;
    private $_where;
    private $_from;
    private $_order;
    private $_limit;
    private $_len;

    /**
     * Influxdb constructor.
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        // 初始化数据库链接配置信息
        $this->_initConfig($conf);
        // 初始化连接
        $this->_initLink();
        $this->_select = [];
        $this->_where  = [];
        $this->_from   = '';
        $this->_order  = [];
        $this->_limit  = null;
        $this->_len    = 10;
    }

    /**
     * 描述：指定查询字段
     * @param string $select
     */
    public function select(string $select = '')
    {
        if ( ! $select)
        {
            return $this;
        }
        $this->_select = explode(',', $select);
        return $this;
    }

    /**
     * 描述：指定操作的索引
     * @param string $name
     */
    public function from(string $name = '')
    {
        if ($name == '')
        {
            error(9015);
        }
        $this->_from = $name;
        return $this;
    }

    /**
     * 描述：查询条件
     * @param array $where
     */
    public function where(array $where = [])
    {
        if (empty($where))
        {
            return $this;
        }
        $this->_where = [
            'bool' => $where
        ];
        return $this;
    }

    /**
     * 描述：排序规则
     * @param array $order
     */
    public function order(array $order = [])
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * 描述：查询条数
     * @param int $limit
     * @param int $len
     */
    public function limit(int $limit = 10, int $len = 0)
    {
        $this->_len = $limit;
        if ($len !== 0)
        {
            $this->_limit = $limit;
            $this->_len = $len;
        }
        return $this;
    }

    /**
     * 描述：查询单条数据
     */
    public function get($id = '')
    {
        if ( ! $id)
        {
            error(9016);
        }
        $params = [
            'index'  => $this->_from,
            'type'   => 'normal',
            'id'     => $id,
            'client' => [
                'timeout' => $this->_conf['TIMEOUT'],
                'connect_timeout' => $this->_conf['CONNECT_TIMEOUT']
            ]
        ];
        if ( ! empty($this->_select))
        {
            $params['_source_include'] = $this->_select;
        }
        try{
            $result = $this->_client->get($params);
            return $result['_source'];
        }
        catch (\Exception $e)
        {
            error(500, 'ES：'.$e->getMessage());
        }
    }

    /**
     * 描述：查询多条数据
     * @param array $highlight
     */
    public function all(array $highlight = [])
    {
        $body = [];
        // 条件
        if ( ! empty($this->_where))
        {
            $body['query'] = $this->_where;
        }
        $body['highlight'] = [
            'pre_tags'  => ['<span class="es-highlight">'],
            'post_tags' => ['</span>']
        ];
        // 高亮字段
        if ( ! empty($highlight))
        {
            foreach($highlight as $field)
            $body['highlight']['fields'][$field] = new \stdClass();
        }
        // 排序
        if ( ! empty($this->_order))
        {
            $body['sort'] = $this->_order;
        }
        // 条数
        $body['size'] = $this->_len;
        if ($this->_limit != null)
        {
            $body['from'] = $this->_limit;
        }
        $params = [
            'index'  => $this->_from,
            'type'   => 'normal',
            'client' => [
                'timeout' => $this->_conf['TIMEOUT'],
                'connect_timeout' => $this->_conf['CONNECT_TIMEOUT']
            ]
        ];
        if ( ! empty($body))
        {
            $params['body'] = $body;
        }
        if ( ! empty($this->_select))
        {
            $params['_source_include'] = $this->_select;
        }
        try{
            $result = $this->_client->search($params);
            $total  = $result['hits']['total'];
            if ($total == 0)
            {
                return [
                    'TOTAL' => 0
                ];
            }
            $tmp = [];
            foreach ($result['hits']['hits'] as $key => $row)
            {
                $tmp[$key] = $row['_source'];
                $tmp[$key]['_id'] = $row['_id'];
                if (isset($row['highlight']))
                {
                    $highlight = [];
                    foreach ($row['highlight'] as $k => $r)
                        $highlight[$k] = $r[0];
                    $tmp[$key]['highlight'] = $highlight;
                }
            }
            $result = [];
            $result['DATA']  = $tmp;
            $result['TOTAL'] = $total;
            return $result;
        }
        catch(\Exception $e)
        {
            error(500, 'ES：'.$e->getMessage());
        }
    }

    /**
     * 描述：插入数据
     * @param array $data
     * @param bool $many
     */
    public function add(array $data = [], bool $many = FALSE)
    {
        if (empty($data))
        {
            error(9019);
        }
        if ( ! $many)
        {
            $data['timestamp'] = time();
            $params = [
                'index' => $this->_from,
                'type'  => 'normal',
                'body'  => $data
            ];
            try{
                $result = $this->_client->index($params);
                return $result['_id'];
            }
            catch (\Exception $e)
            {
                error(500, 'ES：'.$e->getMessage());
            }
        }
        else
        {
            $params = [];
            foreach ($data as $row)
            {
                $params['body'][] = [
                    'index' => [
                        '_index' => $this->_from,
                        '_type'  => 'normal'
                    ]
                ];
                $row['timestamp'] = time();
                $params['body'][] = $row;
            }
            try{
                $this->_client->bulk($data);
                return count($data);
            }
            catch(\Exception $e)
            {
                error(500, 'ES：'.$e->getMessage());
            }
        }
    }

    /**
     * 描述：更新数据
     * @param array $data
     * @param array $param
     */
    public function edit($id = '', array $data = [])
    {
        if (empty($data))
        {
            error(9019);
        }
        if ($id == '')
        {
            error(9020);
        }
        $params = [
            'index' => $this->_from,
            'type'  => 'normal',
            'id'    => $id
        ];
        $body = [];
        // 更新的数据
        $script = [];
        $inline = '';
        $doc = '';
        foreach ($data as $k => $v)
        {
            $inline .= $doc.'ctx._source.'.$k;
            $inline .= (isInt($v)) ? '='.$v : '="'.$v.'"';
            $doc = ';';
        }
        $script['inline'] = $inline;
        $body['script']   = $script;
        $params['body']   = $body;
        try{
            $result = $this->_client->update($params);
            return $result['_shards']['successful'];
        }
        catch(\Exception $e)
        {
            error(500, 'ES：'.$e->getMessage());
        }
    }

    /**
     * 描述：删除数据
     */
    public function delete()
    {
        $params = [
            'index' => $this->_from,
            'type'  => 'normal'
        ];
        $body = [];
        // 条件
        if ( ! empty($this->_where))
        {
            $body['query'] = $this->_where;
        }
        if ( ! empty($body))
        {
            $params['body'] = $body;
        }
        try{
            $result = $this->_client->deleteByQuery($params);
            return $result['deleted'];
        }
        catch (\Exception $e)
        {
            error(500, 'ES：'.$e->getMessage());
        }
    }

    /**
     * 描述：创建索引
     * @param string $name
     * @param array $field
     * @param array $settings
     */
    public function createIndex(string $name = '', array $field = [], array $settings = [])
    {
        if ($name == '')
        {
            error(9013);
        }
        if (empty($field))
        {
            error(9014);
        }
        $params = [
            'index' => $name,
            'body'  => [
                'mappings' => [
                    'normal' => [
                        '_all'=> [
                            'enabled' => FALSE
                        ],
                        'properties' => $field
                    ]
                ]
            ]
        ];
        // 自定义设置
        if ( ! empty($settings))
        {
            $params['body']['settings'] = $settings;
        }
        try{
            return $this->_client->indices()->create($params);
        }
        catch (\Exception $e)
        {
            return $e->getMessage();
        }
    }

    /**
     * 描述：删除索引
     * @param string $name
     */
    public function deleteIndex(string $name = '')
    {
        $params = [
            'index' => $name
        ];
        return $this->_client
                    ->indices()
                    ->delete($params);
    }

    /**
     * 描述：获取索引映射信息
     * @param string $name
     * @return mixed
     */
    public function getMapping(string $name = '')
    {
        $params = [
            'index'  => $name,
            'client' => [
                'ignore' => 404
            ]
        ];
        return $this->_client
                    ->indices()
                    ->getMapping($params);
    }

    /**
     * 描述：修改mapping信息
     */
    public function putMapping(string $name = '', array $field = [])
    {
        $params = [
            'index' => $name,
            'type'  => 'normal',
            'body'  =>  [
                'normal' => [
                    'properties' => $field
                ]
            ]
        ];
        return $this->_client
                    ->indices()
                    ->putMapping($params);
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
            $conf = conf('ES');
            if ( ! $conf)
                error(9011);
        }
        if ( ! isset($conf['HOSTS']))
        {
            error(9012, [
                'config' => 'HOSTS'
            ]);
        }
        $conf['RETRY'] = ( ! isset($conf['RETRY'])) ? 2 : $conf['RETRY'];
        $conf['CONNECT_TIMEOUT'] = ( ! isset($conf['CONNECT_TIMEOUT'])) ? 10 : $conf['CONNECT_TIMEOUT'];
        $conf['TIMEOUT'] = ( ! isset($conf['TIMEOUT'])) ? 10 : $conf['TIMEOUT'];
        $this->_conf = $conf;
    }

    /**
     * 描述：初始化连接句柄
     */
    private function _initLink()
    {
        $tmp = [];
        foreach ($this->_conf['HOSTS'] as $host)
        {
            $tmp[] = array_change_key_case($host, CASE_LOWER);
        }
        $client = \Elasticsearch\ClientBuilder::create();
        $this->_client = $client->setHosts($tmp)
                                ->setRetries($this->_conf['RETRY'])
                                ->build();
    }

}