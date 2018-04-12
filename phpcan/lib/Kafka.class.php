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
 * KAFKA类库
 * ===============================================
 */

namespace phpcan\lib;

class Kafka{

    // 配置信息
    private $_conf;

    /**
     * Influxdb constructor.
     * @param array $conf
     */
    public function __construct(array $conf = [])
    {
        $this->_initConfig($conf);
    }

    /**
     * 描述：写入队列
     * @param string $value
     * @param string $topic
     * @param int $partId
     */
    public function add(string $value = '', string $topic = '', int $partId = 0)
    {
        $stime = microtime(TRUE);
        $config = \Kafka\ProducerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList($this->_conf['HOST'].':'.$this->_conf['PORT']);
        $config->setBrokerVersion('1.0.0');
        $config->setRequiredAck(1);
        $config->setIsAsyn(FALSE);
        $config->setProduceInterval(500);
        $producer = new \Kafka\Producer();
        $producer->send([
            [
                'topic'  => $topic,
                'value'  => $value,
                'key'    => '',
                'partId' => $partId
            ]
        ]);
        $etime = microtime(TRUE);
        \api\Log::add('KAFKA', [
            'TYPE'  => 'write',
            'TIME'  => round($etime - $stime, 4),
            'DATA'  => $value,
            'TOPIC' => $topic,
            'PART'  => $partId
        ]);
    }

    /**
     * 描述：消费队列
     * @param array $topic
     */
    public function consume(array $topic = [], callable $callback)
    {
        $config = \Kafka\ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList($this->_conf['HOST'].':'.$this->_conf['PORT']);
        $config->setGroupId('consumer');
        $config->setBrokerVersion('1.0.0');
        $config->setTopics($topic);
        $config->setOffsetReset('latest');
        $consumer = new \Kafka\Consumer();
        $consumer->start(function($topic, $part, $message) use($callback){
            $callback($topic, $part, $message);
        });
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
            $conf = conf('KAFKA');
            if ( ! $conf)
                error(9001);
        }
        if ( ! isset($conf['HOST']))
        {
            error(9002, [
                'config' => 'HOST'
            ]);
        }
        $conf['PORT'] = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 9092 : $conf['PORT'];
        $this->_conf = $conf;
    }

}