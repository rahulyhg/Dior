<?php
class wms_stock{

     /**
     * 库存查询相关方法，2011.11.01更新
     */
    function search_stockinfo($search){
        $db = kernel::database();
        $oProducts = &app::get('ome')->model('products'); 
        $product_ids = array();
        $product_info = array();
        //模糊搜索商品
        $g_list = $oProducts->db->select("SELECT goods_id FROM sdb_ome_goods WHERE name LIKE '%".$search."%' OR bn LIKE '%".$search."%' OR brief LIKE '%".$search."%' OR barcode LIKE '%".$search."%'");
        if($g_list){
            foreach($g_list as $v){
                $t_products = $oProducts->getList("product_id",array('goods_id'=>$v['goods_id']));
                foreach($t_products as $p){
                    $product_ids[] = $p['product_id'];
                }
            }
        }

        //模糊搜索货品
        $p_list = $oProducts->db->select("SELECT product_id FROM sdb_ome_products WHERE bn LIKE '%".$search."%' OR barcode LIKE '%".$search."%'");
        if($p_list){
            foreach($p_list as $v){
                $product_ids[] = $v['product_id'];
            }
        }

        $product_ids = array_unique($product_ids);

        /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        
        if ($branch_ids){
                //获取所属仓库下的货品
                $oBranchProduct = &app::get('ome')->model('branch_product');
                $branch_product = $oBranchProduct->getList('product_id', array('branch_id'=>$branch_ids), 0,-1);
                if ($branch_product)
                foreach($branch_product as $bp){
                    $branch_product_ids[] = $bp['product_id'];
                }
                if ($product_ids or $branch_product_ids);
                $product_ids = array_intersect($product_ids,$branch_product_ids);
        }


        if($product_ids){
            $ids = implode(',', $product_ids);
           
            $sql = "SELECT p.product_id,p.name,p.bn,p.barcode,p.spec_info,bpt.store,p.store-IFNULL(p.store_freeze,0) AS max_store,
            b.name AS branch,b.branch_id
                        FROM sdb_ome_products AS p
                        LEFT JOIN sdb_ome_branch_product AS bpt ON(p.product_id=bpt.product_id)
                        LEFT JOIN sdb_ome_branch AS b ON(bpt.branch_id=b.branch_id)

                        WHERE p.product_id IN (".$ids.") AND b.branch_id in (".implode(',',$branch_ids).")";
            $product_info = $oProducts->db->select($sql);

        }
        $branch_product_posObj = &app::get('ome')->model('branch_product_pos');
        foreach($product_info as $key=>$val){
            $pos_string ='';
            $posLists = $branch_product_posObj->get_pos($val['product_id'], $val['branch_id']);
            if(count($posLists) > 0){
                foreach($posLists as $pos){
                    $pos_string .= $pos['store_position'].",";
                }
                $product_info[$key]['store_position'] = substr($pos_string,0,strlen($pos_string)-1);
            }
        }

       
        return $product_info;
    }
}
?>