<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/1/24 14:59
 * 官网：http://www.phpcan.cn
 * ===============================================
 * MONGODB类库
 * ===============================================
 */

namespace phpcan\lib;
use MongoDB\Client;

class Mongodb{

    // 连接句柄
    private $_conn;
    // 配置信息
    private $_conf;

    /*
     * MONGODB语句的各个小模块
     */
    private $_select;
    private $_from;
    private $_where;
    private $_order;
    private $_limit = 0;
    private $_skip = 0;

    /**
     * Mongo constructor.
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        // 初始化数据库链接配置信息
        $this->_initConfig($conf);
        // 链接数据库
        $this->_linkDb();
        $this->_where = [];
    }

    /**
     * 描述：指定查询字段
     * @return object
     */
    public function select(string $field = '')
    {
        $this->_select = [];
        if ($field != '')
        {
            $arr = explode(',', $field);
            foreach ($arr as $val)
                $this->_select[trim($val)] = 1;
        }
        return $this;
    }

    /**
     * 描述：指定数据表
     * @param string $table
     * @return object
     */
    public function from(string $table = '')
    {
        $this->_from = $table;
        return $this;
    }

    /**
     * 描述：指定查询条件
     * @param array $cond
     */
    public function where(array $cond = [])
    {
        $tmp = [];
        $map = [
            '>'     => '$gt',
            '<'     => '$lt',
            '>='    => '$gte',
            '<='    => '$lte',
            '<>'    => '$ne',
            'IN'    => '$in',
            'NOTIN' => '$nin'
        ];
        foreach ($cond as $k => $v)
        {
            $k = trim($k);
            if ($k == '_id')
            {
                $tmp[$k] = new \MongoDB\BSON\ObjectId($v);
            }
            else
            {
                if (preg_match('/(.*)\s+(.*)/i', $k, $data))
                    $tmp[$data[1]] = ($data[2] == 'LIKE') ? new \MongoDB\BSON\Regex($v, 'i') : [$map[trim($data[2])] => $v];
                else
                    $tmp[$k] = $v;
            }
        }
        $this->_where = $tmp;
        return $this;
    }

    /**
     * 描述：指定排序条件
     * @param array $order
     * @return object
     */
    public function order(array $order = [])
    {
        $tmp = [];
        $map = [
            'DESC' => -1,
            'ASC'  => 1
        ];
        foreach ($order as $k => $v)
        {
            $tmp[$k] = $map[strtoupper($v)];
        }
        $this->_order = $tmp;
        return $this;
    }

    /**
     * 描述：指定条数限制
     * @param int $skip
     * @param int $limit
     * @return object
     */
    public function limit(int $skip = 0, int $limit = 0)
    {
        if ($limit == 0)
        {
            $this->_skip  = 0;
            $this->_limit = $skip;
        }
        else
        {
            $this->_skip  = $skip;
            $this->_limit = $limit;
        }
        return $this;
    }

    /**
     * 描述：获取单条
     * @param bool $noId
     * @return array
     */
    public function one(bool $noId = FALSE)
    {
        $stime  = microtime(TRUE);
        $result = $this->_collection()->findOne($this->_where, [
            'projection' => $this->_select,
            'typeMap' => ['root' => 'array']
        ]);
        $etime = microtime(TRUE);
        \api\Log::add('MONGODB', [
            'TYPE'  => 'read',
            'TIME'  => round($etime - $stime, 4),
            'TABLE' => $this->_conf['PREFIX'].$this->_from,
            'WHERE' => $this->_where,
            'PARAM' => [
                'FIELD' => $this->_select,
                'LIMIT' => 1
            ]
        ]);
        if ($noId) unset($result['_id']);
        // 重置
        $this->_select = '';
        $this->_from   = '';
        $this->_order  = '';
        $this->_where  = [];
        return $result;
    }

    /**
     * 描述：获取多条
     * @param bool $noId
     * @return array
     */
    public function all(bool $noId = FALSE)
    {
        $stime = microtime(TRUE);
        $param = [
            'projection' => $this->_select,
            'sort'       => $this->_order,
            'skip'       => $this->_skip,
            'typeMap'    => ['root' => 'array']
        ];
        if ($this->_limit != 0)
            $param['limit'] = $this->_limit;
        $result = $this->_collection()->find($this->_where, $param);
        $etime  = microtime(TRUE);
        \api\Log::add('MONGODB', [
            'TYPE'  => 'read',
            'TIME'  => round($etime - $stime, 4),
            'TABLE' => $this->_conf['PREFIX'].$this->_from,
            'WHERE' => $this->_where,
            'PARAM' => [
                'FIELD' => $param['projection'],
                'SORT'  => $param['sort'],
                'SKIP'  => $param['skip'],
                'LIMIT' => isset($param['limit']) ? $param['limit'] : 0
            ]
        ]);
        $tmp = [];
        foreach ($result as $row)
        {
            if ($noId) unset($row['_id']);
            $tmp[] = $row;
        }
        // 重置
        $this->_select = '';
        $this->_from   = '';
        $this->_order  = '';
        $this->_limit  = 0;
        $this->_skip   = 0;
        $this->_where  = [];
        return $tmp;
    }

    /**
     * 描述：写入单条数据
     * @param array $data
     * @param bool $many
     * @return int
     */
    public function add(array $data = [], bool $many = FALSE)
    {
        if (empty($data))
        {
            error(5005);
        }
        try
        {
            $stime  = microtime(TRUE);
            $result = ($many) ? $this->_collection()->insertMany($data) : $this->_collection()->insertOne($data);
            $etime  = microtime(TRUE);
            \api\Log::add('MONGODB', [
                'TYPE'  => 'add',
                'TIME'  => round($etime - $stime, 4),
                'TABLE' => $this->_conf['PREFIX'].$this->_from,
                'DATA'  => $data
            ]);
            $this->_from = '';
            return ($many) ? $result->getInsertedIds() : $result->getInsertedId();
        }
        catch (\Exception $exception)
        {
            error(0, $exception->getMessage());
        }
    }

    /**
     * 描述：更新数据
     * @param array $data
     * @return int
     */
    public function edit(array $data = [])
    {
        if (empty($data))
        {
            error(5006);
        }
        try
        {
            $stime  = microtime(TRUE);
            $result = $this->_collection()->updateMany($this->_where, [
                '$set' => $data
            ]);
            $etime  = microtime(TRUE);
            \api\Log::add('MONGODB', [
                'TYPE'  => 'edit',
                'TIME'  => round($etime - $stime, 4),
                'TABLE' => $this->_conf['PREFIX'].$this->_from,
                'WHERE' => $this->_where,
                'DATA'  => $data
            ]);
            $this->_from  = '';
            $this->_where = [];
            return $result->getModifiedCount();
        }
        catch (\Exception $exception)
        {
            error(0, $exception->getMessage());
        }
    }

    /**
     * 描述：删除数据
     * @return int
     */
    public function delete()
    {
        $stime  = microtime(TRUE);
        $result = $this->_collection()->deleteMany($this->_where);
        $etime  = microtime(TRUE);
        \api\Log::add('MONGODB', [
            'TYPE'  => 'del',
            'TIME'  => round($etime - $stime, 4),
            'TABLE' => $this->_conf['PREFIX'].$this->_from,
            'WHERE' => $this->_where
        ]);
        $this->_from  = '';
        $this->_where = [];
        return $result->getDeletedCount();
    }

    /**
     * 描述：查询总数
     * @return int
     */
    public function count()
    {
        $result = $this->_collection()->count($this->_where);
        $this->_from  = '';
        $this->_where = [];
        return $result;
    }

    /**
     * 描述：创建索引
     * @param string $key
     */
    public function createIndex(string $key = '')
    {
        return $this->_collection()->createIndex([
            $key => 1
        ]);
    }

    /**
     * 描述：返回索引
     */
    public function getIndex()
    {
        $indexs = $this->_collection()->listIndexes();
        $tmp = [];
        foreach ($indexs as $row)
        {
            $tmp[] = $row;
        }
        return $tmp;
    }

    /**
     * 描述：删除索引
     * @param string $key
     */
    public function dropIndex(string $key = '')
    {
        try{
            return $this->_collection()->dropIndex($key);
        }
        catch(\Exception $e)
        {
            return $e->getMessage();
        }
    }

    /**
     * 描述：初始化数据库链接配置信息
     * @param array $conf
     */
    private function _initConfig($conf = [])
    {
        if (empty($conf))
        {
            //加载配置
            $conf = conf('MONGODB');
            if ( ! $conf)
                error(5001);
        }
        if ( ! isset($conf['HOST']))
        {
            error(5004, [
                'config' => 'HOST'
            ]);
        }
        if ( ! isset($conf['USERNAME']))
        {
            error(5004, [
                'config' => 'USERNAME'
            ]);
        }
        if ( ! isset($conf['DATABASE']))
        {
            error(5004, [
                'config' => 'DATABASE'
            ]);
        }
        $conf['PORT'] = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 27017 : $conf['PORT'];
        $conf['PASSWORD'] = ( ! isset($conf['PASSWORD'])) ? '' : $conf['PASSWORD'];
        $conf['PREFIX'] = ( ! isset($conf['PREFIX'])) ? '' : $conf['PREFIX'];
        $this->_conf = $conf;
    }

    /**
     * 描述：连接数据库
     */
    private function _linkDb()
    {
        $dsn = 'mongodb://'.
                $this->_conf['USERNAME'].
                ':'.
                $this->_conf['PASSWORD'].
                '@'.
                $this->_conf['HOST'].
                ':'.
                $this->_conf['PORT'].
                '/'.
                $this->_conf['DATABASE'];
        $client = (new Client($dsn));
        $db = $this->_conf['DATABASE'];
        $this->_conn = $client->$db;
    }

    /**
     * 描述：获取句柄
     */
    private function _collection()
    {
        if ($this->_from == '')
            error(5007);
        $table = $this->_conf['PREFIX'].$this->_from;
        return $this->_conn->$table;
    }

}