<?php
/**
 * 付款service
 * 有关付款方面的扩展功能都可以使用此服务
 * @author dongqiujing
 * @package ome_service_payment
 * @copyright www.shopex.cn 2010.10.14
 *
 */
class ome_service_payment{

    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 添加付款单
     * @access public
     * @param int $payment_id 付款单ID
     */
    public function payment($payment_id){
        $paymentModel = $this->app->model('payments');
        $payment = $paymentModel->dump($payment_id);

        $this->router->setShopId($payment['shop_id'])->add_payment($payment);

        //kernel::single("ome_rpc_request_payment")->add($payment_id);
    }
    
    /**
     * 付款单支付请求
     * @access public
     * @param int $sdf 请求数据
     */
    public function payment_request($payment){
        error_log(var_export($payment,1),3,__FILE__.'.log');
        $this->router->setShopId($payment['shop_id'])->add_payment($payment);

        //kernel::single("ome_rpc_request_payment")->payment_request($sdf);
    }
    
    /**
     * 付款单状态更新
     * @access public
     * @param int $payment_id 付款单ID
     */
    public function status_update($payment_id){
        $paymentModel = $this->app->model('payments');
        $payment = $paymentModel->dump($payment_id);
        
        $this->router->setShopId($payment['shop_id'])->update_payment_status($payment);
        //kernel::single("ome_rpc_request_payment")->status_update($payment_id);
    }
}