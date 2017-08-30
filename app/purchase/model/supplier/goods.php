<?php
/**
 * 供应商商品
 */

class purchase_mdl_supplier_goods extends dbeav_model{
    
    /*
     * 获取供应商商品列表
     */
    function getSupplierGoods($supplier_id=null){
        
        $filter = array("supplier_id"=>$supplier_id);
        $goods = $this->getList('goods_id', $filter, 0, -1);
        foreach ($goods as $v)
        {
            $goodsArr[] = $v['goods_id'];
        }
        if ($supplier_id and !$goodsArr){
            
            $base_filter = array('goods_id'=>'-1');
            
        }elseif ($supplier_id and $goodsArr){
            
            $base_filter = array('goods_id'=>$goodsArr);
        }
        return $base_filter;
    }
    
}
?>