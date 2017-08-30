<?php
class ome_delivery_refuse{

    //发货拒收入库动作
	function do_iostock($reship_id,$io,&$msg){
        $iostock_instance = kernel::single('ome_iostock');
        if ( method_exists($iostock_instance, 'set') ){
            $iostock_data = $this->get_iostock_data($reship_id,$type);

            $iostock_bn = $iostock_instance->get_iostock_bn($type);
            $iostock_instance->set($iostock_bn, $iostock_data, $type, $iostock_msg, $io);
        }
    }

	/**
     * 组织入库数据
     * @access public
     * @param String $iso_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($reship_id,&$type){
        $reshipObj = app::get('ome')->model('reship');
		$reship_items = $reshipObj->getItemList($reship_id);
		$reshipInfo = $reshipObj->dump($reship_id,'reship_bn,order_id');

		$op_name = kernel::single('desktop_user')->get_name();
        $op_name = $op_name ? $op_name : 'system';
        $iostock_data = array();
        if ($reship_items){
            foreach ($reship_items as $k=>$v){
                $iostock_data[$v['item_id']] = array(
                    'branch_id' => $v['branch_id'],
                    'original_bn' => $reshipInfo['reship_bn'],
                    'original_id' => $reship_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => 0.000,
                    'nums' => $v['num'],
                    'cost_tax' => 0,
                    'oper' => $op_name,
                    'create_time' => time(),
                    'operator' => $op_name,
                    'settle_method' => '',
                    'settle_status' => '0',
                    'settle_operator' => '',
                    'settle_time' => '',
                    'settle_num' => '',
                    'settlement_bn' => '',
                    'settlement_money' => '',
                    'order_id'=>$reshipInfo['order_id'],
                );
            }
        }
        $iostock_instance = kernel::service('ome.iostock');
        eval('$type='.get_class($iostock_instance).'::REFUSE_STORAGE;');

        return $iostock_data;
    }

    //拒收退货做负销售单
    function do_sales(){

    }

    //组成销售单数据
    function get_sales_data(){

    }

    
    /**
     * 发送仓库数据.
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function reship_create($reship_id)
    {
        
        $oReship = &app::get('ome')->model('reship');
        $oReship_item = &app::get('ome')->model('reship_items');
        $oReturn = &app::get('ome')->model('return_product');
        $oDelivery_order = &app::get('ome')->model('delivery_order');
        $oDelivery = &app::get('ome')->model('delivery');
        $oOrder = &app::get('ome')->model('orders');
        $reship = $oReship->dump($reship_id,'reship_bn,t_begin,memo,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,return_logi_no,return_logi_name,return_id,order_id,branch_id');

        $reship_item = $oReship_item->getlist('bn,product_name as name,num,price,branch_id',array('reship_id'=>$reship_id),0,-1);
        $branch_id = $reship['branch_id'];
        $iostockdataObj = kernel::single('console_iostockdata');
        $branch = $iostockdataObj->getBranchByid($branch_id);
        $return_id = $reship['return_id'];
        $order_id = $reship['order_id'];
        $delivery_order = $oDelivery_order->dump(array('order_id'=>$order_id),'delivery_id');
        $delivery_id = $delivery_order['delivery_id'];
        $order = $oOrder->dump($order_id,'order_bn');
        $delivery = $oDelivery->dump($delivery_id,'delivery_bn');
        $ship_area = $reship['ship_area'];
        $ship_area = explode(':',$ship_area);
        $ship_area = explode('/',$ship_area[1]);
        $reship_data = array(
            'reship_bn'=>$reship['reship_bn'],
            'branch_id'=>$branch_id,
            'branch_bn'=>$branch['branch_bn'],
            'create_time'=>$reship['t_begin'],
            'memo'=>$reship['memo'],
            'original_delivery_bn'=>$delivery['delivery_bn'],
            'logi_no'=>$reship['return_logi_no'],
            'logi_name'=>$reship['return_logi_name'],
            'order_bn'=>$order['order_bn'],
            'receiver_name'=>$reship['ship_name'],
            'receiver_zip'=>$reship['ship_zip'],
            'receiver_state'=>$ship_area[0],
            'receiver_city'=>$ship_area[1],
            'receiver_district'=>$ship_area[2],
            'receiver_address'=>$reship['ship_addr'],
            'receiver_phone'=>$reship['ship_tel'],
            'receiver_mobile'=>$reship['ship_mobile'],
            'receiver_email'=>$reship['ship_email'],
            'storage_code'=>$branch['storage_code'],
            'items'=>$reship_item
        );
        return $reship_data;
    } // end func

    /**
     * 更新发货单状态
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function update_orderStatus($order_id)
    {
        $orderObj = app::get('ome')->model('orders');
        
        $order_sum = $orderObj->db->selectrow('SELECT sum(sendnum) as count FROM sdb_ome_order_items WHERE order_id='.$order_id.' AND sendnum != return_num');

        $ship_status = ($order_sum['count'] == 0) ? '4' : '3';
        $orderObj->db->exec("UPDATE sdb_ome_orders SET ship_status='".$ship_status."' WHERE order_id=".$order_id);
        
        
        
    }
}
