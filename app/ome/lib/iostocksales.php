<?php
/**
 * 出入库、销售记录
 * @package ome_iostocksales
 * @copyright www.shopex.cn 2011.3.15
 * @author ome
 */
class ome_iostocksales {

    /**
     * 存储出入库、销售记录
     * @access public
     * @param String $data 出入库、销售记录
     * @param String $msg 消息
     * @return boolean 成功or失败
     */
    public function set($data,$io,&$msg,$type=null)
    {
        #拆单配置 ExBOY
        $oDelivery      = &app::get('ome')->model('delivery');
        $split_seting   = $oDelivery->get_delivery_seting();

        $allow_commit = false;
        // kernel::database()->exec('begin');
        $iostock_instance = kernel::single('ome_iostock');
        $sales_instance = kernel::single('ome_sales');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $data['iostock'];
            if(!$type){
                eval('$type='.get_class($iostock_instance).'::LIBRARY_SOLD;');
            }

            $iostock_bn = $iostock_instance->get_iostock_bn($type);

            if ( $iostock_instance->set($iostock_bn, $iostock_data, $type, $iostock_msg, $io) ){

                if ( method_exists($sales_instance, 'set') )
                {
                    if ($data['sales']['sales_items'])
                    {
                        /*------------------------------------------------------ */
                        //-- [拆单]过滤部分拆分OR部分发货时,不存储销售记录  ExBOY
                        /*------------------------------------------------------ */
                        if(!empty($split_seting))
                        {
                            $get_order_id       = intval($data['sales']['order_id']);
                            $get_delivery_id    = intval($data['sales']['delivery_id']);
                            
                            if($data['split_type'] && $get_order_id)
                            {
                                $allow_commit   = $this->check_order_all_delivery($get_order_id, $get_delivery_id);
                            }
                            
                            #[拆单]获取订单对应所有iostock出入库单
                            $order_delivery_iostock_data    = $this->get_delivery_iostock_data($iostock_data);
                            
                            #多个发货单累加物流成本
                            $delivery_cost_actual           = $this->count_delivery_cost_actual($get_order_id);
                            if($delivery_cost_actual)
                            {
                                $sales_data['delivery_cost_actual']  = $delivery_cost_actual;
                            }
                        }
                        
                        if(!$allow_commit)
                        {
                            //存储销售记录
                            $branch_id = '';
                            if ($data['sales']['sales_items']){
                                foreach ($data['sales']['sales_items'] as $k=>$v)
                                {
                                    #[拆单]多个发货单时_iostock_id为NULL重新获取 ExBOY
                                    if(!empty($iostock_data[$v['item_detail_id']]['iostock_id']))
                                    {
                                        $v['iostock_id'] = $iostock_data[$v['item_detail_id']]['iostock_id'];
                                    }
                                    else 
                                    {
                                        $v['iostock_id']   = $order_delivery_iostock_data[$v['item_detail_id']]['iostock_id'];
                                    }
                                    $data['sales']['sales_items'][$k] = $v;
                                }
                            }
                            $data['sales']['iostock_bn'] = $iostock_bn;
                            $sales_data = $data['sales'];
                            $sale_bn = $sales_instance->get_salse_bn();
                            $sales_data['sale_bn'] = $sale_bn;
                            if ( $sales_instance->set($sales_data, $sales_msg) ){
                                $allow_commit = true;
                            }
                        }
                    }
                    else
                    {
                        foreach($data['sales'] as $k=>$v)
                        {
                            /*------------------------------------------------------ */
                            //-- [拆单]过滤部分拆分OR部分发货时,不存储销售记录  ExBOY
                            /*------------------------------------------------------ */
                            if(!empty($split_seting))
                            {
                                $get_order_id       = intval($v['order_id']);
                                $get_delivery_id    = intval($v['delivery_id']);
                                if($data['split_type'] && $get_order_id)
                                {
                                    $allow_commit   = $this->check_order_all_delivery($get_order_id, $get_delivery_id);
                                    
                                    if($allow_commit)
                                    {
                                        continue;
                                    }
                                }
                                
                                #获取订单对应所有iostock出入库单
                                $order_delivery_iostock_data    = $this->get_delivery_iostock_data($iostock_data);
                                
                                #多个发货单累加物流成本
                                $delivery_cost_actual           = $this->count_delivery_cost_actual($get_order_id);
                                if($delivery_cost_actual)
                                {
                                    $data['sales'][$k]['delivery_cost_actual']  = $delivery_cost_actual;
                                }
                            }
                            
                            //存储销售记录
                            $branch_id = '';
                            if ($data['sales'][$k]['sales_items']){
                                foreach ($data['sales'][$k]['sales_items'] as $kk=>$vv)
                                {
                                    #[拆单]多个发货单时_iostock_id为NULL重新获取 ExBOY
                                    if(!empty($iostock_data[$vv['item_detail_id']]['iostock_id']))
                                    {
                                        $vv['iostock_id'] = $iostock_data[$vv['item_detail_id']]['iostock_id'];
                                    }
                                    else 
                                    {
                                        $vv['iostock_id']   = $order_delivery_iostock_data[$vv['item_detail_id']]['iostock_id'];
                                    }
                                    
                                    $data['sales'][$k]['sales_items'][$kk] = $vv;
                                }
                            }
                            $data['sales'][$k]['iostock_bn'] = $iostock_bn;
                            $sale_bn = $sales_instance->get_salse_bn();
                            $data['sales'][$k]['sale_bn'] = $sale_bn;
                            if ( $sales_instance->set($data['sales'][$k], $sales_msg) ){
                                $allow_commit = true;
                            }
                        }

                    }

                }

                //更新销售单上的成本单价和成本金额等字段
                kernel::single('tgstockcost_instance_router')->set_sales_iostock_cost($io,$iostock_data);
            }
        }

        if ($allow_commit == true){
            // kernel::database()->commit();
            return true;
        }else{
            // kernel::database()->rollBack();
            $msg['instock'] = $iostock_msg;
            $msg['sales'] = $sales_msg;
            return false;
        }
    }



    /**
     * 组织出库数据
     * @access public
     * @param String $delivery_id 发货单ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($delivery_id){
        $delivery_items_detailObj = &app::get('ome')->model('delivery_items_detail');

        //发货单信息
        $sql = 'SELECT `branch_id`,`delivery_bn`,`op_name`,`delivery_time`,`is_cod` FROM `sdb_ome_delivery` WHERE `delivery_id`=\''.$delivery_id.'\'';
        $delivery_detail = $delivery_items_detailObj->db->selectrow($sql);
        $delivery_items_detail = $delivery_items_detailObj->getList('*', array('delivery_id'=>$delivery_id), 0, -1);

        $iostock_data = array();
        if ($delivery_items_detail){
            foreach ($delivery_items_detail as $k=>$v){
                $iostock_data[$v['item_detail_id']] = array(
                    'order_id' => $v['order_id'],
                    'branch_id' => $delivery_detail['branch_id'],
                    'original_bn' => $delivery_detail['delivery_bn'],
                    'original_id' => $delivery_id,
                    'original_item_id' => $v['item_detail_id'],
                    'supplier_id' => '',
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['number'],
                    'cost_tax' => '',
                    'oper' => $delivery_detail['op_name'],
                    'create_time' => $delivery_detail['delivery_time'],
                    'operator' => $delivery_detail['op_name'],
                    'settle_method' => '',
                    'settle_status' => '0',
                    'settle_operator' => '',
                    'settle_time' => '',
                    'settle_num' => '',
                    'settlement_bn' => '',
                    'settlement_money' => '0',
                    'memo' => '',
                );
            }
        }
        unset($delivery_detail,$delivery_items_detail);
        return $iostock_data;
    }

///////////////////////////////////////////////////////////

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
    
    /**
     +----------------------------------------------------------
     * [拆单]判断订单是否已全部发货  ExBOY
     +----------------------------------------------------------
     * @param   String      $order_id       订单号ID
     * @param   String      $delivery_id    发货单ID
     * @return  boolean
     +----------------------------------------------------------
     */
    public function check_order_all_delivery($order_id, $delivery_id)
    {
        #订单"部分拆分"不生成销售单
        $sql    = "SELECT process_status FROM sdb_ome_orders WHERE order_id='".$order_id."'";
        $row    = kernel::database()->selectrow($sql);
        if($row['process_status'] == 'splitting')
        {
            return true;
        }
        
        #判断——订单所属发货单是否全部发货 process!='true'
        $sql    = "SELECT dord.delivery_id, d.delivery_bn, d.process, d.status FROM sdb_ome_delivery_order AS dord 
                        LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id) 
                        WHERE dord.order_id='".$order_id."' AND d.delivery_id!='".$delivery_id."' AND d.process!='true' 
                        AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' AND d.status NOT IN('failed','cancel','back','return_back')";
        $row    = kernel::database()->selectrow($sql);
        if(!empty($row))
        {
           return true;
        }
        
        return false;
    }
    
    /**
     +----------------------------------------------------------
     * [拆单]余单撤消后_生成销售单  ExBOY
     +----------------------------------------------------------
     * @param   Array     $data     订单号ID
     * @param   Intval    $io       默认0出库
     * @return  boolean
     +----------------------------------------------------------
     */
    public function add_to_sales($data, $io=0, $type=null)
    {
        $allow_commit       = false;
        $iostock_instance   = kernel::service('ome.iostock');
        $sales_instance     = kernel::service('ome.sales');
        
        if (method_exists($iostock_instance, 'set') == false)
        {
            return false;
        }
        
        //存储出入库记录
        $iostock_data   = $data['iostock'];
        if(!$type)
        {
             eval('$type='.get_class($iostock_instance).'::LIBRARY_SOLD;');
        }
        
        $iostock_bn     = $iostock_instance->get_iostock_bn($type);
        
        if ( method_exists($sales_instance, 'set') )
        {
            if ($data['sales']['sales_items'])
            {
                $get_order_id       = intval($data['sales']['order_id']);
                $get_delivery_id    = intval($data['sales']['delivery_id']);
                
                #[拆单]获取订单对应所有iostock出入库单
                $order_delivery_iostock_data    = $this->get_delivery_iostock_data($iostock_data);
                
                #多个发货单累加物流成本
                $delivery_cost_actual           = $this->count_delivery_cost_actual($get_order_id);
                if($delivery_cost_actual)
                {
                    $sales_data['delivery_cost_actual']  = $delivery_cost_actual;
                }
                
                //存储销售记录
                $branch_id = '';
                if ($data['sales']['sales_items']){
                    foreach ($data['sales']['sales_items'] as $k=>$v)
                    {
                        #[拆单]多个发货单时_iostock_id为NULL重新获取 ExBOY
                        if(!empty($iostock_data[$v['item_detail_id']]['iostock_id']))
                        {
                            $v['iostock_id'] = $iostock_data[$v['item_detail_id']]['iostock_id'];
                        }
                        else 
                        {
                            $v['iostock_id']   = $order_delivery_iostock_data[$v['item_detail_id']]['iostock_id'];
                        }
                        
                        $data['sales']['sales_items'][$k] = $v;
                    }
                }
                $data['sales']['iostock_bn'] = $iostock_bn;
                $sales_data = $data['sales'];
                $sale_bn = $sales_instance->get_salse_bn();
                $sales_data['sale_bn'] = $sale_bn;
                if ( $sales_instance->set($sales_data, $sales_msg) ){
                    $allow_commit = true;
                }
            }
            else
            {
                foreach($data['sales'] as $k=>$v)
                {
                    $get_order_id       = intval($v['order_id']);
                    $get_delivery_id    = intval($v['delivery_id']);
                    
                    #获取订单对应所有iostock出入库单
                    $order_delivery_iostock_data    = $this->get_delivery_iostock_data($iostock_data);
                    
                    #多个发货单累加物流成本
                    $delivery_cost_actual           = $this->count_delivery_cost_actual($get_order_id);
                    if($delivery_cost_actual)
                    {
                        $data['sales'][$k]['delivery_cost_actual']  = $delivery_cost_actual;
                    }
                    
                    //存储销售记录
                    $branch_id = '';
                    if ($data['sales'][$k]['sales_items']){
                        foreach ($data['sales'][$k]['sales_items'] as $kk=>$vv)
                        {
                            #[拆单]多个发货单时_iostock_id为NULL重新获取 ExBOY
                            if(!empty($iostock_data[$vv['item_detail_id']]['iostock_id']))
                            {
                                $vv['iostock_id']   = $iostock_data[$vv['item_detail_id']]['iostock_id'];
                            }
                            else 
                            {
                                $vv['iostock_id']   = $order_delivery_iostock_data[$vv['item_detail_id']]['iostock_id'];
                            }
                            
                            $data['sales'][$k]['sales_items'][$kk] = $vv;
                        }
                    }
                    $data['sales'][$k]['iostock_bn'] = $iostock_bn;
                    $sale_bn = $sales_instance->get_salse_bn();
                    $data['sales'][$k]['sale_bn'] = $sale_bn;
                    
                    if ( $sales_instance->set($data['sales'][$k], $sales_msg) ){
                        $allow_commit = true;
                    }
                }
            }
            
            //更新销售单上的成本单价和成本金额等字段
            kernel::single('tgstockcost_instance_router')->set_sales_iostock_cost($io,$iostock_data);
        }
        
        return $allow_commit;
    }
    
    /**
     +----------------------------------------------------------
     * [拆单]获取订单对应所有iostock出入库单  ExBOY
     +----------------------------------------------------------
     * @param   Array     $iostock_data     出入库单
     * @return  Array     $result
     +----------------------------------------------------------
     */
    public function get_delivery_iostock_data($iostock_data)
    {
        $order_ids  = $delivery_ids = array();
        foreach ($iostock_data as $key => $val)
        {
            $order_ids[$val['order_id']]    = $val['order_id'];
        }
        $in_order_id    = implode(',', $order_ids);
        
        #获取订单对应所有发货单delivery_id
        $sql            = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id in(".$in_order_id.") AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                                            AND d.status NOT IN('failed','cancel','back','return_back')";
        $temp_data      = kernel::database()->select($sql);
        foreach ($temp_data as $key => $val)
        {
            $delivery_ids[]     = $val['delivery_id'];
        }
        
        #读取出库记录
        $result     = array();
        $ioObj      = &app::get('ome')->model('iostock');
        $field      = 'iostock_id, iostock_bn, type_id, branch_id, original_bn, original_id, original_item_id, bn';
        $temp_data  = $ioObj->getList($field, array('original_id'=>$delivery_ids));
        
        foreach ($temp_data as $key => $val)
        {
            $result[$val['original_item_id']]   = $val;
        }
        
        return $result;
    }
    
    /**
     +----------------------------------------------------------
     * [拆单]多个发货单累加物流成本  ExBOY
     +----------------------------------------------------------
     * @param   Array     $iostock_data     出入库单
     * @return  Array     $result
     +----------------------------------------------------------
     */
    public function count_delivery_cost_actual($order_id)
    {
        $oDelivery      = &app::get('ome')->model('delivery');
        $delivery_ids   = $temp_data = array();
        
        #获取订单对应所有发货单delivery_id
        $sql            = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id='".$order_id."' AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                                            AND d.status NOT IN('failed','cancel','back','return_back')";
        $temp_data      = kernel::database()->select($sql);
        
        #[无拆单]订单只有一个发货单,直接返回false
        if(count($temp_data) < 2)
        {
            return false;
        }
        
        foreach ($temp_data as $key => $val)
        {
            $delivery_ids[]     = $val['delivery_id'];
        }
        
        #累加物流成本
        $dly_data               = $oDelivery->getList('delivery_id, delivery_cost_actual, parent_id, is_bind', array('delivery_id'=>$delivery_ids));
        $delivery_cost_actual   = 0;
        foreach ($dly_data as $key => $val)
        {
            #[合并发货单]重新计算物流运费
            if($val['is_bind'] == 'true')
            {
                $val['delivery_cost_actual']    = $this->compute_delivery_cost_actual($order_id, $val['delivery_id'], $val['delivery_cost_actual']);
            }
            $delivery_cost_actual += floatval($val['delivery_cost_actual']);
        }
        
        return $delivery_cost_actual;
    }
    
    /**
     +----------------------------------------------------------
     * [拆单]合并发货单_平摊预估物流运费  ExBOY
     +----------------------------------------------------------
     * @param   Array     $iostock_data     出入库单
     * @return  Array     $result
     +----------------------------------------------------------
     */
    public function compute_delivery_cost_actual($order_id, $delivery_id, $delivery_cost_actual)
    {
        $oOrders    = &app::get('ome')->model('orders');
        $oDelivery  = &app::get('ome')->model('delivery');
        
        $orderIds   = $oDelivery->getOrderIdsByDeliveryIds(array($delivery_id));
        
        $sales_data = $temp_data  = array();
        $temp_data  = $oOrders->getList('order_id, payed', array('order_id'=>$orderIds));
        foreach ($temp_data as $key => $val)
        {
            $val['delivery_cost_actual']    = $delivery_cost_actual;
            $sales_data[$val['order_id']]   = $val;
        }
        
        //平摊预估物流运费，主要处理订单合并发货以及多包裹单的运费问题
        $ome_sales_logistics_feeLib = kernel::single('ome_sales_logistics_fee');
        $ome_sales_logistics_feeLib->calculate($orderIds,$sales_data);
        
        return $sales_data[$order_id]['delivery_cost_actual'];//返回所查订单的平摊物流费用
    }
}