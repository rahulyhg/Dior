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

define('COMMAND_MODE', true);

if (empty($domain) || empty($order_id) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$str .= $domain."\t";
$shopRelObj = kernel::single('ome_shop_relation');
$shopObj = &app::get('ome')->model("shop");
$data = array('node_id'=>'');

$result = $shopRelObj->getBindInfosFromMatrix();
if($result){
    if($result['res'] == 'succ'){
        if(count($result['info']) > 0){
            foreach($result['info'] as $k=>$val){
                if($val['to_node_id']){
                    $res = $shopRelObj->unbindWithMatrix($val['to_node_id']);
                    if($res['res'] == 'succ'){
                        $shopObj->update($data, array('node_id'=>$val['to_node_id']));
                        $str .= "unbind shop_name:".$val['to_shop_name'].",node_id:".$val['to_node_id']." succ. \n";
                    }else{
                        $str .= "unbind shop_name:".$val['to_shop_name'].",node_id:".$val['to_node_id']." fail. reason is ".$res['msg']['errorDescription'].". \n";
                    }
                }
            }
        }else{
            $str .= "matrix has no bindnodes. \n";
        }
    }else{
        $str .= "getbindnodes fail. \n";
    }
}

$str .= "\n";

ilog($str);

/**
 * 日志
 */
function ilog($str) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/unbindExpiredTgNodes_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    //echo date("m-d H:i") . "\t" . $domain . "\n";
    fwrite($fp, $str. "\n");
    fclose($fp);
}