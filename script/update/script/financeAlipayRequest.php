<?php
/**
 * 对帐自动脚本
 * 
 * @author chenping<chenping@shopex.cn>
 * @version 1.0
 */
error_reporting(E_ALL ^ E_NOTICE);

$domain = $argv[1];
if (empty($domain)) {
    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

if(!app::get('finance')->is_installed()){
    return ;
}
// 一小时跑一次
$str = '开始执行时间：'.date('Y-m-d H:i:s'); $begintime = time();
kernel::single('finance_cronjob_tradeScript')->trade_search_queue();
kernel::single('finance_cronjob_tradeScript')->get_taskid_result();
kernel::single('finance_cronjob_tradeScript')->taskid_queue();
#kernel::single('finance_cronjob_tradeScript')->autoretry();
kernel::single('finance_cronjob_tradeScript')->get_sales();
#kernel::single('finance_cronjob_autoflagScript')->autoflag_queue();
$str .= ' 结束执行时间：'.date('Y-m-d H:i:s').' 总耗时：'.(time()-$begintime);

ilog($str,'finance');

function ilog($str,$filename) {   
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/' .$filename.date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}