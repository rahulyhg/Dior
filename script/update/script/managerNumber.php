<?php
/**
 * 根据传入的域名统计操作员数量
 * 
 * @author shshuai
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
$userObj = &app::get('desktop')->model('users');
$groupObj = &app::get('ome')->model('groups');
$groupOpsObj = &app::get('ome')->model('group_ops');
$charsetObj = kernel::single('base_charset');

$title = array(
    $charsetObj->utf2local("域名"),
    $charsetObj->utf2local("管理员数"),
    $charsetObj->utf2local("分组内管理员数")
);
//$str = '"'.implode('","',$title).'"\n';

$str = '';
$data = array();
$userNum = $userObj->count();
$data['domain'] = $charsetObj->utf2local($domain);
$data['userNum'] = $userNum;
$groups = $groupObj->getList('*');
$ginfo = '';
foreach($groups as $group){
    $opsNum = $groupOpsObj->count(array('group_id'=>$group['group_id']));
    $ginfo .= '分组'.$group['name'].'人员数：'.$opsNum.'|';
    unset($group);
}
$data['ginfo'] = $charsetObj->utf2local($ginfo);

$str .= '"'.implode('","',$data).'"';

ilog($str);

/**
 * 日志
 */
function ilog($str) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/manager_' . date('Y-m-d') . '.csv';
    $fp = fopen($filename, 'a');
        echo date("m-d H:i") . "\t" . $domain . "\n";
    fwrite($fp, $str. "\n");
    fclose($fp);
}
