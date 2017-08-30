<?php
/**
 * 根据传入的域名做初始化工作
 * 
 * @author chenping<chenping@shopex.cn>
 * @version 1.0
 */
error_reporting(E_ALL ^ E_NOTICE);

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {
    die('No Params');
}

set_time_limit(0);


require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$rows = app::get('ome')->model('branch')->getList('branch_id,branch_bn,attr');
$branches_unline = array();
$branches_all = array();
foreach ($rows as $key=>$value) {
    if ($value['attr'] == 'false') {
        $branches_unline[$value['branch_id']] = $value['branch_bn'];
    }
    $branches_all[$value['branch_id']] = $value['branch_bn'];
}

$relation = app::get('ome')->getConf('shop.branch.relationship');
if ($relation && $branches_unline) {
    $change = false;
    foreach ($relation as $shop_bn=>$branches) {
        foreach ($branches as $branch_id=>$branch_bn) {
            if ($branches_unline[$branch_id] && $branches_unline[$branch_id] == $branch_bn) {
                $change = true;
                unset($relation[$shop_bn][$branch_id]);
                if (empty($relation[$shop_bn])) {
                    unset($relation[$shop_bn]);
                }
                ilog(' unbind ' . $branch_bn.'=>'.$shop_bn);
            }
        }
    }
    if ($change) {
        app::get('ome')->setConf('shop.branch.relationship',$relation);
    }
}

# 过滤掉所有不存在仓库
if ($relation) {
    $change = false;
    foreach ($relation as $shop_bn=>$branches) {
        foreach ($branches as $branch_id=>$branch_bn) {
            if (!isset($branches_all[$branch_id])) {
                $change = true;
                unset($relation[$shop_bn][$branch_id]);
                if (empty($relation[$shop_bn])) {
                    unset($relation[$shop_bn]);
                }
                ilog(' noexist ' . $branch_bn.'=>'.$shop_bn);
            }
        }
    }
    if ($change) {
        app::get('ome')->setConf('shop.branch.relationship',$relation);
    }
}

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/branchshop' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
