<?php
/**
*
* 组织销售单数据
*
*/
class siso_data_sales {
    
    /**
     * 重写 组织销售单数据
     * @access public
     * @param Array $delivery_id 发货单ID
     * @return sales_data 销售单数据
    **/

    public function get_sales_data($delivery_id,$deliverytime = false){
        $order_original_data = array();
        $sales_data = array();

        $deliveryObj = &app::get('ome')->model('delivery');
        $orderIds = $deliveryObj->getOrderIdsByDeliveryIds(array($delivery_id));

        $ome_original_dataLib = kernel::single('ome_sales_original_data');
        $ome_sales_dataLib = kernel::single('ome_sales_data');
        foreach ($orderIds as $key => $orderId){
            $order_original_data = $ome_original_dataLib->init($orderId);
            if($order_original_data){
                $sales_data[$orderId] = $ome_sales_dataLib->generate($order_original_data,$delivery_id);
                if(!$sales_data[$orderId]){
                    return false;
                }
            }else{
                return false;
            }
            unset($order_original_data);
        }

        //平摊预估物流运费，主要处理订单合并发货以及多包裹单的运费问题
        $ome_sales_logistics_feeLib = kernel::single('ome_sales_logistics_fee');
        $ome_sales_logistics_feeLib->calculate($orderIds,$sales_data);

        return $sales_data;

    }
}

?>