<?PHP
/**
 * 导出白名单
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_export_whitelist
{

    static public function allowed_lists($source=''){
        $data_source = array(
            'ome_mdl_orders' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'order_id', 'structure'=>'multi'),
            'sales_mdl_sales' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'sale_id', 'structure'=>'multi'),
            'ome_mdl_goods' => array('cansplit'=>0, 'splitnums'=>200),
            'iostock_mdl_iostocksearch' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'iostock_id', 'structure'=>'single'),
            'omedlyexport_mdl_ome_delivery' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'delivery_id', 'structure'=>'spec'),
            'wms_mdl_inventory' => array('cansplit'=>0, 'splitnums'=>200),
            'omeanalysts_mdl_ome_goodsale' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'item_id', 'structure'=>'single'),
            'omeanalysts_mdl_ome_products' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'omeanalysts_mdl_ome_sales' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'omeanalysts_mdl_ome_aftersale' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'item_id', 'structure'=>'single'),
            'omeanalysts_mdl_ome_shop' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'omeanalysts_mdl_ome_income' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'omeanalysts_mdl_ome_cod' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'delivery_id', 'structure'=>'single'),
            'omeanalysts_mdl_ome_branchdelivery' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'tgstockcost_mdl_costselect' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'tgstockcost_mdl_branch_product' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'omeanalysts_mdl_ome_goodsrank' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'omeanalysts_mdl_ome_storeStatus' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'omeanalysts_mdl_ome_delivery' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'finance_mdl_bill_order' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'finance_mdl_ar_statistics' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'finance_mdl_analysis_bills' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'finance_mdl_analysis_book_bills' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'console_mdl_branch_product' => array('cansplit'=>0, 'splitnums'=>200),
            'wms_mdl_branch_product' => array('cansplit'=>0, 'splitnums'=>200),
            'drm_mdl_distributor_product_sku' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'inventorydepth_mdl_shop_frame' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'ome_mdl_refund_apply' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'ome_mdl_statement' => array('cansplit'=>1, 'splitnums'=>200, 'structure'=>'single'),
            'ome_mdl_reship' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'reship_id', 'structure'=>'multi'),
            'invoice_mdl_order' => array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'id', 'structure'=>'single'),
            'wms_mdl_delivery'=>array('cansplit'=>1, 'splitnums'=>200, 'primary_key' => 'delivery_id', 'structure'=>'spec'),

        );

        if(!empty($source)){
        	return isset($data_source[$source]) ? $data_source[$source] : '';
    	}else{
    		return $data_source;	
    	}
    }
}