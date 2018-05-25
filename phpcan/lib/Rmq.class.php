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
 * RABBITMQ类库
 * ===============================================
 */

namespace phpcan\lib;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Rmq{

    // 配置信息
    private $_conf;
    // 句柄
    private $_conn;
    // 通道
    private $_channel;
    // 交换机
    private $_exchange;
    // 队列
    private $_queue;
    // 路由
    private $_route;

    /**
     * Rmq constructor.
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        // 初始化配置信息
        $this->_initConfig($conf);
        // 初始化连接
        $this->_initLink();
        $this->_exchange = '';
        $this->_queue    = '';
        $this->_route    = '';
    }

    /**
     * 描述：指定交换机
     * @param string $name
     * @param string $type
     * @param bool $delayed     是否延迟
     * @param bool $passive     当交换机不存在时不自动创建
     * @param bool $durable     持久化
     * @param bool $autoDelete  连接断开后自动删除交换机
     * @return $this
     */
    public function exchange(
        string $name     = '',
        string $type     = 'direct',
        bool $delayed    = FALSE,
        bool $passive    = FALSE,
        bool $durable    = TRUE,
        bool $autoDelete = FALSE
    )
    {
        $name = ($name == '') ? $this->_conf['EXCHANGE'] : $name;
        $this->_exchange = $name;
        if ($delayed)
        {
            $this->_channel->exchange_declare(
                $name,
                'x-delayed-message',
                $passive,
                $durable,
                $autoDelete,
                FALSE,
                FALSE,
                new AMQPTable([
                    'x-delayed-type' => $type
                ])
            );
        }
        else
        {
            $this->_channel->exchange_declare(
                $name,
                $type,
                $passive,
                $durable,
                $autoDelete
            );
        }
        return $this;
    }

    /**
     * 描述：指定队列
     * @param string $name
     * @param bool $passive     当队列不存在时不自动创建
     * @param bool $durable     持久化
     * @param bool $exclusive   队列允许其他通道消费
     * @param bool $autoDelete  队列执行完毕后自动删除
     */
    public function queue(
        string $name     = '',
        bool $passive    = FALSE,
        bool $durable    = TRUE,
        bool $exclusive  = FALSE,
        bool $autoDelete = FALSE
    )
    {
        $name = ($name == '') ? $this->_conf['QUEUE'] : $name;
        $this->_queue = $name;
        $this->_channel->queue_declare(
            $name,
            $passive,
            $durable,
            $exclusive,
            $autoDelete
        );
        return $this;
    }

    /**
     * 描述：绑定
     * @param string $route
     */
    public function bind(string $route = '')
    {
        if ($this->_exchange == '')
            $this->_exchange = $this->_conf['EXCHANGE'];
        if ($this->_queue == '')
            $this->_queue = $this->_conf['QUEUE'];
        // 绑定交换机和队列
        $this->_channel->queue_bind($this->_queue, $this->_exchange, $route);
        $this->_route = $route;
        return $this;
    }

    /**
     * 描述：消费队列
     * @param callable|null $callback   回调
     * @param bool $autoAck             自动应答
     * @param string $tag               标签
     */
    public function consume(
        callable $callback = null,
        bool $autoAck = FALSE,
        string $tag = ''
    )
    {
        if ($this->_queue == '')
            $this->_queue = $this->_conf['QUEUE'];
        // 消费队列
        $this->_channel->basic_consume(
            $this->_queue,
            $tag,
            FALSE,
            $autoAck,
            FALSE,
            FALSE,
            $callback
        );
        while(count($this->_channel->callbacks))
        {
            $this->_channel->wait();
        }
    }

    /**
     * 描述：读取单条队列
     * @return mixed
     */
    public function get()
    {
        if ($this->_queue == '')
            $this->_queue = $this->_conf['QUEUE'];
        $msg = $this->_channel->basic_get($this->_queue);
        if ( ! $msg)
        {
            return FALSE;
        }
        $this->_channel->basic_ack($msg->delivery_info['delivery_tag']);
        $this->_channel->close();
        $this->_conn->close();
        return $msg->body;
    }

    /**
     * 描述：消费应答
     * @param $message
     */
    public function ack($message = '')
    {
        $tag = $message->delivery_info['delivery_tag'];
        $message->delivery_info['channel']->basic_ack($tag);
    }

    /**
     * 描述：写入队列
     * @param $data
     * @param bool $batch
     * @param $delayed
     */
    public function add($data = '', bool $batch = FALSE, $delayed = FALSE)
    {
        // 数组转化
        if (is_array($data))
        {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $msg = new AMQPMessage($data, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        // 是否需要延迟
        if ($delayed !== FALSE)
        {
            $headers = new AMQPTable([
                'x-delay' => $delayed
            ]);
            $msg->set('application_headers', $headers);
        }
        // 批量写入
        if ($batch)
        {
            $this->_channel->batch_basic_publish($msg, $this->_exchange, $this->_route);
            return $this;
        }
        else
        {
            $this->_channel->basic_publish($msg, $this->_exchange, $this->_route);
            return TRUE;
        }
    }

    /**
     * 描述：提交到队列
     */
    public function publish()
    {
        $this->_channel->publish_batch();
        return TRUE;
    }

    /**
     * 描述：初始化配置信息
     * @param array $conf
     */
    private function _initConfig(array $conf = [])
    {
        if (empty($conf))
        {
            //加载配置
            $conf = conf('RMQ');
            if ( ! $conf)
                error(9032);
        }
        if ( ! isset($conf['HOST']))
        {
            error(9033, [
                'config' => 'HOST'
            ]);
        }
        $conf['USERNAME'] = ( ! isset($conf['USERNAME'])) ? '' : $conf['USERNAME'];
        $conf['PASSWORD'] = ( ! isset($conf['PASSWORD'])) ? '' : $conf['PASSWORD'];
        $conf['PORT']     = ( ! isset($conf['PORT'])) ? 5672 : $conf['PORT'];
        $conf['VHOST']    = ( ! isset($conf['VHOST'])) ? '/' : $conf['VHOST'];
        $conf['EXCHANGE'] = ( ! isset($conf['EXCHANGE'])) ? 'route' : $conf['EXCHANGE'];
        $conf['QUEUE']    = ( ! isset($conf['QUEUE'])) ? 'queue' : $conf['QUEUE'];
        $conf['QOS']      = ( ! isset($conf['QOS'])) ? 1000 : $conf['QOS'];
        $this->_conf = $conf;
    }

    /**
     * 描述：初始化连接句柄
     */
    private function _initLink()
    {
        $this->_conn = new AMQPStreamConnection(
            $this->_conf['HOST'],
            $this->_conf['PORT'],
            $this->_conf['USERNAME'],
            $this->_conf['PASSWORD'],
            $this->_conf['VHOST'],
            [
                'read_write_timeout' => 3000,
                'heartbeat' => 1000
            ]
        );
        $this->_channel = $this->_conn->channel();
        $this->_channel->basic_qos(null, $this->_conf['QOS'], null);
    }

}