<?php
/**
 * 订单object类型处理（PKG：捆绑商品）
 * @author Chris.Zhang
 * @access public
 * @copyright www.shopex.cn 2010.01.05
 */
class ome_service_objtype_pkg {
    /*
     * 处理object类型数据
     *
     * @param array $obj object数据
     * 
     * @return array $items 订单详情
     */
    public function process($obj){
        if ($obj['obj_type'] == 'pkg'){
            $item = app::get('ome')->model('order_items')->dump(array('obj_id'=>$obj['obj_id'],'order_id'=>$obj['order_id'],'item_type'=>'pkg'));
            if ($item['delete']=='true'){
                return array();
            }
            $order = app::get('ome')->model('orders')->dump($obj['order_id'],'order_bn');
            
            $items['order_bn']  = $order['order_bn'];
            $items['bn']        = $obj['bn'];
            $items['name']      = $obj['name'];
            $items['unit']      = $obj['unit']?$obj['unit']:'-';
            $items['spec_info'] = $obj['spec_info']?$obj['spec_info']:'-';
            $items['nums']      = $obj['quantity'];
            $items['price']     = $obj['price'];
            $items['sale_price'] = $obj['sale_price'];
            
            return array($items);
        }
        return array();
    }
}