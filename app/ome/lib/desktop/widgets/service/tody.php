<?php
class ome_desktop_widgets_service_tody{
	
	public function get_menugroup(){
        $today = array(strtotime(date('Y-m-d',time())),strtotime(date('Y-m-d',(time()+86400))));
        $orderObj = &app::get('ome')->model('orders');
        $data['label'] = '今日';
        $data['type'] = 'tody';
        $data['value']['0']['count'] = $orderObj->count(array('createtime|between' =>$today));
        $data['value']['0']['link'] = 'javascript:void(0)';
        $data['value']['0']['label'] = '今日订单';
        $data['value']['1']['count'] = $orderObj->count(array('process_status' =>'unconfirmed','createtime|between' =>$today, 'status'=>'active'));
        $data['value']['1']['link'] = 'javascript:void(0)';
        $data['value']['1']['label'] = '今日未确认';
        $data['value']['2']['count'] = $orderObj->count(array('ship_status' =>'1','createtime|between' =>$today));
        $data['value']['2']['link'] = 'javascript:void(0)';
        $data['value']['2']['label'] = '今日已发货';
        return $data;
	}
}