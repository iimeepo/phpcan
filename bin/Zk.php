<?php

/**
 * ===============================================
 * PHPCAN微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/3/14 13:15
 * 官网：http://www.phpcan.cn
 * ===============================================
 * zookeeper客户端脚本
 * ===============================================
 */

if(version_compare(PHP_VERSION,'7.0.0','<')){
    exit('PHP版本必须大于7.0');
}

// 项目根目录，项目所有涉及到目录的地方以此作为根目录
define('ROOTPATH', getcwd());
define('_CLI', TRUE);

// 引入驱动文件
require ROOTPATH . '/phpcan/Driver.php';

if ( ! _SOA)
    exit('没有开启SOA支持');

// 默认项目
define('_APP', (isset($argv[1])) ? $argv[1] : conf('DEFAULT'));
// 合并项目配置信息
$appConf = require ROOTPATH.'/work/'._APP.'/conf/Conf.php';
\api\Conf::merge($appConf);
// 读取配置信息
$conf = conf('ZOOKEEPER');
// 初始化ZK类
$Zookeeper = new \phpcan\lib\Zookeeper([
    // 地址
    'HOST'    => $conf['HOST'],
    // 端口
    'PORT'    => $conf['PORT'],
    // 超时时间
    'TIMEOUT' => $conf['TIMEOUT']
]);
// 获取环境变量中的IP和端口
$env = getenv();
// 创建节点
$node = '/soa/'.conf('EXAMPLE').'/'.conf('ENVIRONMENT');
// 获取环境变量
$env = getenv();
while(TRUE)
{
    // 定义节点名(版本+IP+PORT)
    $nodeName = str_replace('.', '', $appConf['VERSION']).'-'.$env['HOST_IP'].':'.$env['PORT'].'-'.$appConf['VERSION'];
    if ($Zookeeper->create($node.'/'.$nodeName, 1, [], 1) !== FALSE)
    {
        break;
    }
    else
    {
        sleep(1);
    }
}
// 容器启动后默认从配置中心拉取一次配置
watchConf($node);
// 监听实例配置
$Zookeeper->watch($node, 'watchConf');

// 监听回调
function watchConf($path)
{
    global $Zookeeper;
    $conf = $Zookeeper->get($path);
    if (! $conf || ! $conf['data'])
    {
        return FALSE;
    }
    $path = ltrim($path, '/');
    $file = _SOADATA.'/'.str_replace('/', '-', $path).'.php';
    // 尝试解析配置的JSON
    try
    {
        $conf = jsonDecode(stripcslashes($conf['data']));
        $conf = '<?PHP return '.var_export($conf, TRUE).';';
        file_put_contents($file, $conf);
    }
    catch (\InvalidArgumentException $exception)
    {
        logs('配置信息解析有误，请在SOA中检查配置信息书写是否正确', 'conf');
    }
}

// 保持会话
hold();