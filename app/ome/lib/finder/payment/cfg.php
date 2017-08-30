<?php
class ome_finder_payment_cfg{
    var $detail_basic = "支付方式详情";
    
    function detail_basic($id){
        $render = app::get('ome')->render();
        $cfgObj = &app::get('ome')->model('payment_cfg');
        $cfgShopObj = &app::get('ome')->model('payment_shop');

        $cfg = $cfgObj->dump($id);
        $cfgShop = $cfgShopObj->getShopByPayBn($cfg['pay_bn']);
        $cfg['pay_type'] = ome_payment_type::pay_type_name($cfg['pay_type']);

        $render->pagedata['cfg'] = $cfg;
        $render->pagedata['cfgShop'] = $cfgShop;
        return $render->fetch("admin/system/payment_cfg_detail.html");
    }

    var $addon_cols = "id,pay_bn";
    var $column_relation_shop = "关联店铺";
    var $column_relation_shop_width = "120";
    function column_relation_shop($row){
        $cfgShopObj = &app::get('ome')->model('payment_shop');

        $pay_bn = $row[$this->col_prefix.'pay_bn'];
        $cfgShop = $cfgShopObj->getShopByPayBn($pay_bn);
        $shop = '';
        foreach($cfgShop as $shop){
            $relation_shop .= $shop['name'].' ';
        }
        $value = '<span title="'.$relation_shop.'">'.$relation_shop.'</span>';

        return $value;
    }
}
?>