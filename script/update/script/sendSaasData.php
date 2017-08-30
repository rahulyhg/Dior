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
$startTime = mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"));
$endTime = $startTime + 86400;

if (empty($domain)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');
require_once(realpath(dirname(__FILE__).'/../../../') . '/config/saasapi.php');

cachemgr::init(false);
 
$db = kernel::database();

$saasdata = array(
            'servername' => $domain,
            'date'		 => date('Y-m-d', $startTime),
        	'shopexId' => getShopexId(),
			'shops' => getShopsInfo(),
			'expresses' => getSendOrdersNumberByExpresse(),
        );
//$http = kernel::single('base_httpclient');
//$http->post('http://api.saas.taoex.com/api.php',array('appdata' => serialize(array($saasdata))));

define('SASS_APP_KEY', 'taoguan');
define('SAAS_SECRE_KEY', '49F4589687E79D815339B13A73E5FBB4');

$api = new SaasOpenClient();
$api->appkey = SASS_APP_KEY;
$api->secretKey = SAAS_SECRE_KEY;
$api->format = 'json';
$api->execute('application.storedata',array('service_code'=>'taoex-tg','appdata' => serialize(array($saasdata))));


//获取商家的短信企业ShopexId
function getShopexId(){
	$sql ="SELECT value FROM `sdb_base_kvstore`  WHERE `prefix`='taoexlib' AND `key`='account'";
	$rs = $GLOBALS['db']->select($sql);
	$param = unserialize(unserialize($rs[0]['value']));
	ilog($sql);
	return $param['entid'];
}

//获取商家中的每一个店铺的信息 shops
function getShopsInfo(){
    global $startTime,$endTime;
	$sql = "SELECT name,shop_type,shop_id FROM `sdb_ome_shop`";
	$rs = $GLOBALS['db']->select($sql);
	ilog($sql);
	foreach($rs as $key=>$value){
		$shops[$key]['shopname'] = $value['name'];
		$shops[$key]['platform'] = $value['shop_type'];
		
		//收到的订单量
		$newOrdersNumSql = "SELECT COUNT(order_id) AS newOrdersNum FROM `sdb_ome_orders` WHERE `shop_id`='{$value['shop_id']}' AND `createtime`>={$startTime} AND `createtime`<{$endTime}";
		$newOrdersNumrs = $GLOBALS['db']->select($newOrdersNumSql);
		$shops[$key]['newOrdersNum'] = (int)$newOrdersNumrs[0]['newOrdersNum'];
		ilog($newOrdersNumSql);
		//收到的订单金额
		$newOrdersAmountSql = "SELECT SUM(total_amount) AS newOrdersAmount FROM `sdb_ome_orders` WHERE `shop_id`='{$value['shop_id']}' AND `createtime`>={$startTime} AND `createtime`<{$endTime}";
		$newOrdersAmountrs = $GLOBALS['db']->select($newOrdersAmountSql);
		$shops[$key]['newOrdersAmount'] = (float)$newOrdersAmountrs[0]['newOrdersAmount'];
		ilog($newOrdersAmountSql);
		//发送订单数量
		$sendOrdersNumSql =  "SELECT COUNT(order_id) AS sendOrdersNum FROM `sdb_ome_orders` WHERE `shop_id`='{$value['shop_id']}' AND  `sync`='succ' AND `up_time`>={$startTime} AND `up_time`<{$endTime}";
		$sendOrdersNumrs = $GLOBALS['db']->select($sendOrdersNumSql);
		$shops[$key]['sendOrdersNum'] = (int)$sendOrdersNumrs[0]['sendOrdersNum'];
		ilog($sendOrdersNumrs);
	}
	
	return $shops;
}

function getSendOrdersNumberByExpresse() {
    global $startTime,$endTime;
	$sql = "SELECT dc.type AS expresstype, count( o.order_id ) AS cr FROM sdb_ome_dly_corp dc LEFT JOIN sdb_ome_orders o ON dc.corp_id = o.logi_id  WHERE o.`sync`='succ' AND o.`up_time`>={$startTime} AND o.`up_time`<{$endTime} GROUP BY o.logi_id";
	
	$rs = $GLOBALS['db']->select($sql);
	foreach($rs as $key=>$value){
		$expresses[$key]['code'] = $value['expresstype'];
		$expresses[$key]['sendOrdersNum'] = (int)$value['cr'];
	}
	return $expresses;
}


/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/sendSaasData_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
