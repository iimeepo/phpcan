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
 * MYSQL数据库类库
 * ===============================================
 */

namespace phpcan\lib;

class Mysql{

    // 定义成员属性
    private $_conn;                // 数据库连接属性
    private $_host;                // 主机
    private $_user;                // 数据库用户名
    private $_pass;                // 数据库密码
    private $_data;                // 连接的数据库
    private $_port;                // 端口
    private $_char;                // 字符编码
    private $_prefix;              // 数据表前缀
    private $_table;               // 操作的数据表

    // 定于SQL语句的各个小模块
    private $_select;
    private $_from;
    private $_where;
    private $_order;
    private $_group;
    private $_limit;
    private $_join;
    private $_pk       = '';
    private $_bindData = [];
    private $_querySql = '';
    private $_realSql  = '';
    private $_keyNum   = 0;
    private $_query    = FALSE;

    // 结果集
    private $_result;

    /**
     * Mysql constructor.
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        // 初始化数据库链接配置信息
        $this->_initConfig($conf);
        // 链接数据库
        $this->_linkDb();
    }

    public function __destruct()
    {
        $this->_conn = null;
    }

    /**
     * 描述：设置主键字段
     * @param string $pk
     * @return object
     */
    public function pk(string $pk = '')
    {
        if ($pk == '')
        {
            return $this;
        }
        $this->_pk = $pk;
        return $this;
    }

    /**
     * 描述：组成SQL语句：SELECT `field`
     * @param string $field
     * @return object
     */
    public function select(string $field = '')
    {
        $this->_select  = 'SELECT ';
        $this->_select .= ($field == '') ? '*' : $field;
        return $this;
    }

    /**
     * 描述：组成SQL语句：FROM `table`
     * @param string $table
     * @return object
     */
    public function from(string $table = '')
    {
        if ($table == '')
        {
            error(2003);
        }
        $this->_table = $this->_prefix.$table;
        $this->_from  = '';
        $this->_from  = ' FROM '.$this->_table;
        return $this;
    }

    /**
     * 描述：组成SQL语句：WHERE `field` = ?
     * @param array $where
     * @return object
     */
    public function where(array $where = [])
    {
        if (empty($where))
        {
            return $this;
        }
        $this->_where .= ' WHERE ';
        $this->_bindData = [];
        $i = 0;
        foreach ($where as $key => $val)
        {
            if ($i == 0)
            {
                $and = '';
            }
            else
            {
                $and = (preg_match('/or/i', $key)) ? '' : ' AND ';
            }
            $this->_where .= $and.$key;
            if (isInt($val) || is_string($val))
            {
                if ((preg_match('/\s/', $key)))
                {
                    $this->_where .= (preg_match('/^or\s+([\w]+)$/i', $key)) ? ' = ? ' : ' ? ';
                }
                else
                {
                    $this->_where .= ' = ? ';
                }
                $this->_bindData[] = $val;
            }
            else
            {
                $this->_where .= is_array($val) ? ' ('.implode(',', $val).') ' : ' IS NULL ';
            }
            $i++;
        }
        return $this;
    }

    /**
     * 描述：组成SQL语句：ORDER BY `field` DESC/ASC
     * @param string $order
     * @return $this
     */
    public function order(string $order = '')
    {
        if ($order == '')
        {
            return $this;
        }
        $this->_order = ' ORDER BY '.$order;
        return $this;
    }

    /**
     * 描述：组成SQL语句：GROUP BY `field`
     * @param string $group
     * @return $this
     */
    public function group(string $group = '')
    {
        if ($group == '')
        {
            return $this;
        }
        $this->_group = ' GROUP BY '.$group;
        return $this;
    }

    /**
     * 描述：组成SQL语句：LIMIT 10,1
     * @param int $limit
     * @param int $len
     * @return $this
     */
    public function limit(int $limit = 1, int $len = 0)
    {
        $this->_limit = ' LIMIT '.$limit;
        if ($len > 0)
            $this->_limit .= ','.$len;
        return $this;
    }

    /**
     * 描述：组成SQL语句：INNER JOIN `table` ON ....
     * @param string $join
     * @param string $on
     * @param string $model
     * @return $this
     */
    public function join(string $join = '', string $on = '', string $model = 'INNER')
    {
        if ($join == '' OR $on == '')
        {
            return $this;
        }
        $this->_join .= ' '.$model.' JOIN '.$this->_prefix.$join.' ON '.$on;
        return $this;
    }

    /**
     * 描述：获取单条记录
     */
    public function one()
    {
        $stime = microtime(TRUE);
        if ( ! $this->_query)
        {
            $this->_limit = ' LIMIT 1';
            // 组装执行SQL
            $this->_createSql();
        }
        $data = $this->_result->fetch(\PDO::FETCH_ASSOC);
        $this->_query = FALSE;
        $etime = microtime(TRUE);
        \api\Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $data;
    }

    /**
     * 描述：查询多条记录
     */
    public function all()
    {
        $stime = microtime(TRUE);
        if ( ! $this->_query)
        {
            // 组装执行SQL
            $this->_createSql();
        }
        $data = $this->_result->fetchAll(\PDO::FETCH_ASSOC);
        $this->_query = FALSE;
        $etime = microtime(TRUE);
        \api\Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $data;
    }

    /**
     * 描述：插入数据
     * @param array $data
     * @return int
     */
    public function add(array $data = [])
    {
        $stime = microtime(TRUE);
        // 初始化SQL
        $this->_querySql = '';
        // 组成SQL
        $this->_querySql = 'INSERT INTO `'.$this->_table.'` ';
        // 遍历字段
        $field = $value = '(';
        $doc = '';
        $this->_bindData = [];
        foreach ($data as $key => $val)
        {
            $field .= $doc.'`'.$key.'`';
            $value .= $doc.' ? ';
            $doc = ',';
            $this->_bindData[] = $val;
        }
        $field .= ')';
        $value .= ')';
        $this->_querySql .= $field.' VALUES '.$value;
        // 执行SQL
        $this->_exec();
        $etime = microtime(TRUE);
        \api\Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $this->_conn->lastInsertId();
    }

    /**
     * 描述：更新数据
     * @param array $data
     * @return int
     */
    public function edit(array $data = [])
    {
        $stime = microtime(TRUE);
        //组成SQL
        $this->_querySql = 'UPDATE `'.$this->_table.'` SET ';
        $doc = '';
        foreach ($data as $key => $val)
        {
            $this->_querySql .= $doc.'`'.$key.'` = ';
            if (isInt($val) || preg_match('/`[\w]+`\s?[+|-]\s?[\d]+/', $val))
            {
                $this->_querySql .= $val;
            }
            else
            {
                $this->_querySql .= '"'.$val.'"';
            }
            $doc = ',';
        }
        $this->_querySql .= $this->_where.$this->_limit;
        // 执行SQL
        $this->_exec();
        $etime = microtime(TRUE);
        \api\Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $this->_result->rowcount();
    }

    /**
     * 描述：删除数据
     */
    public function delete()
    {
        $stime = microtime(TRUE);
        $this->_querySql  = 'DELETE FROM `'.$this->_table.'` ';
        $this->_querySql .= $this->_where;
        // 执行SQL
        $this->_exec();
        $etime = microtime(TRUE);
        \api\Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $this->_result->rowcount();
    }

    /**
     * 描述：执行SQL语句
     * @param string $sql
     * @return object|bool
     */
    public function query(string $sql = '')
    {
        if ($sql == '')
        {
            return FALSE;
        }
        $stime = microtime(TRUE);
        $this->_querySql = str_replace('[prefix]', $this->_prefix, $sql);
        // 执行SQL
        try{
            $this->_result = $this->_conn->query($this->_querySql);
        }
        catch (\PDOException $exception)
        {
            error(2004, [
                'error' => $this->_querySql,
                'msg'   => $exception->getMessage()
            ]);
        }
        $etime = microtime(TRUE);
        \api\Log::add('MYSQL', [
            'SQL'  => $this->_querySql,
            'TIME' => round($etime - $stime, 4)
        ]);
        // 判断操作类型
        if (preg_match('/^select|show/i', $this->_querySql))
        {
            $this->_query = TRUE;
            return $this;
        }
        else
        {
            return $this->_result->rowcount();
        }
    }

    /**
     * 描述：更新数据
     * @param array $insert
     * @param array $update
     * @return int
     */
    public function replace(array $insert = [], array $update = [])
    {
        $pk = ($this->_pk == '') ? '*' : $this->_pk;
        $this->_querySql = 'SELECT '.$pk.' FROM '.$this->_table.$this->_where.' LIMIT 1';
        // 执行SQL
        $this->_exec();
        $data = $this->_result->fetch();
        if (empty($data))
        {
            $this->add($insert);
        }
        else
        {
            if ( ! empty($update))
            $this->edit($update);
        }
        return $this->_result->rowcount();
    }

    /**
     * 描述：获取执行的最后一条SQL
     */
    public function lastSql()
    {
        return $this->_realSql;
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
            $conf = conf('MYSQL');
            if ( ! $conf)
                error(2001);
        }
        if ( ! isset($conf['HOST']))
        {
            error(2005, [
                'config' => 'HOST'
            ]);
        }
        if ( ! isset($conf['USERNAME']))
        {
            error(2005, [
                'config' => 'USERNAME'
            ]);
        }
        if ( ! isset($conf['DATABASE']))
        {
            error(2005, [
                'config' => 'DATABASE'
            ]);
        }
        $conf['PORT'] = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 3306 : $conf['PORT'];
        $conf['CHARSET'] = ( ! isset($conf['CHARSET']) || ! $conf['CHARSET']) ? 'utf8' : $conf['CHARSET'];
        $conf['PREFIX'] = ( ! isset($conf['PREFIX'])) ? '' : $conf['PREFIX'];
        $this->_host   = $conf['HOST'];
        $this->_user   = $conf['USERNAME'];
        $this->_pass   = $conf['PASSWORD'];
        $this->_data   = $conf['DATABASE'];
        $this->_port   = $conf['PORT'];
        $this->_char   = $conf['CHARSET'];
        $this->_prefix = $conf['PREFIX'];
    }

    /**
     * 描述：连接数据库
     */
    private function _linkDb()
    {
        $dsn = 'mysql:host='.$this->_host.';dbname='.$this->_data.';port='.$this->_port.';charset='.$this->_char;
        try
        {
            $this->_conn = new \PDO($dsn, $this->_user, $this->_pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        }
        catch(\PDOException $err)
        {
            error(2002, [
                'error' => $err->getMessage()
            ]);
        }
        $this->_conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, FALSE);
        $this->_conn->exec('SET NAMES '.$this->_char);
    }

    /**
     * 描述：组装SQL语句
     */
    private function _createSql()
    {
        // 拼接SQL语句
        $this->_querySql = $this->_select.
               $this->_from.
               $this->_join.
               $this->_where.
               $this->_order.
               $this->_limit.
               $this->_group;
        // 执行SQL
        $this->_exec();
        // 重置
        $this->_join  = '';
        $this->_where = '';
        $this->_bindData = [];
        $this->_order = '';
        $this->_group = '';
        $this->_limit = '';
    }

    /**
     * 描述：执行SQL
     */
    private function _exec()
    {
        // 获取真实SQL
        $this->_realSql = $this->_realSql();
        // 执行预查询
        try{
            $this->_result = $this->_conn->prepare($this->_querySql);
        }
        catch (\PDOException $exception)
        {
            error(2004, [
                'error' => $this->_realSql,
                'msg'   => $exception->getMessage()
            ]);
        }
        // 占位符赋值
        if ( ! empty($this->_bindData))
        {
            foreach ($this->_bindData as $k => $v)
                $this->_result->bindValue(($k + 1), $v, (is_int($v)) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $this->_result->execute();
    }

    /**
     * 描述：获取执行的SQL语句
     */
    private function _realSql()
    {
        $sql = preg_replace_callback('/\s+\?\s+/i', [$this, '_sqlReplace'], $this->_querySql);
        $this->_keyNum = 0;
        return $sql;
    }

    /**
     * 描述：组装真实SQL
     * @return string
     */
    private function _sqlReplace()
    {
        $val = $this->_bindData[$this->_keyNum];
        if (is_array($val))
        {
            $val = ' ('.implode(',', $val).') ';
        }
        elseif (is_numeric($val))
        {
            $val = ' '.$val.' ';
        }
        else
        {
            $val = " '".$val."' ";
        }
        $this->_keyNum++;
        return $val;
    }

}