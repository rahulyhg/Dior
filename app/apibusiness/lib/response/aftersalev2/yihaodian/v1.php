<?php
class apibusiness_response_aftersalev2_yihaodian_v1 extends apibusiness_response_aftersalev2_v1{
    /**
     * 订单object列表
     * @var Array
     */
     static public $_orderObjectlist = array();
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        return parent::canAccept($tgOrder);
    }

    /**
     * 添加售后单
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        parent::add();
        $this->aftersale_add();
    }

    protected function aftersale_additional($returninfo){
        $oReturn_yhd = app::get(self::_APP_NAME)->model('return_product_yihaodian');
        $return_yhd_data = array(
                    'return_id'  => $returninfo['return_id'],
                    'return_bn'  => $returninfo['return_bn'],
                    'shop_id'    => $returninfo['shop_id'],
                    'sendbackaddress'=> $this->_refundsdf['receiver_address'],
                    'receive_state'=>$this->_refundsdf['good_status'],
            );
         $oReturn_yhd->save($return_yhd_data);

    }
    /**
     * 更新退款单状态
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        parent::status_update();

    }

    protected function format_data($sdf){
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order_objectModel = app::get(self::_APP_NAME)->model('order_objects');
        $order_bn    = $sdf['tid'];
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn),'order_id');
        $order_id = $tgOrder['order_id'];
        
        if ($sdf['refund_item_list']) {
            $refund_item_list = json_decode($sdf['refund_item_list'],true);
            $refund_item_list = $refund_item_list['return_item'];
            $return_item = array();
            $return_item = $this->_getOrderobject($order_id,$refund_item_list);
            $sdf['refund_item_list'] = $return_item;
        }
        return $sdf;
    }

    private function _getOrderobject($order_id,$refund_item_list){
        $order_objectModel = app::get(self::_APP_NAME)->model('order_objects');
        $oOrder_items = app::get(self::_APP_NAME)->model('order_items');
        $item_list = array();
        foreach ($refund_item_list as $k=>$refund_item ) {
            $oid = trim($refund_item['oid']);
            $order_object = $order_objectModel->dump(array('order_id'=>$order_id,'oid'=>$oid),'*');
            if ($order_object) {
                $bn = $order_object['bn'];
                $obj_type = $order_object['obj_type'];
                $obj_id = $order_object['obj_id'];
                $order_items = $oOrder_items->getlist('bn,price,nums',array('obj_id'=>$obj_id));
                foreach ( $order_items as $ok=>$ov ) {
                    $item_list[$ok]=array(
                        'num'=>   $ov['nums'],
                        'bn'=>   $ov['bn'],
                        'price'=>$ov['price'],
                    );
                }
            }else{
                $item_list[$k] = array(
                    'bn'=>$refund_item['outer_id'],
                    'num'=>$refund_item['num'],
                    'price'=>$refund_item['price'],
                );
            }
        }


        return $item_list;
    }
}

?>