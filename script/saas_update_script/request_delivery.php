<?php
//更新脚本 补发发货单请求
$server_name = '';
$start_time = '';
$end_time = '';

if(isset($argv[1])){
	if(isset($argv[1])){
	    $server_name = $argv[1];
	    $_SERVER['SERVER_NAME'] = $server_name;
	}
	if(isset($argv[2])){
	    $start_time = strtotime($argv[2]);
	}
	if(isset($argv[3])){
	    $end_time = strtotime($argv[3]);
	}
}else{
	$server_name = $_SERVER['SERVER_NAME'];
	if(isset($_REQUEST['start_time'])){
	    $start_time = strtotime($_REQUEST['start_time']);
	}
	if(isset($_REQUEST['end_time'])){
	    $end_time = strtotime($_REQUEST['end_time']);
	}
}

if(!($server_name && $start_time && $end_time)){
	echo 'argv error';
	exit;
}

$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
@include_once(APP_DIR.'/base/defined.php');

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

cachemgr::init(false);
$page = 1;
while(true){
	if(!retry_shipment($page)){
		return false;
	}
	$page++;
}

    function retry_shipment($page,$limit = 100){
    	global $start_time,$end_time;
        //echo('<script>alert("start");</script>');
        $start = ($page-1) * $limit;
        $sql = 'SELECT delivery_id FROM sdb_ome_delivery WHERE process="true" and  create_time >='.$start_time.' and  create_time <='.$end_time.'  LIMIT '.$start.' ,'.$limit;
		//echo $sql;exit;
        $orders = kernel::database()->select($sql);
        if($orders) {
            foreach($orders as $v) {
                $delivery_id = $v['delivery_id'];
 				echo $delivery_id."\n";
                //获取订单编号 sdb_ome_orders:order_bn
                $sql = 'SELECT order_id FROM sdb_ome_delivery_order WHERE delivery_id="'.$delivery_id.'" LIMIT 1';
                $order = kernel::database()->select($sql);
                $order_id = $order[0]['order_id'];
                
                $sql = 'SELECT order_bn FROM sdb_ome_orders WHERE order_id="'.$order_id.'" LIMIT 1';
                $order = kernel::database()->select($sql);
                $order_bn = $order[0]['order_bn'];
                //echo('<script>alert("'.$order_bn.'");</script>');
                
                //检查是否存在api日志
                $sql = 'SELECT log_id FROM sdb_ome_api_log WHERE task_name like "%(订单号:'.$order_bn.',发货单号%" LIMIT 1';
             	$log = kernel::database()->select($sql);
                if($log) {
                    continue;
                }
                
                kernel::database()->exec('update sdb_ome_orders set ship_status = \'1\' where order_id='.$order_id);
             
                $sql = 'update sdb_ome_order_items set sendnum = nums where order_id='.$order_id;
                kernel::database()->exec($sql);
                
                echo $delivery_id." is ok\n";
                error_log(date('Y-m-d H:i:s').' '.$delivery_id."\n",3,DATA_DIR.'/'.basename(__FILE__).'_'.date('Y-m-d').'_'.$_SERVER['SERVER_NAME'].'.txt');
               //$dlyObj = &app::get('ome')->model('delivery');
               //$dlyObj->call_delivery_api($delivery_id);
              //发货API
                foreach(kernel::servicelist('service.delivery') as $object=>$instance){
                    if(method_exists($instance,'delivery')){
                        $instance->delivery($delivery_id);
                    }
                }
               
            }
            
            return true;
        }else{
        	return false;
        }
        
        
    }