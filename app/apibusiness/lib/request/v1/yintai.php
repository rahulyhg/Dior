<?php
/**
*银泰(银泰平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
*/
class apibusiness_request_v1_yintai extends apibusiness_request_partyabstract
{
    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        
        $param = array(
            'tid'               => $delivery['order']['order_bn'],
            'company_code'      => $delivery['dly_corp']['type'],
            'logistics_company' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no'      => $delivery['logi_no'] ? $delivery['logi_no'] : '',
            'bn' =>$delivery['good_bn'],
        );

        return $param;
    }// TODO TEST

    public function add_delivery($delivery)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$delivery) {
            $rs['msg'] = 'no delivery';
            return $rs;
        }

        $deliOrderModel = app::get(self::_APP_NAME)->model('delivery_order');
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        if ($delivery['is_bind'] == 'true') {
            $deliOrderList = $deliOrderModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
            if ($deliOrderList) {
                foreach ($deliOrderList as $key => $deliOrder) {
                    $order = $orderModel->dump(array('order_id'=>$deliOrder['order_id']),'ship_status,shop_id,order_bn,is_delivery,mark_text,sync,order_id,self_delivery,createway');

                    if ($order['ship_status'] != '1') {
                        continue;
                    }
                    
                    if ($delivery['shop_id'] != $order['shop_id']) {
                        $mydelivery = $deliOrderModel->dump(array('order_id' => $deliOrder['order_id'],'delivery_id|noequal'=>$delivery['delivery_id']));
                        if ($mydelivery) {
                            kernel::single('ome_service_delivery')->delivery($mydelivery['delivery_id']);
                        }
                        continue;
                    }
                    
                    if ($delivery['shop_id'] != $order['shop_id']) {
                        $mydelivery = $deliOrderModel->dump(array('order_id' => $deliOrder['order_id'],'delivery_id|noequal'=>$delivery['delivery_id']));
                        if ($mydelivery) {
                            kernel::single('ome_service_delivery')->delivery($mydelivery['delivery_id']);
                        }
                        continue;
                    }

                    $delivery['order'] = $order;
                     #要按商品拆分
                    $order_product = $this->get_OrderProductParams($order);
                    foreach ( $order_product as $product ) {
                        $bn = $product['bn'];
                        //$item = $this->shop_item_get($bn,$order['shop_id']);
                        $delivery['good_bn'] = $bn;
                        $this->delivery_request($delivery);
                        
                    }
                    
                }
            }
        } else {
            
            if( !isset($delivery['delivery_id']) ){
                $deliOrder['order_id'] = $delivery['order']['order_id'];
            }else{
                $deliOrder = $deliOrderModel->dump(array('delivery_id'=>$delivery['delivery_id']),'*');
            }
            
            $order = $orderModel->dump(array('order_id'=>$deliOrder['order_id']),'ship_status,order_bn,shop_id,is_delivery,mark_text,sync,order_id,self_delivery,createway');

            if ($order['ship_status'] != '1') {
                return false;
            }

            $delivery['order'] = $order;
             #要按商品拆分
            $order_product = $this->get_OrderProductParams($order);
            //print_r($order_product);
            foreach ( $order_product as $product ) {
                $bn = $product['bn'];
                //$item = $this->shop_item_get($bn,$order['shop_id']);
                $delivery['good_bn'] = $bn;
                $this->delivery_request($delivery);
                
            }
            
        }

        $rs['rsp'] = 'success';

        return $rs;
    }

   

    /**
    * 获取订单商品参数
    *
    */
    private function get_OrderProductParams($order){
        $order_id = $order['order_id'];
        $oOrder_object = app::get(self::_APP_NAME)->model('order_objects');
        $order_object = $oOrder_object->getList('*',array('order_id'=>$order_id));
        
        return $order_object;
    }

    /**
     * 获取单个商品明细
     *
     * @param Int $iid商品ID
     * @param String $shop_id 店铺ID
     * @return void
     * @author
     **/
    public function shop_item_get($iid)
    {
        $goods = array();
        
        $rs = kernel::single('apibusiness_router_request')->setShopId($this->_shop['shop_id'])->items_custom_get($iid);

        if ($rs->rsp == 'fail' || !$rs->data ){
            $this->_apiLog['info'][] = '获取商品('.$rs->msg_id.')失败：' . $iid;
            return array();
        }

        $data = json_decode($rs->data,true);
        $this->_apiLog['info'][] = '获取商品('.$rs->msg_id.')：' . $iid;

        if ($rs->rsp == 'succ' && $data) {
            //$item = $data['sku'];unset($data);
            $goods=array('iid'=>$data['items']['item'][0]['iid']);
        }
        return $goods;
    }
}