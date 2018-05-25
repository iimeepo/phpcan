<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/5/2 16:50
 * 官网：http://www.phpcan.cn
 * ===============================================
 * ES的IDE自动完成
 * ===============================================
 */

namespace api;

/**
 * Class Rmq
 * @package api
 * @method static|\api\Rmq exchange(string $name, string $type, bool $delayed, bool $passive, bool $durable, bool $autoDelete)
 * @method \api\Rmq queue(string $name, bool $passive, bool $durable, bool $exclusive, bool $autoDelete)
 * @method \api\Rmq bind(string $route)
 * @method consume(callable $callback, bool $autoAck, string $tag)
 * @method get()
 * @method static ack($message)
 * @method add($data, bool $batch, $delayed)
 * @method publish()
 *
 */
class Rmq{}