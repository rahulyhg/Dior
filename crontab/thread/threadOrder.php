<?php
require_once(dirname(__FILE__) . '/../lib/redis/redis.php');
require_once(dirname(__FILE__) . '/../lib/queue.php');

$queue = new queue();

$param = $queue->pop();

if (empty($param)) {
    
    exit;    
}

print_r($param);

$_SERVER['SERVER_NAME'] = $param['host'];

define('ROOT_DIR', realpath(dirname(__FILE__)) . '/../../');

require(ROOT_DIR . '/config/loader.php');
require(ROOT_DIR . '/app/base/kernel.php');
require(ROOT_DIR . '/config/mapper.php');


if (get_magic_quotes_gpc()) {
    kernel::strip_magic_quotes($_GET);
    kernel::strip_magic_quotes($_POST);
}
if (!kernel::register_autoload()) {
    require(ROOT_DIR . '/app/base/autoload.php');
}

cachemgr::init();
require(ROOT_DIR.'/config/config.php');
//require(ROOT_DIR.'/config/db_exinfo.php');
@include(ROOT_DIR.'/app/base/defined.php');
$response = kernel::single('base_rpc_service');
base_rpc_service::$node_id = $param['nodeId'];

$order = new taoguan_rpc_response_queue_order();
print_r($order->add($param['order'], $response));
