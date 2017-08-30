<?php
/**
 * 退货service
 * 有关退货方面的扩展功能都可以使用此服务
 * @author dongqiujing
 * @package ome_service_reship
 * @copyright www.shopex.cn 2010.10.14
 *
 */
class ome_service_reship{

    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 添加退货单
     * @access public
     * @param int $reship_id 退货单ID
     */
    public function reship($reship_id){
        $reshipModel = $this->app->model('reship');
        $reship = $reshipModel->dump($reship_id);

        $this->router->setShopId($reship['shop_id'])->add_reship($reship);
        //kernel::single("ome_rpc_request_reship")->add($reship_id);
    }
    
    /**
     * 退货单状态更新
     * @access public
     * @param int $reship_id 退货单ID
     */
    public function update_status($reship_id){
        $reshipModel = $this->app->model('reship');
        $reship = $reshipModel->dump($reship_id);

        $this->router->setShopId($reship['shop_id'])->update_reship_status($reship);
        
        //kernel::single("ome_rpc_request_reship")->status_update($reship_id);
    }
}