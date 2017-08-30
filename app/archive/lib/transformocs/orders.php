<?php
class archive_transformocs_orders  extends archive_transformocs_abstract
{
    static public $products = array();
    
 
    function do_exec()
    {
        $wheresql = array();
        $sqlstr = '';
        if ($filter['shop_id']) {
            $wheresql[]= 'shop_id in(\''.implode('\',\'',$filter['shop_id']).'\')';
        }
        
        if ($wheresql) {
            $sqlstr.=" WHERE ".implode(' AND ',$wheresql);
        }
        $SQL = "SELECT count(order_id) as _count FROM sdb_ome_orders ".$sqlstr;

        //取订单
        $query = mysql_query($SQL,$this->conn) or die('no query'.$SQL);
        $orderRow = mysql_fetch_assoc($query);
        
        $total =$orderRow['_count'];
        
        $pagelimit = 100;
        $page = ceil($total/$pagelimit);
        $fail = 0;$succ=0;
        for($i=1;$i<=$page;$i++){
           
            $offset = $pagelimit*($i-1);
            $offset = max($offset,0);
            $keys='order_id,order_bn,relate_order_bn,member_id,confirm,process_status,`status`,pay_status,ship_status,is_delivery,shipping,pay_bn,payment,weight,tostr,itemnum,createtime,download_time,last_modified,shop_id,shop_type,ip,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_email,ship_time,ship_mobile,consigner_name,consigner_area,consigner_addr,consigner_zip,consigner_email,consigner_mobile,consigner_tel,cost_item,is_tax,cost_tax,tax_company,cost_freight,is_protect,cost_protect,is_cod,is_fail,edit_status,cost_payment,currency,cur_rate,score_u,score_g,discount,pmt_goods,pmt_order,total_amount,final_amount,payed,custom_mark,mark_text,disabled,mark_type,tax_no,dt_begin,is_anti,group_id,op_id,dispatch_time,order_limit_time,coupons_name,refer_id,refer_url,refer_time,c_refer_id,c_refer_url,c_refer_time,abnormal,print_finish,source,pause,is_modify,old_amount,order_type,order_job_no,is_auto';
            $querysql = "SELECT ".$keys." FROM sdb_ome_orders".$sqlstr;
            $queryrow = mysql_query($querysql."  ORDER BY order_id ASC  LIMIT $offset,$pagelimit",$this->conn);
          
            $order_list = array();
            $member_list = array();
            while ($orderrow =mysql_fetch_assoc($queryrow) ) {
                $order_id = $orderrow['order_id'];
                $order_list[$order_id] = $orderrow;
                $member_list[] = $orderrow['member_id'];
            }
           
            $ordersdfList = $this->_transOrders($order_list);
            foreach ( $ordersdfList as $ordersdf ) {
                //插入会员
                $order_bn = $ordersdf['order_bn'];
                $this->db->exec('begin');
                $order_result = $this->create_order($ordersdf);
               
                if ($order_result) {
                    //生成发货单
                    
                    
                    $order_id = $ordersdf['order_id'];
                    $delivery_list = $this->_tranDeliverys($order_bn);
                    if ($delivery_list) {

                        foreach ($delivery_list as $delivery ) {
                            $delivery_id = $this->addDelivery($order_id,$delivery);
                            if (!$delivery_id) {
                                $fail++;
                                $this->db->rollBack();
                            }
                        }
                        
                       
                    }
                    $this->db->commit();
                    //成功订单
                    error_log($order_bn.",",3,DATA_DIR.'.succ.log');
                    $succ++;
                }else{
                    //失败订单
                    $fail++;
                   error_log($order_bn.",",3,DATA_DIR.'.fail.log');
                }
            }
            
            
            
        }
        echo '成功'.$succ.'<br>';
        echo '失败'.$fail.'<br>';
    }

    
    /**
     * 转换订单.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function _transOrders($order_list)
    {
        $order_ids = array_keys($order_list);
        //会员

        $order_sdfList = array();
        $orderObjectList = $this->_getorderObject($order_ids);
        
        foreach ($order_list as $orders ) {
            //转换收件人等信息
            $orders['consignee']['name']   = $orders['ship_name'];
            $orders['consignee']['area']     = $orders['ship_area'];
            $orders['consignee']['addr']     = $orders['ship_addr'];
            $orders['consignee']['zip']       = $orders['ship_zip'];
            $orders['consignee']['telephone'] = $orders['ship_tel'];
            $orders['consignee']['email'] = $orders['ship_email'];
            $orders['consignee']['r_time'] = $orders['ship_time'];
            $orders['consignee']['mobile'] = $orders['ship_mobile'];
            $orders['shipping']['shipping_name'] = $orders['shipping'];
            $orders['payinfo']['pay_name'] = $orders['payment'];
            $orders['consigner'] = $this->getConsigner($orders);
            
            $orders['shipping']['is_cod'] = $orders['is_cod'];
            $orders['payinfo']['cost_payment'] = $orders['cost_payment'];
            $orders['shipping']['cost_shipping'] = $orders['cost_freight'];
            $orders['shipping']['is_protect'] = $orders['is_protect'];
            $orders['shipping']['cost_protect'] = $orders['cost_protect'];
            $order_id = $orders['order_id'];
            unset($orders['order_id'],$orders['ship_name'],$orders['ship_area'],$orders['ship_addr'],$orders['ship_zip'],$orders['ship_tel'],$orders['ship_email'],$orders['ship_time'],$orders['ship_mobile'],$orders['shipping'],$orders['payment'],$orders['consigner_name'],$orders['consigner_area'],$orders['consigner_addr'],$orders['consigner_zip'],$orders['consigner_email'],$orders['consigner_mobile'],$orders['consigner_tel'],$orders['is_cod'],$orders['cost_payment'],$orders['cost_freight'],$orders['is_protect'],$orders['cost_protect']);
            $order_sdf = $orders;
            $order_objects = $orderObjectList[$order_id];
            //格式化货号
            foreach ($order_objects  as &$objects ) {
                
                foreach ($objects['order_items'] as &$items ) {
                    
                    $products = $this->transGoods($items['bn']);
                    $items['product_id'] = $products['product_id'];

                }
                $objects['goods_id'] = $products['goods_id'];
            }

            $order_sdf['order_objects'] = $order_objects;
            
            $order_sdfList[] = $order_sdf;
        }
        return $order_sdfList;
    }

    
    
    public function _getorderObject($order_ids)
    {
        $order_ids = implode(',',$order_ids);
        
        $object_keys = 'obj_id,order_id,oid,obj_type,obj_alias,shop_goods_id,goods_id,bn,`name`,price,amount,quantity,pmt_price,sale_price,weight,score';
        $sql = "SELECT ".$object_keys." FROM sdb_ome_order_objects WHERE order_id in (".$order_ids.")";
        $objectquery = mysql_query($sql,$this->conn);
        
        $item_keys = 'order_id,obj_id,shop_goods_id,product_id,shop_product_id,bn,`name`,cost,price,amount,pmt_price,sale_price,nums,sendnum,item_type,score,sell_code,`delete`';
        $sql1 = "SELECT ".$item_keys." FROM sdb_ome_order_items WHERE order_id in(".$order_ids.") ORDER BY item_id ASC";
        $goodsObj = app::get('ome')->model('goods');
        $itemquery = mysql_query($sql1,$this->conn);
        $order_object = $order_items = array();
        while ( $itemrow = mysql_fetch_assoc($itemquery) ) {
            $order_id = $itemrow['order_id'];
            $obj_id = $itemrow['obj_id'];
            unset($itemrow['order_id'],$itemrow['obj_id']);
           
            $order_items[$order_id][$obj_id][] = $itemrow;
        }
        while ($objectrow = mysql_fetch_assoc($objectquery) ) {
            $order_id = $objectrow['order_id'];
            $obj_id = $objectrow['obj_id'];
            $objectrow['order_items'] = $order_items[$order_id][$obj_id];
            unset($objectrow['order_id'],$objectrow['obj_id']);
            $order_object[$order_id][] = $objectrow;
        }
        return $order_object;
    }
    
    
    
    
    /**
     * 根据订单号获取相应发货单信息
     * @param  
     * @return  array
     * @access  public
     * @author sunjing@shopex.cn
     */
    function _tranDeliverys($order_bn)
    {
        
        $delivery_list = array();
        $deliveryquery = mysql_query("SELECT d.* FROM sdb_ome_delivery_order  as od LEFT JOIN sdb_ome_delivery as d on od.delivery_id=d.delivery_id LEFT JOIN sdb_ome_orders as o on o.order_id=od.order_id WHERE o.order_bn='".$order_bn."'",$this->conn);
       
        while($delivery_detail = mysql_fetch_assoc($deliveryquery) ){

            if ($delivery_detail) {
                //delivery_items
                $delivery_id = $delivery_detail['delivery_id'];
                $itemsquery = mysql_query("SELECT product_id,shop_product_id,bn,product_name,number,verify,verify_num FROM sdb_ome_delivery_items WHERE delivery_id=".$delivery_id,$this->conn);
                $items = array();
                while ($item_list = mysql_fetch_assoc($itemsquery) ) {
                    //取product_id
                    $item_list['product_id'] = $this->getProduct($item_list['bn']);
                    $items[] = $item_list;
                }

                $delivery_detail['delivery_items'] = $items;
                //取发货单对应明细
                $detail_query = mysql_query("SELECT item_type,product_id,bn,number,price,amount FROM sdb_ome_delivery_items_detail WHERE delivery_id=".$delivery_id,$this->conn);
                $delivery_items_detail = array();
                while ($items_detail = mysql_fetch_assoc($detail_query) ) {
                    $items_detail['product_id'] = $this->getProduct($items_detail['bn']);
                    $delivery_items_detail[] = $items_detail;
                }
                $delivery_detail['delivery_items_detail'] = $delivery_items_detail;
                $delivery_list[] = $delivery_detail;
            }
            
        }
          
        return $delivery_list;

    }

    function create_order(&$sdf){
        $orderObj = app::get('ome')->model('orders');
        //判断订单号是否重复
        if($orderObj->dump(array('order_bn'=>$sdf['order_bn'],'shop_id'=>$sdf['shop_id']))){
            return false;
        }
       //保存会员
       
        $member_id = $this->transMember($sdf['member_id']);
        unset($sdf['member_id']);
        $sdf['member_id'] =$member_id;
       
        $regionLib = kernel::single('eccommon_regions');
        //收货人/发货人地区转换
        $area = $sdf['consignee']['area'];
        $regionLib->region_validate($area);
        $sdf['consignee']['area'] = $area;
        $consigner_area = $sdf['consigner']['area'];
        $regionLib->region_validate($consigner_area);
        $sdf['consigner']['area'] = $consigner_area;
        
        if(!$orderObj->save($sdf)) return false;


        //增加订单创建日志
        $logObj = &app::get('ome')->model('operation_log');
        $logObj->write_log('order_create@ome',$sdf['order_id'],'从OCS迁移订单创建成功');
        return true;
    }
    
    function addDelivery($order_id,$delivery){

        $deliveryObj = app::get('ome')->model('delivery');
        $delivery_items_detail = $delivery['delivery_items_detail'];
       // unset($delivery['delivery_items_detail']);
        $ship_info = $delivery['consignee'] ;
        $delivery_bn = $delivery['delivery_bn'];
        $data['delivery_bn'] = $delivery_bn;
        $data['parent_id'] = $delivery['parent_id'] ;
        $data['is_cod'] = $delivery['is_cod'] ;
        $data['delivery'] = $delivery['delivery'];
        $data['logi_id'] = $delivery['logi_id'];
        $data['memo'] = $delivery['memo'];
        $data['delivery_group'] = $delivery['delivery_group'];
        $data['sms_group'] = $delivery['sms_group'];
        $data['branch_id'] = $delivery['branch_id'];
        $data['type'] = $delivery['type'];
        $deliverys = $this->db->selectrow("SELECT count(delivery_id) as _count FROM sdb_ome_delivery WHERE logi_no='".$delivery['logi_no']."'");
        
        if ($deliverys['_count']>=1) {
            $data['logi_no']             = $delivery['logi_no'].'_'.$deliverys['_count'];
            
        }else{
            $data['logi_no']             = $delivery['logi_no'] ? $delivery['logi_no'] : null;
        }

        $data['logi_name']             = $delivery['logi_name'];
        $data['is_protect']            = $delivery['is_protect'] ;
        $data['create_time']           = $delivery['create_time'] ;
        $data['cost_protect']          = $delivery['cost_protect'];
        $data['net_weight']            = $delivery['weight'];
        $data['delivery_cost_expect']  = $delivery['delivery_cost_expect'];
        $data['member_id']             = $this->transMember($delivery['member_id']);
        $data['shop_id']               = $delivery['shop_id'];
        $data['delivery_items'] = $delivery['delivery_items'];
        $data['consignee'] = $ship_info;
        $data['order_createtime'] = $delivery['order_createtime'];#付款时间为空时取创建时间
        $data['op_id']   = $delivery['op_id'];
        $data['op_name'] = $delivery['op_name'];
        $bns = array();
        $totalNum = 0;
        $delivery_items = $data['delivery_items'];
        foreach($delivery_items as $v){
            $totalNum += $v['number'];
            $bns[$v['product_id']] = $v['bn'];
        }
        ksort($bns);
        //11.25新增
        $data['skuNum']     = count($delivery_items);
        $data['itemNum']    = $totalNum;
        $data['bnsContent'] = serialize($bns);
        $data['idx_split']  = $data['skuNum'] * 10000000000 + sprintf("%u", crc32($data['bnsContent']));
        $data['bind_key'] = $delivery['bind_key'];
       
        $data['consignee'] = $this->getConsignee($delivery);
        $data['status'] = $delivery['status'];
        $data['process'] = $delivery['process'];

        $data['stock_status'] = $delivery['stock_status'];
        $data['deliv_status'] = $delivery['deliv_status'];
        $data['expre_status'] = $delivery['expre_status'];
        $data['delivery_time'] = $delivery['delivery_time'];
      
        $result = $deliveryObj->save($data);
        if (!$result || !$data['delivery_id']) {
            
            return false;
        }

        if ($data['delivery_id'] && $delivery_items_detail){
            //$this->create_delivery_items_detail($data['delivery_id'], $order_id,$delivery_items_detail);
        }

        //插关联表
        if($order_id){
            $rs  = $this->db->exec('SELECT * FROM sdb_ome_delivery_order WHERE 0=1');
            $ins = array('order_id'=>$order_id,'delivery_id'=>$data['delivery_id']);
            $sql = kernel::single("base_db_tools")->getinsertsql($rs,$ins);
            $this->db->exec($sql);
        }

        return $data['delivery_id'];
    }

    function create_delivery_items_detail($delivery_id,$order_id,$delivery_items_detail){
        $didObj = app::get('ome')->model('delivery_items_detail');
        $diObj = app::get('ome')->model('delivery_items');
       
        foreach ($delivery_items_detail as $item){

            $oi = $di = $di_item = $did = array();
            $oi = $this->db->selectrow('SELECT item_id,obj_id FROM sdb_ome_order_items WHERE product_id='.$item['product_id'].' AND order_id='.$order_id);
            $di = $diObj->dump(array('delivery_id'=>$delivery_id,'product_id'=>$item['product_id'],'number'=>$item['number']));
            $item_id = $di['item_id'];
            //查询是否已有

            $di_item = $this->db->selectrow('SELECT delivery_item_id FROM sdb_ome_delivery_items_detail WHERE delivery_id='.$delivery_id.' AND product_id='.$item['product_id'].' AND number='.$item['number']);
            if($di_item){
                $di_item1 = $this->db->selectrow('SELECT item_id FROM sdb_ome_delivery_items WHERE delivery_id='.$delivery_id.' AND product_id='.$item['product_id'].' AND number='.$item['number'].' AND item_id!='.$di_item['delivery_item_id']);
                $item_id = $di_item1['item_id'];
            }
            
            $did = array(
                'delivery_id'       => $delivery_id,
                'delivery_item_id'  => $item_id ,
                'order_id'          => $order_id,
                'order_item_id'     => $oi['item_id'],
                'order_obj_id'      => $oi['obj_id'],
                'item_type'         => $item['item_type'],
                'product_id'        => $item['product_id'],
                'bn'                => $item['bn'],
                'number'            => $item['number'],
                'price'             => $item['price'],
                'amount'            => $item['amount'],
            );

            $didObj->save($did);
        }
    }

    
    
    /**
     * 获取发货地址信息
     *
     * @param Array $order 订单数据
     * @return Array
     */
    private function getConsignee($delivery) {
        return array(
            'name' => $delivery['ship_name'],
            'area' => $delivery['ship_area'],
            'province' => $delivery['ship_province'],
            'city' => $delivery['ship_city'],
            'district' => $delivery['ship_district'],
            'addr'=> $delivery['ship_addr'],
            'zip'=>$delivery['ship_zip'],
            'telephone'=>$delivery['ship_tel'],
            'mobile'=>$delivery['ship_mobile'],
            'email'=>$delivery['ship_email'],
        );
    }

    private function getConsigner($orders) {
 
        return array(
            'addr' => $orders['consigner_addr'],
            'area' => $orders['consigner_area'],
            'zip' => $orders['consigner_zip'],
            'email' => $delivery['consigner_email'],
            'mobile' => $delivery['consigner_mobile'],
            'tel'=> $delivery['consigner_tel'],
        );
    }
} 


?>