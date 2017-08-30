<?php
/**
 * �͵��۲�ѯ
 * @author Chris.Zhang
 * @access public
 * @copyright www.shopex.cn 2010.12.30
 */
class omeanalysts_crontab_script_ordersPrice{

    public function orderPrice(){
        @set_time_limit(0);
        $db = kernel::database();
        //$runtime = $this->runtime();
        //if($runtime == false) return;

        $interval = &app::get('omeanalysts')->model('interval');

        //base_kvstore::instance('omeanalysts_ordersPrice')->fetch('ordersPrice_time',$old_time);
        $old_time = app::get('omeanalysts')->getConf('old_time.ordersPrice_time');//获取上次脚本执行时间
        $new_time = time();
        if(!$old_time){
            //base_kvstore::instance('omeanalysts_priceInterval')->fetch('priceInterval',$arr);
            $arr = app::get('omeanalysts')->getConf('priceInterval');
            $info = unserialize($arr);

            if(empty($info)){
                return;
            }else{
                $interval_sql = "truncate table sdb_omeanalysts_interval";
                $db->exec($interval_sql);

                foreach($info as $v){
                    $new_interval = array();
                    $new_interval['from'] = $v['from'];
                    $new_interval['to'] = $v['to'];
                    $interval->save($new_interval);
                }

            }

            //base_kvstore::instance('omeanalysts_ordersPrice')->store('ordersPrice_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.ordersPrice_time', $new_time);
            //$start_time = 'select min(createtime) as time from sdb_ome_orders';
            //$tmp = $db->selectrow($start_time);
            //$old_time = $tmp['time'];
            $old_time = $new_time - 86400;
            $info_sql = "truncate table sdb_omeanalysts_ordersPrice";
            $db->exec($info_sql);
        }else {
            //base_kvstore::instance('omeanalysts_ordersPrice')->store('ordersPrice_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.ordersPrice_time', $new_time);
        }
        $old_time = strtotime(date('Y-m-d',$old_time));
        $new_time = strtotime(date('Y-m-d',$new_time));
        $val = ($new_time-$old_time)/86400;

        if($val > 1){
            $te = $new_time;
            for($tim=0;$tim < $val;$tim++){

                $teo = $te-86400;
                $times[] = array('from_time'=>$teo,'to_time'=>$te);
                $te -= 86400;
            }
        }else{
            $times[0]=array('from_time'=>$old_time,'to_time'=>$new_time);
        }

        $interval_price = $interval->getList();
        $customerPrice = &app::get('omeanalysts')->model('ordersPrice');

        foreach($times as $time){
            foreach($interval_price as $key => $value){
                if(!empty($value['from']) && !empty($value['to'])){
                    $sql = 'select count(order_id) as num,shop_id,createtime FROM sdb_ome_orders WHERE createtime >= '.$time['from_time'].' AND createtime < '.$time['to_time'].' AND total_amount >= '.$value['from'].' AND total_amount < '.$value['to'].' group by shop_id';
                    $datas = $db->select($sql);
                    foreach($datas as $data){
                        $row = array();
                        $row['dates'] = $data['createtime'];
                        $row['shop_id'] = $data['shop_id'];
                        $row['interval_id'] = $value['interval_id'];
                        $row['num'] = $data['num'];

                        $customerPrice->insert($row);
                    }

                }elseif(!empty($value['from']) && empty($value['to'])){
                    $sql = 'select count(order_id) as num,shop_id,createtime FROM sdb_ome_orders WHERE createtime >= '.$time['from_time'].' AND createtime < '.$time['to_time'].' AND total_amount >= '.$value['from'].' AND total_amount < '.$value['to'].' group by shop_id';
                    $datas = $db->select($sql);

                    foreach($datas as $data){
                        $row = array();
                        $row['dates'] = $data['createtime'];
                        $row['shop_id'] = $data['shop_id'];
                        $row['interval_id'] = $value['interval_id'];
                        $row['num'] = $data['num'];

                        $customerPrice->insert($row);
                    }
                }elseif(empty($value['from']) && !empty($value['to'])){
                    $sql = 'select count(order_id) as num,shop_id,createtime FROM sdb_ome_orders WHERE createtime >= '.$time['from_time'].' AND createtime < '.$time['to_time'].' AND total_amount >= '.$value['from'].' AND total_amount < '.$value['to'].' group by shop_id';
                    //$sql = 'select count(order_id) as num,shop_id,createtime FROM sdb_ome_orders WHERE createtime > 1310659200 AND createtime <= 1316880000 AND total_amount <= '.$value['to'].' group by shop_id';
                    $datas = $db->select($sql);

                    foreach($datas as $data){
                        $row = array();
                        $row['dates'] = $data['createtime'];
                        $row['shop_id'] = $data['shop_id'];
                        $row['interval_id'] = $value['interval_id'];
                        $row['num'] = $data['num'];

                        $customerPrice->insert($row);
                    }
                }

            }
        }
    }

    function runtime(){
        $timepart = date_parse(date('Y-m-d H:i:s',time()));
        return ($timepart['hour'] == '00' && $timepart['minute'] == '30') ? true : false;
    }

}
?>