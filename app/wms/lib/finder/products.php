<?php
class wms_finder_products{

    var $detail_basic = "库存详情";

    function detail_basic($product_id){
        if($_POST) {
            $oBranchPro = &app::get('ome')->model('branch_product'); 
            $branch_id = $_POST['branch_id'];
            $product_ids = $_POST['product_id'];
            $safe_store = $_POST['safe_store'];
            $is_locked = $_POST['is_locked'];
            for($k=0;$k<sizeof($branch_id);$k++) {
                $oBranchPro -> update(
                    array('safe_store'=>$safe_store[$k],'is_locked'=>$is_locked[$k]),
                    array(
                        'product_id'=>$product_ids[$k],
                        'branch_id'=>$branch_id[$k]
                    )
                );
            }
            
            $sql = 'UPDATE sdb_ome_products SET alert_store=0 WHERE product_id='.$product_ids[$k-1];
            kernel::database()->exec($sql);
            
            $sql = 'UPDATE sdb_ome_products SET alert_store=999 WHERE product_id IN
                (
                    SELECT product_id FROM sdb_ome_branch_product
                    WHERE product_id='.$product_ids[$k-1].' AND safe_store>(store - store_freeze + arrive_store)
                )
            ';
            kernel::database()->exec($sql);
        }
        $render = app::get('wms')->render();
        $productObj = kernel::single('wms_receipt_products');
        
        $render->pagedata['pro_detail'] = $productObj->products_detail($product_id);
        return $render->fetch('admin/stock/detail_stock.html');
    }


    var $addon_cols='product_id,store_freeze,alert_store';
   
   
    var $column_arrive_store='在途库存';
    var $column_arrive_store_width='60';
    var $column_arrive_store_order = COLUMN_IN_TAIL;//排在列尾
    function column_arrive_store($row){
        $product_id = $row[$this->col_prefix.'product_id'];
        
        $productObj = kernel::single('wms_receipt_products');
        $num = $productObj->countBranchProduct($product_id,'arrive_store');
        return (int)$num;
    }
}

?>