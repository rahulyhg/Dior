<?php
class purchase_setting{
    public function view(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = &app::get('ome')->getConf($set);
        }
        $render = kernel::single('base_render');
        $render->pagedata['setData'] = $setData;

        $html = $render->fetch('admin/setting.html','purchase');
        return $html;
    }

    function all_settings(){
        $all_settings =array(
             'purchase.stock.stockset',
             'purchase.stock_confirm','purchase.stock_cancel',
             'purchase.po_type',
            );
        return $all_settings;
    }
}
