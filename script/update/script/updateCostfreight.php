<?php
/**
 * 更新物流公司运费
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
require_once(dirname(__FILE__) . '/../../lib/init.php');
cachemgr::init(false);
kernel::single('logistics_dly_corp')->turn_area_conf();

/*大地区初始化数据*/
$sql = "INSERT INTO `sdb_logistics_area` (`area_id`, `local_name`, `region_id`, `region_name`, `ordernum`, `disabled`) VALUES
(1, '华北地区', '1,42,814,1989,2340', '北京,天津,河北,内蒙古,山西', NULL, 'false'),
(2, '东北地区', '1176,1573,1874', '黑龙江,吉林,辽宁', NULL, 'false'),
(3, '华东地区', '21,104,227,1643,1763,2182,3133', '上海,安徽,福建,江苏,江西,山东,浙江', NULL, 'false'),
(4, '华中地区', '998,1320,1436', '河南,湖北,湖南', NULL, 'false'),
(5, '华南地区', '423,566,788', '广东,广西,海南', NULL, 'false'),
(6, '西南地区', '62,690,2589,2792,2987', '重庆,贵州,四川,西藏,云南', NULL, 'false'),
(7, '西北地区', '322,2103,2130,2471,2873', '甘肃,宁夏,青海,陕西,新疆', NULL, 'false'),
(8, '港澳台地区', '3235,3239,3242', '香港,澳门,台湾', NULL, 'false')";
kernel::database()->exec($sql);

kernel::single('logistics_dly_corp')->turn_dly_corplist();

ilog("Update $domain Ok.");

/**
 * 日志
 */
function ilog($str) {
	
	global $domain;
	$filename = dirname(__FILE__) . '/../logs/logistics_' . date('Y-m-d') . '.log';
	$fp = fopen($filename, 'a');
        echo date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n";
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}

