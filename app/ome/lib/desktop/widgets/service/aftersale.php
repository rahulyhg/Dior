<?php
class ome_desktop_widgets_service_aftersale{
   
    public function get_menugroup(){
        $returnProductObj = &app::get('ome')->model('return_product');
        $returnProcessObj = &app::get('ome')->model('return_process');
        $refundObj = &app::get('ome')->model('refund_apply');
        $data['label'] = '售后服务';
        $data['type'] = 'aftersale';
        $data['value']['0']['count'] =  $refundObj->count(array('status'=>array('0','1')));
        $data['value']['0']['label'] = '待退款';
        $data['value']['1']['count'] = $returnProductObj->count(array('status'=>'1'));
        $data['value']['1']['label'] = '售后待审核';
        $data['value']['2']['count'] = $returnProcessObj->count(array('recieved'=>'false'));
        $data['value']['2']['label'] = '售后待收货';
        $data['value']['3']['count'] = $returnProcessObj->count(array('recieved'=>'true','verify'=>'false'));
        $data['value']['3']['label'] = '售后待质检';
        $data['value']['4']['count'] = $returnProductObj->count(array('status'=>'7'));
        $data['value']['4']['label'] = '待退换货确认';
        return $data;
    }
}