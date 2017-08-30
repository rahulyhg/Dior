<?php
class monitor_ctl_admin_order extends desktop_controller{
    var $name = "订单作业状况";
    var $workground = "performance";

    function index(){
        $shopObj = &app::get('ome')->model("shop");
        $shopList = $shopObj->getList('shop_id,name');
        $this->pagedata['shopList'] = $shopList;

        $this->page('admin/order.html');
    }

    function getShopData($shop_id){
        $shopObj = &app::get('ome')->model("shop");
        $shop = $shopObj->dump($shop_id);
        if($shop && !empty($shop['name'])){
            $data = app::get('monitor')->getConf('order_monitor'.$shop_id);
            if(!$data['last_modify'] || (time()-$data['last_modify'])>300){
                $orderObj = &app::get('ome')->model('orders');
                $deliveryObj = &app::get('ome')->model('delivery');

                $data = array();
                for($i=7;$i>=0;$i--){
                    $selDate = array();
                    $selDate[] = mktime(0,0,0,date("m"),date("d")-$i,date("Y"));
                    $selDate[] = $selDate[0]+86400;
                    $data[$i]['sel_time'] = date("Y-m-d",$selDate[0]);

                    //订单相关情况
                    $data[$i]['all_order'] = $orderObj->count(array('createtime|between'=>$selDate,'shop_id'=>$shop_id)); //新增订单
                    $data[$i]['cod_order'] = $orderObj->count(array('createtime|between'=>$selDate,'shop_id'=>$shop_id,'is_cod'=>'true')); //货到付款订单
                    $data[$i]['fail_order'] = $orderObj->count(array('createtime|between'=>$selDate,'shop_id'=>$shop_id,'is_fail'=>'true')); //失败订单
                    $data[$i]['abnormal_order'] = $orderObj->count(array('createtime|between'=>$selDate,'shop_id'=>$shop_id,'is_fail'=>'false','abnormal'=>'true')); //异常订单
                    $data[$i]['cancel_order'] = $orderObj->count(array('createtime|between'=>$selDate,'shop_id'=>$shop_id,'status'=>'dead','process_status'=>'cancel')); //取消订单
                    $data[$i]['unconfirmed_order'] = $orderObj->count(array('createtime|between'=>$selDate,'shop_id'=>$shop_id,'status'=>'active','process_status|in'=>array('unconfirmed','confirmed'))); //未确认订单
                    $data[$i]['confirmed_order'] = $orderObj->count(array('createtime|between'=>$selDate,'shop_id'=>$shop_id,'is_fail'=>'false','process_status'=>'splited')); //已确认订单

                    $baseFilter = array('order_createtime|between'=>$selDate,'shop_id'=>$shop_id,'parent_id'=>'0','type'=>'normal');
                    $data[$i]['all_delivery'] = $deliveryObj->count(array_merge($baseFilter,array('status|notin'=>array('cancel','back','return_back')))); //发货单
                    $data[$i]['process_delivery'] = $deliveryObj->count(array_merge($baseFilter,array('process'=>'true','status'=>'succ'))); //已发货
                    $data[$i]['noprocess_delivery'] = $deliveryObj->count(array_merge($baseFilter,array('status'=>array('failed','progress','timeout','ready','stop'),'process'=>'false'))); //未发货
                    $data[$i]['unprint_delivery'] = $deliveryObj->count(array_merge($baseFilter,array('status'=>array('failed','progress','timeout','ready','stop'),'verify'=>'false' ,'process'=>'false','print_status'=>0))); //未打印
                    $data[$i]['print_delivery'] = $deliveryObj->count(array_merge($baseFilter,array('status'=>array('failed','progress','timeout','ready','stop'),'verify'=>'false','process'=>'false','print_status'=>1))); //已打印
                    $data[$i]['unverify_delivery'] = $deliveryObj->count(array_merge($baseFilter,array('status'=>array('failed','progress','timeout','ready','stop'),'verify'=>'false','process'=>'false'))); //未校验
                    $data[$i]['verify_delivery'] = $deliveryObj->count(array_merge($baseFilter,array('status'=>array('failed','progress','timeout','ready','stop'),'verify'=>'true','process'=>'false'))); //已校验
                }
                $data['last_modify'] = time();
                app::get('monitor')->setConf('order_monitor'.$shop_id,$data);
            }

            $this->pagedata['data'] = $data;
            echo $this->fetch('admin/shop_data.html');
        }else{
            echo '<span class="red">获取店铺信息异常...</span>';
        }
    }
}
