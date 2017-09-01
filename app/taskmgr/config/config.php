<?php
/**
 * 配置信息
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

//定义数据提供者队列
//define('__CONNECTER_MODE','rabbitmq');

//define('__RABBITMQ_INTERFACE__','pecl');

//mq配置定义
/*
$GLOBALS['__RABBITMQ_CONFIG'] = array(

    'HOST'      => '127.0.0.1',
    'PORT'      => '5672',
    'USER'      => 'task',
    'PASSWD'    => 'task123',
    'VHOST'     => 'erp_task',
    'ROUTER'    => 'erp.task.%s.*'
);
*/

define('__CONNECTER_MODE','redis');

$GLOBALS['__REDIS_CONFIG'] = array(
    'HOST'      => '127.0.0.1',
    'PORT'      => '6379',
    //'PASSWD'    => 'erp3310',
);

//缓存存储介质提供者
//define('__CACHE_MODE','memcache');

//define('__MEMCACHE_CONFIG','127.0.0.1:11211,127.0.0.1:11212');

define('__CACHE_MODE','filesystem');

//文件存储介质提供者
//define('__STORAGE_MODE','ftp');

/*
$GLOBALS['__STORAGE_CONFIG'] = array(
    'HOST'    => '127.0.0.1',
    'PORT'    => '21',
    'USER'    => 'test',
    'PASSWD'  => 'test',
    'TIMEOUT' => '30',
    'PASV'    => false,
);
*/

define('__STORAGE_MODE','local');

//设置为真实的域名
define('DOMAIN', 'ec-oms.dior.cn');

//定义内部任务请求的token
define('REQ_TOKEN', 'SzjJ7VF9w2Dy3yt4K9vcV3LEwFc5pX3cXHu');