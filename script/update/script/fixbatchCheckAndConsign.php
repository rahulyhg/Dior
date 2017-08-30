<?php
/**
 * 删除批量发货和校验的垃圾数据
 *
 * @author chenping<chenping@shopex.cn>
 * @version $2012-8-22 11:03Z
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {
    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$db = kernel::database();

$sql = "delete FROM `sdb_ome_batch_log` WHERE `log_text` ='a:0:{}'";
$db->exec($sql);
