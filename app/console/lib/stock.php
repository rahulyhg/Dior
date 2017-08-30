<?php
class console_stock{
    //仓储调拨 商品调拨 选择商品最大数
    const SELECT_PRODUCT_MAX_NUM = 300;

    /**
    * 获取货品类型
    * @access public 
    * @param Int $product_id 货品ID
    * @return string normal/combination/pkg
    */
    public function get_product_type($product_id = ''){
        $product = $this->get_product_data($product_id);
        return $product['type'];
    }

    /**
    * 获取货品基础信息
    * @access public 
    * @param Int $product_id 货品ID
    * @return array
    */
    public function get_product_data($product_id = ''){
        if($product_id == '') return null;
        $products_mdl = app::get('ome')->model('products');
        $product = $products_mdl->getlist('*',array('product_id'=>$product_id),0,1);
        return $product[0] ? $product[0] : null;
    }

    /**
     *  释放出库单预占库存量
     */
    public function clear_stockout_store_freeze($stockdump_bn){
        $oAppro = app::get('console')->model('stockdump');
        $oAppro_items = app::get('console')->model('stockdump_items');
        $pStockObj = kernel::single('console_stock_products');
        $appro_lists = $oAppro_items->getList(
            'stockdump_id,product_id,num',
            array('stockdump_bn'=>$stockdump_bn)
        );
        $appro_data = $oAppro->dump(array('stockdump_bn'=>$stockdump_bn),'from_branch_id,to_branch_id');
        
        foreach($appro_lists as $value){
            //释放出库单预占仓库库存量
            $log_data = array(
                'original_id'=> $value['appropriation_id'],
                'original_type'=>'iostock',#kernel::single('ome_freeze_log')->get_original_type（）方法获取
                'memo'=>'单据出库释放出库单预占仓库库存量',
            );
            $pStockObj->branch_unfreeze($appro_data['from_branch_id'],$value['product_id'],$value['num'],$log_data);
        }
        $appro_lists = null;
        unset($appro_lists);
        return true;
    }
}
?>
