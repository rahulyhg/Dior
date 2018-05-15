<?php
/**
 * OMS->WMS 库存查询类
 * @todo 捆绑商品的处理
 * 商品表已经增加了 shop_id 对gurlain官网店铺没有影响
 */
class qmwms_request_inventoryquery{
    //库存查询
    public function inventery_query(){
        //每次查询50个sku 循环查询
        $offset = 0; $limit = 50;
        do{
            $sql = sprintf("select op.bn,op.store from sdb_ome_products op left join sdb_ome_goods og on op.goods_id = og.goods_id where og.is_prepare='false' order BY op.product_id ASC limit %u,%u ",$offset,$limit);//bn in('G002471','G002472') 条件仅调试使用
            $productData  = app::get('ome')->model('products')->db->select($sql);

            if(!empty($productData)){
                kernel::single('qmwms_request_omsqm')->inventoryQuery($productData,$offset,$limit);
            }
            $offset = $offset + $limit;
        }while(!empty($productData));

    }
}