<?php
/**
 * 拒绝所有没有明细的售后单
 * 
 * @author chenping@shopex.cn
 * @version 1.0
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];
if (empty($domain) || empty($order_id) || empty($host_id) ) {
    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

# 判断发货单是否存在
$reshipModel = app::get('ome')->model('reship');
$reshipList = $reshipModel->getList('reship_id,is_check',array('is_check'=>array('0','1','3')));

$updateCount = 0;
$reshipItemModel = app::get('ome')->model('reship_items');
foreach ($reshipList as $key=>$value) {
    $filter = array('reship_id'=>$value['reship_id']);
    $count = $reshipItemModel->count($filter);
    if ($count == 0) {
        $reshipModel->update(array('is_check'=>'5'),array('reship_id'=>$value['reship_id']));
        ilog("reship_id:".$value['reship_id'].';is_check:'.$value['is_check']);

        $updateCount++;
    }
}
echo 'count:'.$updateCount;

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/reshipException' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
