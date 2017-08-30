<?php
class console_finder_foreign_sku{

    function __construct(){
        if($_GET['wms_id'] != '0'){
            unset($this->column_wms);
        }     
    }

    var $column_inner_name = '货品名称';
    function column_inner_name($row){
        $product_id = $row['inner_product_id'];
        $productObj = &app::get('ome')->model('products');
        $product_name = $productObj->getList('name',array('product_id'=>$product_id));
        return $product_name[0]['name'];
    }

    var $column_inner_brand = '商品品牌';
    function column_inner_brand($row){
        $product_id = $row['inner_product_id'];
        $productObj = &app::get('ome')->model('products');
        $brandObj = &app::get('ome')->model('brand');
        $product = $productObj->getList('brand_id',array('product_id'=>$product_id));
        $brand = $brandObj->getList('brand_name',array('brand_id'=>$product[0]['brand_id']));
        return $brand[0]['brand_name'];
    }

    var $column_eidt = '操作';
    var $column_edit_order = COLUMN_IN_HEAD;//排在列头
    function column_eidt($row){
        $wms_id = intval($_GET['wms_id']);

        $node_type = kernel::single('channel_func')->getWmsNodeTypeById($_GET['wms_id']);

        $product_id = $row['inner_product_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        $view = intval($_GET['view']);
        //同步成功 跟 同步中的不出现同步按钮
        if($row['sync_status'] != '2' && $row['sync_status'] != '3'){
 
            if ($node_type == 'qimen') {
                $html = "<a href='index.php?app=console&ctl=admin_goodssync&act=batchSyncDialog&p[0]=".$wms_id."&finder_id=".$finder_id."' target=dialog::{width:690,height:200,title:'同步单个',ajaxoptions:{method:'POST',data:{view:".$view.",inner_product_id:".$product_id."}}} >同步</a>";
            } else {
                $html = "<a href='index.php?app=console&ctl=admin_goodssync&act=sync&wms_id=".$wms_id."&view=".$view."&inner_product_id=".$product_id."&finder_id=".$finder_id."'>同步</a>";
            }


            return $html;
        }
    }

    var $column_wms = '分派到第三方仓库名称';
    var $column_wms_width = '100';
    var $addon_cols = 'inner_sku,wms_id,outer_sku,sync_status';
    function column_wms($row){
        $wms_id = $row[$this->col_prefix.'wms_id'];
        return kernel::single('channel_func')->getChannelNameById($wms_id);
    }


}