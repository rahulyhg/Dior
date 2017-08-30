<?php
class ome_desktop_widgets_service_apilog{
       
    public function get_menugroup_bak(){
        $aObj = &app::get('ome')->model('api_log');
        $oObj = &app::get('ome')->model('orders');
        $data['label'] = '同步管理';
        $data['type'] = 'apilog';
        $data['value']['0']['count'] = $aObj->count(array('status'=>'fail','api_type'=>'request'));
        $data['value']['0']['link'] = 'index.php?app=ome&ctl=admin_api_log&act=index&p[0]=fail&p[1]=request';
        $data['value']['0']['label'] = '信息回写失败';
        $data['value']['1']['count'] = $oObj->count(array('is_fail'=>'true'));
        $data['value']['1']['link'] = 'index.php?app=ome&ctl=admin_order_fail&act=index';
        $data['value']['1']['label'] = '订单下载失败';
        return $data;
    }
}