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
 * 模型类
 * ===============================================
 */

namespace phpcan;
use phpcan\lib\Mysql;

class Model extends Mysql
{

    // 默认数据表
    protected $table;
    // 默认主键
    protected $pk;
    // 打时间戳
    protected $timestamps;
    // 添加时间字段名
    protected $createAt;
    // 修改时间字段名
    protected $updateAt;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = '';
        $this->pk = 'id';
        $this->timestamps = FALSE;
        $this->createAt = 'create_at';
        $this->updateAt = 'update_at';
    }

    /**
     * 描述：初始化模型
     */
    public function init()
    {
        $conf = conf('MYSQL');
        $data = $this->query('SELECT table_name FROM information_schema.tables WHERE table_schema="'.$conf['DATABASE'].'"')->all();
        if (empty($data))
        {
            error(2006);
        }
        // 模型存放路径
        $path = _WORKPATH.'/'._APP.'/model';
        // 通用头部
        $header  = "<?PHP\r\n";
        $header .= "\r\n";
        $header .= "/**\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * PHPCAN微服务框架 - docker版本\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * 版本：PHP7.0 +\r\n";
        $header .= " * 作者: \r\n";
        $header .= " * 日期: ".date('Y-m-d H:i')."\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * [msg]\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " */\r\n\r\n";
        $header .= "namespace model;\r\n";
        $header .= "use phpcan\Model;\r\n\r\n";
        foreach ($data as $row)
        {
            $table = ucfirst(str_replace($conf['PREFIX'], '', $row['table_name']));
            $file  = $path.'/'.$table.'.php';
            if (is_file($file)) continue;
            $content  = "class $table extends Model{\r\n";
            $content .= "\r\n";
            $content .= "   public function __construct()\r\n";
            $content .= "   {\r\n";
            $content .= "       parent::__construct();\r\n";
            $content .= "       // 数据表名，不包括表前缀\r\n";
            $content .= "       \$this->table = '".str_replace($conf['PREFIX'], '', $row['table_name'])."';\r\n";
            $content .= "       // 主键，默认为id\r\n";
            $content .= "       \$this->pk = 'id';\r\n";
            $content .= "       // 是否自动打时间标记\r\n";
            $content .= "       \$this->timestamps = FALSE;\r\n";
            $content .= "       // 添加时间字段名\r\n";
            $content .= "       \$this->createAt = 'create_at';\r\n";
            $content .= "       // 更新时间字段名\r\n";
            $content .= "       \$this->updateAt = 'update_at';\r\n";
            $content .= "   }\r\n";
            $content .= "\r\n\r\n\r\n";
            $content .= "}";
            $header   = str_replace('[msg]', $table.' 表模型文件', $header);
            // 生成模型文件
            file_put_contents($file, $header.$content);
        }
    }

    /**
     * 描述：快速查询
     * @param $cond
     * @param $param
     */
    public function find($cond = null, array $param = [])
    {
        if (is_null($cond) || ! $cond)
        {
            $cond = [];
        }
        else
        {
            if (isInt($cond))
            $cond = [
                $this->pk => $cond
            ];
        }
        // 查询字段
        $field = (isset($param['field'])) ? $param['field'] : '';
        $limit = (isset($param['limit'])) ? $param['limit'] : FALSE;
        if (isInt($cond))
        {
            $order = '';
        }
        else
        {
            if (isset($param['order']))
            {
                $order = $param['order'];
            }
            else
            {
                $order = ($limit === 1) ? '' : $this->pk.' DESC';
            }
        }
        $data = $this->select($field)
                     ->from($this->table)
                     ->where($cond)
                     ->order($order);
        if ($limit !== FALSE)
        {
            $data->limit($limit);
        }
        return ($limit == 1 || isset($cond[$this->pk])) ? $data->one() : $data->all();
    }

    /**
     * 描述：查询符合条件的第一条
     * @param null $cond
     * @param string $field
     * @return mixed
     */
    public function first($cond = null, string $field = '')
    {
        return $this->find($cond, [
            'field' => $field,
            'order' => $this->pk.' ASC',
            'limit' => 1
        ]);
    }

    /**
     * 描述：查询符合条件的最后一条
     * @param null $cond
     * @param string $field
     * @return mixed
     */
    public function last($cond = null, string $field = '')
    {
        return $this->find($cond, [
            'field' => $field,
            'order' => $this->pk.' DESC',
            'limit' => 1
        ]);
    }

    /**
     * 描述：查询总数
     * @param null $cond
     */
    public function count($cond = null)
    {
        return $this->find($cond, [
            'field' => 'COUNT('.$this->pk.') AS count',
            'limit' => 1
        ]);
    }

    /**
     * 描述：计算总和
     * @param null $cond
     * @param string $field
     */
    public function sum($cond = null, string $field = '')
    {
        if ( ! $field)
        {
            error(2008);
        }
        return $this->find($cond, [
            'field' => 'SUM('.$field.') AS sum',
            'limit' => 1
        ]);
    }

    /**
     * 描述：获取最大值
     * @param null $cond
     * @param string $field
     */
    public function max($cond = null, string $field = '')
    {
        if ( ! $field)
        {
            error(2008);
        }
        return $this->find($cond, [
            'field' => 'MAX('.$field.') AS max',
            'limit' => 1
        ]);
    }

    /**
     * 描述：获取最小值
     * @param null $cond
     * @param string $field
     */
    public function min($cond = null, string $field = '')
    {
        if ( ! $field)
        {
            error(2008);
        }
        return $this->find($cond, [
            'field' => 'MIN('.$field.') AS min',
            'limit' => 1
        ]);
    }

    /**
     * 描述：根据主键删除
     * @param null $id
     */
    public function destroy($id = null)
    {
        $cond = [];
        if (isInt($id))
        {
            $cond = [
                $this->pk => $id
            ];
        }
        else
        {
            $cond = [
                $this->pk.' IN' => $id
            ];
        }
        return $this->from($this->table)
                    ->where($cond)
                    ->delete();
    }

    /**
     * 描述：写入
     * @param array $data
     */
    public function insert(array $data = [])
    {
        if ($this->timestamps)
        {
            $data[$this->createAt] = time();
        }
        return $this->from($this->table)->add($data);
    }

    /**
     * 描述：更新
     *
     * @param null $cond
     * @param array $data
     */
    public function update($cond = null, array $data = [])
    {
        if (isInt($cond))
        {
            $cond = [
                $this->pk => $cond
            ];
        }
        if ($this->timestamps)
        {
            $data[$this->updateAt] = time();
        }
        return $this->from($this->table)
                    ->where($cond)
                    ->edit($data);
    }

    /**
     * 描述：递增
     * @param $cond
     * @param array $data
     */
    public function inc($cond = null, array $data = [])
    {
        $tmp = [];
        foreach ($data as $k => $v)
        {
            $tmp[$k] = '`'.$k.'` + '.$v;
        }
        if ($this->timestamps)
        {
            $tmp[$this->updateAt] = time();
        }
        return $this->from($this->table)
                    ->where($cond)
                    ->edit($tmp);
    }

    /**
     * 描述：递减
     * @param $cond
     * @param array $data
     */
    public function dec($cond = null, array $data = [])
    {
        $tmp = [];
        foreach ($data as $k => $v)
        {
            $tmp[$k] = '`'.$k.'` - '.$v;
        }
        if ($this->timestamps)
        {
            $tmp[$this->updateAt] = time();
        }
        return $this->from($this->table)
            ->where($cond)
            ->edit($tmp);
    }

    /**
     * 描述：更新或写入数据
     */
    public function insertOrUpdate($cond = null, array $insert = [], array $update = [])
    {
        return $this->from($this->table)
                    ->where($cond)
                    ->replace($insert, $update);
    }

}