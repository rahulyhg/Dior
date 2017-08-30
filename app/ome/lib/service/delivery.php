<?php
/**
 * 发货service
 * 有关发货方面的扩展功能都可以使用此服务
 * @author dongqiujing
 * @package ome_service_delivery
 * @copyright www.shopex.cn 2010.10.14
 *
 */
class ome_service_delivery{
    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 添加发货单
     * @access public
     * @param int $delivery_id 发货单ID
     */
    public function delivery($delivery_id,$sync=false){
        $deliveryModel = $this->app->model('delivery');
        $delivery = $deliveryModel->dump($delivery_id);

        $this->router->setShopId($delivery['shop_id'])->add_delivery($delivery);
        
        //kernel::single("ome_rpc_request_shipping")->add($delivery_id);
    }
    
    /**
     * 更改发货单状态
     * @access public
     * @param int $delivery_id 发货单ID
     * @param string $status 发货单状态
     * @param boolean $queue true：进队列  false：立即发起
     */
    public function update_status($delivery_id,$status='',$queue=false){
        $deliveryModel = $this->app->model('delivery');
        $delivery = $deliveryModel->dump($delivery_id);   
        $this->router->setShopId($delivery['shop_id'])->update_delivery_status($delivery,$status,$queue);    
        //kernel::single("ome_rpc_request_shipping")->status_update($delivery_id, $status, $queue);
    }
    
    /**
     * 更改发货物流信息
     * @access public
     * @param int int $delivery_id 发货单ID
     * @param int $parent_id 合并发货单ID
     * @param boolean $queue true：进队列  false：立即发起
     */
    public function update_logistics_info($delivery_id, $parent_id='',$queue=false){
        $deliveryModel = $this->app->model('delivery');
        $delivery = $deliveryModel->dump($delivery_id);

        $this->router->setShopId($delivery['shop_id'])->update_logistics($delivery,$queue);
       // kernel::single("ome_rpc_request_shipping")->logistics_update($delivery_id, $parent_id, $queue);
    }
    #订阅华强宝物流信息
    public function get_hqepay_logistics($delivery_id){
        #检测是否已经绑定华强宝物流
        base_kvstore::instance('ome/bind/hqepay')->fetch('ome_bind_hqepay', $is_ome_bind_hqepay);
        if(!$is_ome_bind_hqepay){
            #先绑定（此次请求的这个物流单，不再去订阅）
            kernel::single('ome_hqepay')->bind();
        }else{
            $deliveryModel = $this->app->model('delivery');
            $delivery = $deliveryModel->dump($delivery_id);
            $this->router->setShopId($delivery['shop_id'])->get_hqepay_logistics($delivery);
            return true;
        }
    }
}