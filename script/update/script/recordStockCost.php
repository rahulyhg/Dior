<?php
/**
 * 定时脚本执行记录每天的库存成本 
 * 
 * @author yangminsheng@shopex.com
 * @version 1.0
 */

//php d:\wamp\www\prerelease20120904\script\update\script\recordStockCost.php 192.168.132.54/prerelease20120904 11:04

$domain = $argv[1];

$order_id = $argv[2];

$host_id = $argv[3];

//执行具体的时间点

//默认是每天的4点

if (empty($domain)) {

    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

recordStockCost();

function recordStockCost(){

    global $domain;

    if( app::get('tgstockcost')->is_installed()){

        $stock_msg = kernel::single('tgstockcost_crontab_stockcost')->set();

        ilog("Record daily StockCost $domain message information: \n\t     ".implode('    ',$stock_msg));

    }else{
        ilog("Record daily StockCost $domain error. message : ".$msg);
        die($msg);
    }
}


/**
 * 日志
 */
function ilog($str) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/update_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    echo date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n";
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}





