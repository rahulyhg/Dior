<?php
class ome_service_paymethod{

    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 获取前端支付方式
     *
     * @param String $shop_id 店铺ID
     * @return void
     * @author 
     **/
    public function get_paymethod($shop_id)
    {
        $this->router->setShopId($shop_id)->get_paymethod();
    }
}