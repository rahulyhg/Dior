<?php
class creditorderapi_finder_apiconfig{
    var $column_edit = '配置操作';
    public function column_edit($row){

        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=creditorderapi&ctl=admin_apiconfig&act=edit&ax_id='.$row ['ax_id'].'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
    }

    var $detail_info = '配置信息';
    function detail_info($ax_id){

        $render =  app::get('creditorderapi')->render();
        $render->path[] = array('text'=>app::get('base')->_('商品店铺编辑'));
        $apiconfig = &$render->app->model('apiconfig')->getList('*',array('ax_id'=>$ax_id));
        $apiconfigInfo = json_decode($apiconfig[0]['ax_setting_info'],1);

        //绑定店铺信息
        $shopInfo = app::get('ome')->model('shop')->getList('shop_id,name');

        $apiconfig[0]['shop_id'] = unserialize($apiconfig[0]['shop_id']);
        foreach ($shopInfo as $key=>$shop){
            if(in_array($shop['shop_id'],$apiconfig['0']['shop_id'])){
                $shopInfo[$key]['select'] = '1';
            }
        }
        $render->pagedata['apiconfig'] = $apiconfig[0];
        $render->pagedata['apiconfigInfo'] = $apiconfigInfo;
        $render->pagedata['shops'] = $shopInfo;

        return $render->fetch('admin/ax/detail.html');
    }

}