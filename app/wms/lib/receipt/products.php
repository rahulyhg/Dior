<?php
class wms_receipt_products{

   /*
    * 获取库存详情
    *$param int
    *return array
    */
    function products_detail($product_id){
        $oProduct = app::get('ome')->model('products');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        $pro=$oProduct->dump($product_id);
        $oBranch_product=app::get('ome')->model('branch_product_pos');
       
        $oBr_product=app::get('ome')->model('branch_product');
        $sql = 'SELECT
        p.product_id,p.branch_id,p.arrive_store,p.store,p.store_freeze,p.safe_store,p.is_locked,
        bc.name as branch_name
        FROM sdb_ome_branch_product as p
        LEFT JOIN sdb_ome_branch as bc ON bc.branch_id=p.branch_id
        WHERE p.product_id='.$product_id.' AND p.branch_id in ('.implode(',',$branch_ids).')';
       
        $branch_product = $oProduct->db->select($sql);

       
        foreach($branch_product as $key=>$val){
            $pos_string ='';
            $posLists = $oBranch_product->get_pos($val['product_id'], $val['branch_id']);
            if(count($posLists) > 0){
                foreach($posLists as $pos){
                    $pos_string .= $pos['store_position'].",";
                }
                $branch_product[$key]['store_position'] = substr($pos_string,0,strlen($pos_string)-1);
            }
        }

        $pro['branch_product'] = $branch_product;
        $store_total = 0;
        foreach($branch_product as $bp){
            $store_total+=$bp['store'];
        }
        
        $pro['store'] = $store_total;
        return $pro;
    }

    /**
    * 统计自有仓库存
    *
    * $product_id 
    */
    function countBranchProduct($product_id, $column='safe_store'){
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        $sql = "SELECT SUM($column) AS 'total' FROM sdb_ome_branch_product WHERE product_id = $product_id AND branch_id in ('.implode(',',$branch_ids).')";

        $count = kernel::database()->selectrow($sql);

        return $count['total'];
    }
}