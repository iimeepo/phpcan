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
 * Class Es
 * @package api
 * @method static createIndex(string $name, array $field, array $settings)
 * @method static deleteIndex(string $name)
 * @method static getMapping(string $name)
 * @method static putMapping(string $name, array $field)
 * @method static|\api\Es select(string $field)
 * @method \api\Es from(string $index)
 * @method \api\Es where(array $where)
 * @method \api\Es order(array $order)
 * @method \api\Es limit(int $limit, int $len)
 * @method get($id)
 * @method all(array $highlight)
 * @method add(array $data, bool $many)
 * @method edit($id, array $data)
 * @method delete()
 */
class Es{}