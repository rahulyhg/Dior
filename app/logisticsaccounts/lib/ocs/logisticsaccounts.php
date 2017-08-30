<?php
/**
 * 物流对账抓取发货单
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

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');
$db = kernel::database();
cachemgr::init(false);

function crontab_delivery(){
        set_time_limit(0);
        $delivery = kernel::single('logisticsaccounts_estimate')->delivery();
        $now_time = time()-60*60;
        $last_time = &app::get('logisticsaccounts')->getConf('logisticsaccounts.delivery.downtime');
        if(!$last_time){
            $last_time=0;
        }
        echo "此次预估账单 begin(".date('Y-m-d H:i:s',$last_time).")...<br>";
        echo "此次预估账单 end(".date('Y-m-d H:i:s',$now_time).")...<br>";
        $val = ($now_time-$last_time)/86400;
        if($val>1){
            $total = $delivery->get_total($now_time,$last_time);
            echo '共'.$total.'条记录';
            $pagelimit = 100;
            $page = ceil($total/$pagelimit);
            for($i=0;$i<$page;$i++){
                $offset = $pagelimit*($i-1);
                $offset = max($offset,0);
                $data = $delivery->delivery_list($now_time,$last_time,$offset,$pagelimit);
                $result = $this->save_data($data);

            }
        }
    }
?>