<?php
/**
 * 订单业务逻辑处理
 * @access public
 * @copyright www.shopex.cn 2010.12.16
 * @author ome
 */
class ome_order_order{

    /**
     * 余单撤消
     * @access public
     * @param string $order_id 订单号
     * @param int $revock_price 撤销金额
     * @param int $reback_price 退款金额
     * @return 处理成功或者失败
     */
    public function order_revoke($order_id,$reback_price='',$revock_price=''){
        $flag   = true;//[拆单]发货单打回_成功标志 ExBOY
        
        $oOrder = &app::get('ome')->model("orders");
        $oOrder_items = &app::get('ome')->model("order_items");
        $oProducts = &app::get('ome')->model("products");
        $oShop = &app::get('ome')->model('shop');
        $oOperation_log = &app::get('ome')->model('operation_log');
        
        #[拆单]获取订单关联详情_保存快照 ExBOY
        $order_detail   = $oOrder->dump($order_id, "*", array("order_objects"=>array("*",array("order_items"=>array('*')))));
        
        $order_update['process_status'] = 'remain_cancel';//确认状态：余单撤消
        $order_update['ship_status'] = '1';//发货状态：已发货
        $order_update['cost_item'] = $order_detail['cost_item'] - $revock_price;//商品总额=原商品总额-撤销商品价格
        $order_update['discount'] = $order_detail['discount'] + ($revock_price - $reback_price);//折扣=原折扣+（撤销商品价格-退款金额）
        $order_update['total_amount'] = $order_detail['total_amount']  - $reback_price;
        
        $order_update['archive'] = 1;//订单归档 ExBOY
        
        $filter = array('order_id'=>$order_id);
        
        //打回订单未发货的发货单
        $flag   = $oOrder->rebackDeliveryByOrderId($order_id, true);
        
        #打回发货单失败
        if($flag == false)
        {
            $msg    = '余单撤销失败';
            $oOperation_log->write_log('order_modify@ome', $order_id, $msg);//操作日志
            
            return false;
        }
        //更新订单
        $oOrder->update($order_update, $filter);
        
        //店铺信息
        $shop_detail = $oShop->dump(array('shop_id'=>$order_detail['shop_id']), 'node_type');
        $node_type = $shop_detail['node_type'];
        
        //获取撤消的商品明细
        $cancelitems = array();
        $cancel_sql = "SELECT product_id,bn,nums,sendnum,(nums-sendnum) as cancel_num,((nums-sendnum)*price) as c_total_money FROM `sdb_ome_order_items` WHERE order_id='".$order_id."' AND `sendnum`<`nums` AND `delete`='false' ";
        $cancel_items = kernel::database()->select($cancel_sql);
        if ($cancel_items){
            foreach ($cancel_items as $c_key=>$c_items){
                //减少冻结库存
                $oProducts->chg_product_store_freeze($c_items['product_id'],$c_items['cancel_num'],"-");
                $cancelitems[] = '货号:'.$c_items['bn'].'(购买数:'.$c_items['nums'].',已发货:'.$c_items['sendnum'].',本次撤销:'.$c_items['cancel_num'].'个)';
            }
        }
        $cancelitems = implode(';',$cancelitems);
        $msg = '余单撤销(撤销商品总金额为:'.$revock_price.';明细为:'.$cancelitems;
        
        /*    */
        //更新订单明细
        $order = app::get('ome')->model('orders')->dump($order_id,"order_id",array("order_objects"=>array("*",array("order_items"=>array("*")))));
        if ($order['order_objects']){
            foreach ($order['order_objects'] as $obj){
                if ($service = kernel::service("ome.service.order.remain.".trim($obj['obj_type']))){
                    if (method_exists($service, 'remain_cancel')) $service->remain_cancel($obj);
                }else {
                    if ($service = kernel::service("ome.service.order.remain.goods")){
                        if (method_exists($service, 'remain_cancel')) $service->remain_cancel($obj);
                    }
                }
            }
        }
        
        //操作日志
        $log_id     = $oOperation_log->write_log('order_modify@ome', $order_id, $msg);
        
        #[拆单]余单撤消_保存订单快照 ExBOY
        $oOrder->write_log_detail($log_id, $order_detail);
        
        
        $c2c_shop_list = ome_shop_type::shop_list();
        if (!in_array($node_type, $c2c_shop_list)){
            //订单编辑同步
            if ($service_order = kernel::servicelist('service.order')){
                foreach($service_order as $object=>$instance){
                    if(method_exists($instance, 'update_order')){
                        $instance->update_order($order_id);
                    }
                }
            }
        }
        //发货单同步
        if (in_array($node_type, $c2c_shop_list)){
            //C2C前端店铺回写发货单
            $sql = "SELECT de.`delivery_id` FROM `sdb_ome_delivery` de
                    JOIN `sdb_ome_delivery_order` dor ON dor.`delivery_id`=de.`delivery_id` AND dor.`order_id`='".$order_id."'
                    WHERE de.`logi_no` IS NOT NULL ORDER BY de.`delivery_id` DESC";
            $c2c_delivery = kernel::database()->selectrow($sql);
            $delivery_id = $c2c_delivery['delivery_id'];
            if ($delivery_id){
                if ($service_delivery = kernel::servicelist('service.delivery')){
                    foreach($service_delivery as $object=>$instance){
                        if(method_exists($instance, 'delivery')){
                            $instance->delivery($delivery_id);
                        }
                    }
                }
            }
        }
        
        /*------------------------------------------------------ */
        //-- [拆单]余单撤消后_生成销售单 ExBOY
        /*------------------------------------------------------ */
        $iostock_instance   = kernel::single('ome_iostock');
        $sales_instance     = kernel::single('ome_sales');
        
        #判断是否存在销售单
        $sql        = "SELECT COUNT(*) AS num FROM sdb_ome_sales WHERE order_id='".$order_id."'";
        $sales_row  = kernel::database()->selectrow($sql);
        if(empty($sales_row['num']))
        {
            #获取"最后成功发货"的普通发货单ID
            $sql    = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d 
                        ON(dord.delivery_id=d.delivery_id) WHERE dord.order_id='".$order_id."' AND (d.parent_id=0 OR d.is_bind='true') 
                        AND d.disabled='false' AND d.status='succ' AND d.type='normal' ORDER BY delivery_time DESC";
            $delivery_row   = kernel::database()->selectrow($sql);
            
            $delivery_id    = $delivery_row['delivery_id'];
            if($delivery_id)
            {
                #生成销售单
                $iostock_sales_data = array();
                $iostock_data       = kernel::single('ome_iostocksales')->get_iostock_data($delivery_id);
                $sales_data         = kernel::single('ome_iostocksales')->get_sales_data($delivery_id);
                
                #[余单撤消]计算订单商品发送数量
                $order_items    = $detail_items = $temp_arr = array();
                
                $sql    = "SELECT a.item_id, a.obj_id, a.nums, a.sendnum, a.item_type, b.quantity 
                            FROM sdb_ome_order_items AS a LEFT JOIN sdb_ome_order_objects AS b ON a.obj_id=b.obj_id 
                            WHERE a.order_id='".$order_id."'";
                $temp_arr   = kernel::database()->select($sql);
                foreach ($temp_arr as $key => $val)
                {
                    $item_id    = $val['item_id'];
                    
                    $val['dly_nums']        = intval($val['sendnum'] / $val['nums'] * $val['quantity']);//已发货数量
                    $order_items[$item_id]  = $val;
                }
                
                $delivery_items_detailObj = &app::get('ome')->model('delivery_items_detail');
                $temp_arr   = $delivery_items_detailObj->getList('item_detail_id, order_item_id, order_obj_id, number', array('order_id'=>$order_id));
                foreach ($temp_arr as $key => $val)
                {
                    $item_detail_id     = $val['item_detail_id'];
                    $order_item_id      = $val['order_item_id'];
                    
                    $val['dly_nums']    = intval($order_items[$order_item_id]['dly_nums']);//总发货数量
                    
                    $detail_items[$item_detail_id]  = $val;
                }
                
                #[余单撤消]格式化商品发货数量_商品优惠金额_销售总价_商品小计
                foreach ($sales_data as $ord_id => $order_item)
                {
                    foreach ($order_item['sales_items'] as $key => $item)
                    {
                        $item_detail_id     = $item['item_detail_id'];
                        $nums               = intval($item['nums']);//购买数量
                        $send_num           = $detail_items[$item_detail_id]['dly_nums'];//发货数量
                        
                        $item['pmt_price']      = round(($item['pmt_price'] / $nums * $send_num), 2);
                        $item['sale_price']     = round(($item['sale_price'] / $nums * $send_num), 2);
                        $item['sales_amount']   = round(($item['sales_amount'] / $nums * $send_num), 2);
                        $item['nums']           = $send_num;
                        
                        $sales_data[$ord_id]['sales_items'][$key]   = $item;
                    }
                }
                
                #获取出入库类型type_id
                $type       = null;
                if(!$type){
                    eval('$type='.get_class($iostock_instance).'::LIBRARY_SOLD;');
                }
                foreach($iostock_data as $key=>$v)
                {
                    $iostock_data[$key]['type_id']  = $type;
                }
                
                $iostock_sales_data['iostock']  = $iostock_data;
                $iostock_sales_data['sales']    = $sales_data;
                
                kernel::single('ome_iostocksales')->add_to_sales($iostock_sales_data);
            }
        }
        
        return true;
    }

    /**
     * 订单数据字段格式化过滤
     * @access public
     * @param $order_sdf 订单标准sdf结构数据
     * @return 订单标准sdf结构数据
     */
    public function modify_sdfdata($order_sdf){
        if ($order_sdf['shop_type'] == 'taobao'){
            foreach($order_sdf['order_objects'] as $key=>$object){
                if ($object['obj_type'] != 'pkg' || !$object['amount']) continue;
                $obj_amount = $object['amount'];
                $count_items = count($object['order_items']);
                if ( $obj_amount > $count_items ){
                    $average_item_price = floor($obj_amount / $count_items);
                    $remain_price = $obj_amount - $average_item_price * $count_items;
                }else{
                    $average_item_price = round($obj_amount / $count_items, 3);
                }
                $i = 1;
                foreach($object['order_items'] as $k=>$item){
                    $amount = $price = 0;
                    if ( $i == 1 ){
                        $amount = bcadd($average_item_price,$remain_price,3);
                    }else{
                        $amount = $average_item_price;
                    }
                    if ($item['quantity']){
                        $price = round($amount / $item['quantity'], 3);
                    }else{
                        $price = $amount;
                    }
                    $order_sdf['order_objects'][$key]['order_items'][$k]['price'] = $price;
                    $order_sdf['order_objects'][$key]['order_items'][$k]['amount'] = $amount;
                    $i++;
                }
            }
        }
        return $order_sdf;
    }

    /**
     * 计算捆绑、礼包的差额
     * @access public
     * @param $order_objects objects_sdf 结构
     * @return Number 差额
     */
    public function obj_difference($order_objects){
        $obj_amount = $items_amount = 0;
        $db = kernel::database();
        $obj_sendnum = 1;

        if ($order_objects['order_items']){
            $items_tmp = array();
            // 捆绑商品明细总金额
            foreach ($order_objects['order_items'] as $items){
                if (empty($items_tmp)){
                    $items_tmp = $items;
                }
                $items_amount += $items['amount'];
            }
            // 计算当前捆绑的发送数量
            $sql = " SELECT `nums` FROM `sdb_ome_order_items` WHERE `item_id`='".$items_tmp['item_id']."' ";
            $old_order_items = $db->selectrow($sql);
            if ($items_tmp['number']){
                $obj_sendnum = $order_objects['nums'] / ($old_order_items['nums'] / $items_tmp['number']);
            }
            $obj_amount = $obj_sendnum * $order_objects['price'];
        }

        $difference = $obj_amount - $items_amount;
        return $difference;
    }

    /**
      * 处理本地或shopex体系的0元订单
      *
      * @return void
      * @author
      **/
     function order_pay_confirm($shop_id,$order_id,$total_amount)
     {

        $ome_payment_confirm = app::get('ome')->getConf('ome.payment.confirm');

        $orderObj = app::get('ome')->model('orders');

        if($ome_payment_confirm == 'false'){//不需要经过财审,直接生成支付单

            $op_info = kernel::single('ome_func')->getDesktopUser();

            $pay_time = time();

            $paymentObj = &app::get('ome')->model('payments');
            $sdf = array(
                'payment_bn' => $paymentObj->gen_id(),
                'shop_id' => $shop_id,
                'order_id' => $order_id,
                'currency' => 'CNY',
                'money' => '0',
                'paycost' => '0',
                'cur_money' => '0',
                'pay_type' => 'online',
                't_begin' => $pay_time,
                'download_time' => $pay_time,
                't_end' => $pay_time,
                'status' => 'succ',
                'memo'=>'0元订单自动生成支付单',
                'op_id'=> $op_info['op_id'],
            );
            $paymentObj->create_payments($sdf);
            $pay_status = '1';
            $sql ="UPDATE `sdb_ome_orders` SET pay_status='".$pay_status."', paytime='".$pay_time."' WHERE `order_id`='".$order_id."'";
        }else{
            $pay_status = '0';
            $sql ="UPDATE `sdb_ome_orders` SET pay_status='".$pay_status."' WHERE `order_id`='".$order_id."'";
        }

        $orderObj->db->exec($sql);

        return true;

     }

}