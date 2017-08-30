<?php
/**
 * 根据传入的域名做初始化工作
 *
 * @author hzjsq@msn.com
 * @version 1.0
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

$sql = 'TRUNCATE TABLE sdb_ome_api_stock_log';
$db->exec($sql);

$sql = 'OPTIMIZE TABLE sdb_ome_api_stock_log';
$db->exec($sql);

$sql = 'ALTER TABLE sdb_ome_api_stock_log DROP INDEX idx_shop_product, DROP INDEX idx_shop_id, DROP INDEX idx_store, DROP INDEX ind_status, DROP INDEX ind_api_type, DROP INDEX ind_error_lv';
$db->exec($sql);

$sql = 'ALTER TABLE sdb_ome_api_stock_log ADD crc32_code BIGINT(20) NOT NULL';
$db->exec($sql);

$sql = 'ALTER TABLE  sdb_ome_api_stock_log ADD UNIQUE idx_crc32_code(crc32_code)';
$db->exec($sql);

