<?php
/**
 * 订单服务
 * 有关订单方面的扩展功能都可以使用此服务
 * @author dongqiujing
 * @package ome_service_order
 * @copyright www.shopex.cn 2010.10.14
 *
 */
class ome_service_order{
    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 订单编辑 iframe
     * @access public
     * @param string $order_id 订单ID
    * @param Bool $is_request 是否发起请求
    * @param Array $ext 扩展参数
     */
    public function update_iframe($order_id,$is_request=true,$ext=array()){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $rs = $this->router->setShopId($order['shop_id'])->update_iframe($order,$is_request,$ext);     

        return $rs;
        //return kernel::single("ome_rpc_request_order")->update_iframe($order_id,$is_request,$ext);
    }
       

    /**
     * 订单编辑
     * @access public
     * @param string $order_id 订单号
     */
    public function update_order($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order($order);
        //kernel::single("ome_rpc_request_order")->update_order($order_id);
    }
    
    /**
     * 订单备注修改
     * @access public
     * @param string $order_id 订单号
     * @param string $memo 订单备注
     */
    public function update_memo($order_id, $memo){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order_memo($order,$memo);  
        //kernel::single("ome_rpc_request_order")->memo_update($order_id, $memo);
    }
    
    /**
     * 订单备注添加
     * @access public
     * @param string $order_id 订单号
     * @param string $memo 订单备注
     */
    public function add_memo($order_id, $memo){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->add_order_memo($order,$memo);  
        //kernel::single("ome_rpc_request_order")->memo_add($order_id, $memo);
    }
    
    /**
     * 订单状态修改
     * @access public
     * @param string $order_id 订单号
     * @param string $status 状态
     * @param string $memo 备注
     * @param string $mode 请求类型:sync同步  async异步
     */
    public function update_order_status($order_id,$status='',$memo='',$mode='sync'){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        return $this->router->setShopId($order['shop_id'])->update_order_status($order,$status,$memo,$mode);  
       //return kernel::single("ome_rpc_request_order")->order_status_update($order_id,$status,$memo,$mode);
    }

    /**
     * 订单暂停与恢复
     * @access public
     * @param string $order_id 订单号
     * @param string $status 订单状态true:暂停 false:恢复
     */
    public function update_order_pause_status($order_id,$status=''){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order_pause_status($order,$status);   
        //kernel::single("ome_rpc_request_order")->order_pause_status_update($order_id, $status);
    }
    
   /**
     * 更新订单发票信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_order_tax($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order_tax($order);     
        //kernel::single("ome_rpc_request_order")->order_tax_update($order_id);
    }
    
    
    /**
     * 买家留言添加
     * @access public
     * @param string $order_id 订单号
     * @param string $memo 买家留言
     */
    public function add_custom_mark($order_id, $memo){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->add_order_custom_mark($order,$memo);  
        
        //kernel::single("ome_rpc_request_order")->custom_mark_add($order_id, $memo);
    }

    /**
     * 更新交易发货人信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_consigner_info($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order_consignerinfo($order);  
        
        //kernel::single("ome_rpc_request_order")->consigner_info_update($order_id);
    }
    
    /**
     * 更新代销人信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_sellingagent_info($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order_sellagentinfo($order);  
        //kernel::single("ome_rpc_request_order")->sellagent_info_update($order_id);
    }
    
    /**
     * 订单失效时间
     * @access public
     * @param string $order_id 订单号
     * @param string $order_limit_time 订单失效时间
     */
    public function update_order_limit_time($order_id,$order_limit_time=''){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order_limittime($order,$order_limit_time);  
        //kernel::single("ome_rpc_request_order")->update_order_limit_time($order_id, $order_limit_time);
    }
    
    /**
     * 更新交易收货人信息
     * @access public
     * @param string $order_id 订单号
     */
    public function update_shippinginfo($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->update_order_shippinginfo($order); 

       // kernel::single("ome_rpc_request_order")->shippinginfo_update($order_id);
    }

    /**
     * 获取发票抬头
     *
     * @return void
     * @author 
     **/
    public function get_invoice($order_bn,$shop_id)
    {
        $rs = $this->router->setShopId($shop_id)->get_order_invoice($order_bn);

        if($rs){
            if($rs->rsp == 'succ'){
                $tmp = json_decode($rs->data,true);
                return $tmp;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 订单批次索引号（返回或更新）
     * @param sdf $orderSdf 订单sdf结构
     * @param string $process （get：返回当前的批次索引号(获取一次都占一个号，慎用)；add：如果已存在批次索引号，则不更新；update：不管有没有批次索引号，都更新）
     */
    public function order_job_no($orderSdf, $process='get'){
        return kernel::single("ome_order_batch")->order_job_no($orderSdf, $process);
    }
    
    public function notify_get_order($shop,$start_time,$end_time){
        kernel::single("ome_rpc_request_order")->notify_get_order($shop,$start_time,$end_time);
    }
    
    public function destroy_running_no($shop_id, $username, $md5){
        return kernel::single("ome_order_batch")->destroy_running_no($shop_id, $username, $md5);
    }

    /**
     * 获取子订单的订单号
     * @access public
     * @param String $oid 子订单号
     * @return 订单号
     */
    public function getOrderBnByoid($oid='',$node_id='') {
        return kernel::single('ome_order')->getOrderBnByoid($oid,$node_id);
    }

    /**
    * 订单号是否存在
    * @access public
    * @param String $order_bn 订单号
    * @param String $node_id 节点ID
    * @return bool
    */
    public function order_is_exists($order_bn='',$node_id=''){
        return kernel::single('ome_order')->order_is_exists($order_bn,$node_id);
    }

}