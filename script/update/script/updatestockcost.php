<?php
/**
 * 初始化系统成本设置
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

$get_value_type = app::get("tgstockcost")->getConf("tgstockcost.get_value_type");
$cost = app::get("tgstockcost")->getConf("tgstockcost.cost");
$installed = app::get("tgstockcost")->getConf("tgstockcost.installed");

app::get("ome")->setConf("tgstockcost.get_value_type",$get_value_type);
app::get("ome")->setConf("tgstockcost.cost",$cost);
app::get("ome")->setConf("tgstockcost.installed",$installed);
