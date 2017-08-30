<?php
/**
 * 重新计算货品表的库存
 * 
 * @author chenping<chenping@shopex.cn>
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

$offset = 0; $limit = 9000;
do {
    # 库存表中货品总数
    $branch_product = $db->selectlimit('select product_id,sum(store) as _s from sdb_ome_branch_product group by(product_id)',$limit,$offset);
    if( !$branch_product ) break;
    foreach ($branch_product as $key=>$value) {
        if ($value['product_id']) {
            $product = $db->selectrow('select bn,product_id,store from sdb_ome_products where product_id='.$value['product_id']);
            $db->exec('update sdb_ome_products set store='.$value['_s'].' WHERE product_id='.$value['product_id'].' AND store!='.$value['_s'].' limit 1');
            if ($db->affect_row()) {
                ilog('bn:'.$product['bn'].' product_id:'.$product['product_id'].' store:'.$product['store']);
            }
        }
    }
    
    $offset += $limit;
}while(true);

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/updateProductStore' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
