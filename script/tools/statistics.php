<?php

$domain = $argv[1];
$host_id = $argv[2];
$start = $argv[3];
$end = $argv[4];
$fix = $argv[5];
$endTime = strtotime(date('Y-m-d 0:0:0',$fix));


if (empty($domain) || empty($start) || empty($host_id) || empty($end)) {

    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../lib/init.php');

$fileName = getFileName($fix);
$allCnt['domain'] =  $domain;
$allCnt['start'] = $start;
$allCnt['end'] = $end;
$allCnt['order'] = app::get('omestart')->getconf('tb_nick');
$allCnt['id'] = $host_id;
$allCnt['shopInfo'] = getShopInfo($endTime);
$allCnt['orderInfo'] = getOrderInfo($endTime);
$allCnt['deliveryInfo'] = getDeliveryInfo($endTime);
$allCnt['SESSION'] = getSessionInfo($endTime);

writeFile($fileName, "\$static['{$host_id}'] = '". str_replace('\'', "\'", json_encode($allCnt)) ."';\n");

function getSessionInfo($endTime) {
    
     $ret['session'] = app::get('omestart')->getconf('tb_session');
     $ret['nick'] = app::get('omestart')->getconf('tb_nick');
     $ret['uid'] = app::get('omestart')->getconf('tb_uid');
     
     return $ret;
}

function getDeliveryInfo($time) {
    
    $db = kernel::database();
    $ret['cnt'] = $db->count("SELECT count(*) as c FROM sdb_ome_delivery WHERE status not in ('back', 'failed', 'cancel') and create_time<={$time}");
    $ret['finishCnt'] = $db->count("SELECT count(*) as c FROM sdb_ome_delivery WHERE status in ('succ') and disabled='false' and is_bind='false' and create_time<={$time}");
    $ret['printCnt'] = $db->count("SELECT count(*) as c FROM sdb_ome_delivery WHERE (stock_status='true' or deliv_status='true' or expre_status='true') and status not in ('back', 'cancel', 'failed') and disabled='false' and is_bind='false' and create_time<={$time}");
    $ret['verifyCnt'] = $db->count("SELECT count(*) as c FROM sdb_ome_delivery WHERE status in ('succ') and disabled='false' and is_bind='false' and verify='true' and create_time<={$time}");
    return $ret;
}

function getOrderInfo($time) {
    
     $db = kernel::database();
     $ret['cnt'] = $db->count("SELECT count(*) as c FROM sdb_ome_orders WHERE createtime<={$time}");
     $ret['outBuffer'] = $db->count("SELECT count(*) as c FROM sdb_ome_orders WHERE op_id IS NOT NULL and group_id IS NOT NULL AND status NOT IN ('dead') AND createtime<={$time}");
     $ret['splitCnt'] = $db->count("SELECT count(*) as c FROM sdb_ome_orders WHERE op_id IS NOT NULL and group_id IS NOT NULL AND status NOT IN ('dead') AND process_status in ('splited', 'splitting') AND createtime<={$time}");
     $ret['syncCnt'] = $db->count("SELECT count(*) as c FROM sdb_ome_orders WHERE op_id IS NOT NULL and group_id IS NOT NULL AND status NOT IN ('dead') AND sync in ('succ','fail','run') AND createtime<={$time}");
     return $ret;
}

function getShopInfo() {
    
    $db = kernel::database();
    $list = $db->select("SELECT * FROM sdb_ome_shop WHERE disabled='false'");
    if ($list) {
        foreach($list as $key => $item) {
            
            $list[$key]['addon'] = unserialize($item['addon']);
            $list[$key]['config'] = unserialize($item['config']);
        }
        return $list;
    } else {
        
        return array();
    }
}

function writeFile($fileName, $content) {

    $fp = fopen($fileName, 'a');
    fwrite($fp, $content . "\n");
    fclose($fp);
}

function getFileName($fix) {

    $path = realpath(dirname(__FILE__) . '/../logs/');
    $dataPath = $path . '/data/' . date('Y-m-d', $fix) . '/';
    @mkdir($dataPath, 0777, true);
    return $dataPath . 'data.php';
}


/**
 * 日志
 */
function ilog($str) {
	
	global $domain;
	$filename = dirname(__FILE__) . '/../logs/static_' . date('Y-m-d') . '.log';
	$fp = fopen($filename, 'a');
        echo date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n";
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}