<?php

class omeanalysts_mdl_ordersPrice extends dbeav_model{

    function price_interval($data = null){
        $interval_price = &app::get('omeanalysts')->model('interval');
        $interval_list = $interval_price->getList();
        $db = kernel::database();
        $order_price = array();
        
        $data['to'] = $data['to'] + 86400;

        foreach($interval_list as $v){

            if(empty($data['shop_id'])){
                $sql = "select sum(num) as num from sdb_omeanalysts_ordersPrice where interval_id = ".$v['interval_id']." AND dates >= ".$data['from']." AND dates <= ".$data['to']."";
                $info = $db->selectrow($sql);
                $order_price[] .= $info['num'];
            }else{
                $sql = "select sum(num) as num from sdb_omeanalysts_ordersPrice where interval_id = ".$v['interval_id']." AND dates >= ".$data['from']." AND dates <= ".$data['to']." AND shop_id = '".$data['shop_id']."'";
                $info = $db->selectrow($sql);
                $order_price[] .= $info['num'];
            }
        }

        return $order_price;

    }

    function del(){
        $sql = "truncate table sdb_omeanalysts_interval";
        kernel::database()->exec($sql);
    }
}
?>