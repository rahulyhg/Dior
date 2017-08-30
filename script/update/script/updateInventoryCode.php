<?php
/**
 * 盘点单编号代码初始化
 *
 * @author sunjing<sunjing@shopex.cn>
 * @version 1.0
 * @param $argv[1] 域名
 * @param $argv[2] ip
 */
error_reporting(E_ALL ^ E_NOTICE);
$domain = $argv[1];
$host_id = $argv[2];

if (empty($domain) || empty($host_id)) {

die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');
$db = kernel::database();
cachemgr::init(false);
$is_exist = is_exist('inventory');
if(!$is_exist){
    save();
}
function is_exist($name)
{
    $sql = 'SELECT eid FROM `sdb_taoguaninventory_encoded_state` WHERE name=\''.$name.'\' ';
    $row = $GLOBALS['db']->selectrow($sql);
    return $row ? true : false;
}

function save()
{
    $sql = "INSERT INTO `sdb_taoguaninventory_encoded_state` (`name`,`head`,`currentno`,`bhlen`,`description`) VALUES('inventory','PD','0','4','盘点表')";
    $GLOBALS['db']->exec($sql);

}



