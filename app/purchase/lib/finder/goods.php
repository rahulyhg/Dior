<?php
class purchase_finder_goods{
    var $detail_goods_price = "供应商价格";
    
    function detail_goods_price($goods_id){
        
        $render = app::get('purchase')->render();
        if(!is_numeric($goods_id)) die('访问出错');
        //根据商品ID获取供应商列表
        $sql = ' SELECT a.name,a.supplier_id FROM `sdb_purchase_supplier` a
                 LEFT JOIN `sdb_purchase_supplier_goods` b ON a.supplier_id=b.supplier_id
                 WHERE b.goods_id='.$goods_id;
        $row = kernel::database()->select($sql);
        unset($sql);
        
        //获取供应商此件商品的最高价格、历史价格及当前价格
        foreach ($row as $k=>$v){
            
            //最高、最低SQL
            $sql0 = " SELECT e.`supplier_id` ";
            $sql1 = " from `sdb_purchase_branch_product_batch` e 
WHERE e.`supplier_id`='".$v['supplier_id']."' and e.`product_id` in (select f.`product_id` FROM `sdb_ome_products` f WHERE f.`goods_id`='".$goods_id."')
    GROUP BY e.`supplier_id` ";
            //当前价格sql
            $sqlCurr = " SELECT e.`purchase_price` FROM `sdb_purchase_branch_product_batch` e
                       WHERE e.`supplier_id`='".$v['supplier_id']."' and e.`product_id` in 
                       (SELECT f.`product_id` FROM `sdb_ome_products` f WHERE f.`goods_id`='".$goods_id."') ORDER BY e.`purchase_time` DESC LIMIT 0,1 ";
            
            //最高价格
            $highestField = ",max(e.purchase_price) as hight";
            $lowerestFild = ",min(e.purchase_price) as lowers ";
            $tempsql = $sql0.$highestField.$sql1;
            $highestPrice = kernel::database()->select($tempsql);
            //最低价格
            $tempsql = $sql0.$lowerestFild.$sql1;
            $lowerestPrice = kernel::database()->select($tempsql);
            //当前价格
            $currPrice = kernel::database()->select($sqlCurr);
            
            $v['highestprice'] = $highestPrice[0]['hight'];
            $v['lowerestprice'] = $lowerestPrice[0]['lowers'];
            $v['currPrice'] = $currPrice[0]['purchase_price'];
            $price_list[] = $v;
        }
        unset($row);
        $render->pagedata['supplier_price'] = $price_list;
        $render->pagedata['goods_id'] = $goods_id;
        return $render->fetch('admin/goods/supplier_price.html');
    }
}
?>