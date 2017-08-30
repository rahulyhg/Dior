<?php
class logisticsaccounts_estimate{
    static private $__delivery_instance = '';
    /**
     *保存数据至预估表
     */
     function save_data($dataList){
        $estimateObj = &app::get('logisticsaccounts')->model('estimate');
        $delivery=self::delivery();
        foreach($dataList as $k=>$v){
            if($v['logi_no']!='' && $v['delivery_bn']!=''){
                $filter = array('logi_no'=>$v['logi_no'],'delivery_bn'=>$v['delivery_bn']);
            }else if($v['delivery_bn']!='' && $v['logi_no']==''){
                $filter = array('delivery_bn'=>$v['delivery_bn']);

            }
            $estimate = $estimateObj->dump($filter,'eid');
            if(!$estimate){
                $data = array();
                $data['is_cod'] = $v['is_cod'];
                $data['branch_id'] = $v['branch_id'];
                $branch = $delivery->get_branch($v['branch_id']);
                $data['branch_name'] = $branch['name'];
                $data['shop_id'] = $v['shop_id'];
                $shop = $delivery->get_shop($v['shop_id']);
                $data['shop_name'] = $shop['name'];
                $data['delivery_time'] = $v['delivery_time'];
                $data['delivery_bn'] = $v['delivery_bn'];
                $logi = $delivery->get_loginame($v['logi_name']);
                $data['logi_id'] = $logi['corp_id'];
                $data['logi_name'] = $v['logi_name'];
                $data['cost_protect'] = $v['cost_protect'];
                $data['ship_name'] = $v['ship_name'];
                $data['weight'] = $v['weight'];
                $data['delivery_cost_expect'] = $v['delivery_cost_expect'];
                $data['money_expect'] = $v['money_expect'];
                $data['ship_province'] = $v['ship_province'];
                $data['ship_city'] = $v['ship_city'];
                $data['ship_district'] = $v['ship_district'];
                $data['delivery_bn'] = $v['delivery_bn'];
                $data['logi_no'] = $v['logi_no'];
                $data['order_bn'] = $v['order_bn'];
                $data['ship_area'] = $v['ship_area'];
                $data['ship_addr'] = $v['ship_addr'];
                $result = $estimateObj->save($data);
            }
            unset($data,$v);
        }
        unset($dataList);
        return true;
    }

    function delivery(){
        $setup_config = base_setup_config::deploy_info();
        $product_name = $setup_config['product_name'];
        if($product_name=='taog'){
            $classname = 'logisticsaccounts_taoguan_delivery';
        }else{
            $classname = 'logisticsaccounts_ocs_delivery';
        }
        $obj = new $classname;
        if($obj instanceof logisticsaccounts_interface_estimate){
            self::$__delivery_instance = $obj;
        }else{
            trigger_error('delivery must implements logisticsaccounts_interface_estimate!', E_USER_ERROR);
            exit;
        }
        return self::$__delivery_instance;
    }

    function crontab_delivery(){
        set_time_limit(0);
        $delivery = $this->delivery();
        $now_time = mktime(0,0,0,date('m'),date('d'),date('Y'));

        $last_time = &app::get('logisticsaccounts')->getConf('logisticsaccounts.delivery.downtime');

        if($last_time){
            $last_time =intval($last_time)==0 ? strtotime(date('Y-m-d')) : $last_time;
            echo "此次预估账单 begin(".date('Y-m-d H:i:s',$last_time).")...<br>";
        }
        echo "此次预估账单 end(".date('Y-m-d H:i:s',$now_time).")...<br>";

        $val = ($now_time-$last_time)/86400;
        if($val>=1 || !$last_time){
            $total = $delivery->get_total($now_time,$last_time);
            echo '共'.$total.'条记录';

            if($total>0){
                $pagelimit = 100;
                $page = ceil($total/$pagelimit);
                for($i=1;$i<=$page;$i++){
                    $offset = $pagelimit*($i-1);
                    $offset = max($offset,0);
                    $data = $delivery->delivery_list($now_time,$last_time,$offset,$pagelimit);
                    $result = $this->save_data($data);
                    unset($data,$result);
                }
                app::get('logisticsaccounts')->setConf('logisticsaccounts.delivery.downtime',$now_time);
            }
        }
    }
}
?>