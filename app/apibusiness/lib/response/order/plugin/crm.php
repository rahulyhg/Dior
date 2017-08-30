<?php
/**
* 订单CRM处理（针对淘分销）
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author wangkezheng<wangkzheng@shopex.cn>
* @version $Id: crm.php 2015-1-30
*/
class apibusiness_response_order_plugin_crm extends apibusiness_response_order_plugin_abstract{

    /**
     * 更新完成后操作
     *
     * @return void
     * @author
     **/
    public function postUpdate(){
        $order_id = $this->_platform->_tgOrder['order_id'];
    
        $orderItemObj  = &app::get('ome')->model("order_items");
        $orderObjectObj  = &app::get('ome')->model("order_objects");
        $Obj_preprocess = &app::get('ome')->model('order_preprocess');
    
        #删除CRM相关记录记录(shop_goods_id=-1是， CRM赠品类型)
        $orderItemObj->delete(array('order_id'=>$order_id,'shop_goods_id'=>'-1','item_type' => 'gift'));
        $orderObjectObj->delete(array('order_id'=>$order_id,'shop_goods_id'=>'-1','obj_type' => 'gift'));
        $Obj_preprocess->delete(array('preprocess_order_id'=>$order_id,'preprocess_type'=>'crm'));
        $msg = null;
        kernel::single('ome_preprocess_crm')->process($order_id,$msg,1);#重新获取CRM赠品
        return true;
    }
}