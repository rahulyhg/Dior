<?php
class ome_desktop_widgets_service_order{

    public function get_menugroup(){
        $orderObj = &app::get('ome')->model('orders');
        $data['label'] = '订单';
        $data['type'] = 'order';
        $data['value']['0']['count'] = $orderObj->count(array('ship_status' =>array('0','2'),'status' => 'active','is_fail'=>'false','disabled'=>'false','archive'=>0));
        $data['value']['0']['link'] = 'index.php?app=ome&ctl=admin_order&act=index&view=6';
        $data['value']['0']['label'] = '待发货';
        $data['value']['1']['count'] = $orderObj->count(array('process_status' =>'unconfirmed','confirm'=>'N','assigned'=>'assigned','abnormal'=>'false','is_fail'=>'false'));
        $data['value']['1']['link'] = 'index.php?app=ome&ctl=admin_order&act=confirm&flt=unmyown&view=1';
        $data['value']['1']['label'] = '未确认';
        $data['value']['2']['count'] = $orderObj->count(array('pay_status' => array('0','3','4'),'status' => 'active','is_fail'=>'false','process_status|noequal' => array('cancel','remain_cancel'),'pay_status_set'=>'yes'));
        $data['value']['2']['link'] = 'index.php?app=ome&ctl=admin_finance&act=index';
        $data['value']['2']['label'] = '待付款';
        if(app::get('replacesku')->is_installed()){
            $data['value']['3']['count'] = $orderObj->count(array('status'=>'active','is_fail'=>'true','auto_status'=>0));
        }else{
            $data['value']['3']['count'] = $orderObj->count(array('status'=>'active','is_fail'=>'true'));
        }
        $data['value']['3']['link'] = 'index.php?app=ome&ctl=admin_order_fail&act=index';
        $data['value']['3']['label'] = '失败订单';
        return $data;
    }
}