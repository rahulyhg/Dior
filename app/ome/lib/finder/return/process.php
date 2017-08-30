<?php
class ome_finder_return_process{
    var $detail_basic = "收货/质检详情";
    
    function detail_basic($por_id){
        $render = app::get('ome')->render();
        $oProduct_pro = &app::get('ome')->model('return_process');
        $oOrder = &app::get('ome')->model('orders');
        $oProduct_pro_detail = $oProduct_pro->product_detail($por_id);
        $render->pagedata['pro_detail'] = $oProduct_pro_detail;
        if (!is_numeric($oProduct_pro_detail['attachment'])){
           $render->pagedata['attachment_type'] = 'remote';
        }
        $render->pagedata['order'] = $oOrder->dump($oProduct_pro_detail['order_id']);
        return $render->fetch('admin/sv_charge/detail.html');
    }

}
?>