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
$menus = array('导出任务列表','短信开通','短信设置','短信模板','短信日志','免打扰设置','队列管理','导出任务','系统管理');
foreach($menus as $menu_title){
    $sql = "SELECT menu_id,menu_type,menu_title FROM `sdb_desktop_menus` WHERE app_id='taoexlib' AND menu_title='".$menu_title."'";
    $row = $db->select($sql);
    if(count($row)>1){
        if($row[1]['menu_id'] && $row[1]['menu_id']>0){
            $sql = "DELETE FROM `sdb_desktop_menus` WHERE `menu_id`=".$row[1]['menu_id'];
            $db->exec($sql);
        }
    }
    unset($row);
}
ilog("repair menu");

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/menu_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
