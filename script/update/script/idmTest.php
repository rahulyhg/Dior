<?php
/**
 * 批量校验自动脚本
 *
 * @author chenping<chenping@shopex.cn>
 * @version $2012-8-22 11:03Z
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];
$shop_id = $argv[4];
$iids = (array)$argv[5];
if (empty($domain) || empty($order_id) || empty($host_id) || empty($iids) || empty($shop_id) ) {
    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$_SERVER['HTTP_HOST'] = $domain;
$iids = array(
'16279178308','16317470099','14908842475',
'18671204214',
'	18672272859',
'15820347876',
'16321785469',
'17702935273',
'20597580755',
'	18672668838',
'14912521532',
'16038746072',
'17625263665',
'20717896320',
'14906870424',
'16260006910',
'16385623841',
'16321261609',
'16321209148',
'15333857150',
);
$shop_id = 'bde6365970a6f89413f76778e2e03eae';
$result = kernel::single('inventorydepth_taog_rpc_request_shop_items')->items_list_get($iids,$shop_id);

print_r($result);

ilog(var_export($result,true));

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/idmTest' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}