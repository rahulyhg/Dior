<?php
class archive_order{
    public function __construct(){
        
        $this->db = kernel::database();
        
    }
    
   
    function archivetimeFilter($archive_time)
    {
        $create_time = '';
        switch($archive_time){
            case '1':
                $create_time =  strtotime("-1 month");
            break;
            case '2':
                $create_time =  strtotime("-2 month");
            break;
             case '3':
                $create_time =  strtotime("-3 month");
            break;
            case '6':
                $create_time =  strtotime("-6 month");
            break;
            case '9':
                $create_time =  strtotime("-9 month");
            break;
            default:
                $create_time =  strtotime("-12 month");
                break;
        }
        return $create_time;
    }
    /**
     * 创建订单信息
     * @param   
     * @return  
     * @access  public
     * @author sunjng@shopex.cn
     */
    function _create_order($order_list)
    {
   
        $orderIds = array();
       
        foreach ($order_list as $order ) {
            $orderIds[] = $order['order_id'];
        }
        
        $orderIdstr = implode(',',$orderIds);
        $order_sql="INSERT INTO sdb_archive_orders(order_id,order_bn,member_id,status,pay_status,ship_status,pay_bn,payment,itemnum,createtime,download_time,last_modified,shop_id,shop_type,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_email,ship_time,ship_mobile,consigner_name,consigner_area,consigner_addr,consigner_zip,consigner_email,consigner_mobile,consigner_tel,cost_item,is_tax,cost_tax,tax_company,cost_freight,is_protect,cost_protect,is_cod,is_fail,discount,pmt_goods,pmt_order,total_amount,final_amount,payed,custom_mark,mark_text,tax_no,coupons_name,source,order_type,order_job_no,order_combine_idx,order_combine_hash,paytime,modifytime,order_source,relate_order_bn,createway)SELECT order_id,order_bn,member_id,status,pay_status,ship_status,pay_bn,payment,itemnum,createtime,download_time,last_modified,shop_id,shop_type,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_email,ship_time,ship_mobile,consigner_name,consigner_area,consigner_addr,consigner_zip,consigner_email,consigner_mobile,consigner_tel,cost_item,is_tax,cost_tax,tax_company,cost_freight,is_protect,cost_protect,is_cod,is_fail,discount,pmt_goods,pmt_order,total_amount,final_amount,payed,custom_mark,mark_text,tax_no,coupons_name,source,order_type,order_job_no,order_combine_idx,order_combine_hash,paytime,modifytime,order_source,relate_order_bn,createway FROM sdb_ome_orders WHERE order_id in(".$orderIdstr.") AND order_id NOT IN(SELECT order_id FROM sdb_archive_orders)";
       
        $order_result = $this->db->exec($order_sql);
        $order_objsql = "INSERT INTO sdb_archive_order_objects(obj_id,order_id,obj_type,goods_id,bn,name,price,amount,quantity,pmt_price,sale_price,oid)SELECT obj_id,order_id,obj_type,goods_id,bn,name,price,amount,quantity,pmt_price,sale_price,oid FROM sdb_ome_order_objects WHERE order_id in(".$orderIdstr.") AND obj_id NOT IN(SELECT obj_id FROM sdb_archive_order_objects)";
       
        $this->db->exec($order_objsql);
        $order_itemsql = "INSERT INTO sdb_archive_order_items(item_id,order_id,obj_id,product_id,bn,name,cost,price,pmt_price,sale_price,amount,nums,sendnum,item_type) SELECT I.item_id,I.order_id,I.obj_id,I.product_id,I.bn,I.name,I.cost,I.price,I.pmt_price,I.sale_price,I.amount,I.nums,I.sendnum,I.item_type FROM sdb_ome_order_items AS I WHERE I.order_id in (".$orderIdstr.") AND I.delete='false' AND I.item_id NOT IN (SELECT item_id FROM sdb_archive_order_items)";
      
        if ($order_result) {
            $item_result = $this->db->exec($order_itemsql);
            
        }
    }

    
    /**
     * 发货单组建信息
     * @param  
     * @return  
     * @access  public
     * @author sunjng@shopex.cn
     */
    function _create_delivery($order_list)
    {
        
        $delivery_flag = true;
        $dlyObj = &app::get('ome')->model('delivery');
        $ardlyObj = app::get('archive')->model('delivery');
        $orderIds = array();
       
        foreach ($order_list as $order ) {
            $orderIds[] = $order['order_id'];
        }
        
        $orderIdstr = implode(',',$orderIds);

        //删除已经取消和退回的发货单据
        $cancel_sql = "SELECT O.delivery_id,D.delivery_bn FROM sdb_ome_delivery_order  as O LEFT JOIN sdb_ome_delivery as D on O.delivery_id=D.delivery_id WHERE O.order_id in(".$orderIdstr.") AND  D.status in ('cancel','back','return_back') group by D.delivery_id";
        
        $delivery_cancel = $this->db->select($cancel_sql);
        $deliverycancelIds = array();
        if ($delivery_cancel) {
            foreach ($delivery_cancel as $dc ) {
                $deliverycancelIds[] = $dc['delivery_id'];
            }
            if ($deliverycancelIds) {
                $deliverycancelstr = implode(',',$deliverycancelIds);
                $this->db->exec("DELETE FROM sdb_ome_delivery WHERE delivery_id in(".$deliverycancelstr.")");
                $this->db->exec("DELETE FROM sdb_ome_delivery_order WHERE delivery_id in(".$deliverycancelstr.")");
                $this->db->exec("DELETE FROM sdb_ome_delivery_items WHERE delivery_id in(".$deliverycancelstr.")");
                $this->db->exec("DELETE FROM sdb_ome_delivery_items_detail WHERE delivery_id in(".$deliverycancelstr.")");
            }
            
        }
        //归纳对应成功发货单
        $delivery_sql = "SELECT D.delivery_id,D.delivery_bn FROM sdb_ome_delivery_order  as O LEFT JOIN sdb_ome_delivery as D on O.delivery_id=D.delivery_id WHERE O.order_id in(".$orderIdstr.")  AND  D.status in ('succ') AND process='true' AND D.parent_id=0 AND D.delivery_id not in(SELECT delivery_id FROM sdb_archive_delivery)";
        $delivery_list = $this->db->select($delivery_sql);
        $deliveryIds = array();
        if ($delivery_list) {
            $outdeliverybn = array();
            foreach ( $delivery_list as $delivery ) {
                $deliveryIds[$delivery['delivery_id']] = $delivery['delivery_id'];
                $outdeliverybn[$delivery['delivery_id']] = $delivery['delivery_bn'];
            }
        }
         if ($deliveryIds) {
            $deliveryIdstr = implode(',',$deliveryIds);
             $copy_delivery_sql = "INSERT INTO sdb_archive_delivery(delivery_id,idx_split,skuNum,itemNum,delivery_bn,bnsContent,member_id,is_protect,cost_protect,is_cod,delivery,logi_id,logi_name,logi_no,logi_number,delivery_logi_number,ship_name,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,create_time,status,memo,branch_id,last_modified,delivery_time,ship_time,op_id,op_name) SELECT delivery_id,idx_split,skuNum,itemNum,delivery_bn,bnsContent,member_id,is_protect,cost_protect,is_cod,delivery,logi_id,logi_name,logi_no,logi_number,delivery_logi_number,ship_name,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,create_time,status,memo,branch_id,last_modified,delivery_time,ship_time,op_id,op_name FROM sdb_ome_delivery WHERE delivery_id in(".$deliveryIdstr.") AND delivery_id NOT IN(SELECT delivery_id FROM sdb_archive_delivery)";
           
            $this->db->exec($copy_delivery_sql);
            $copy_items_sql = "INSERT INTO sdb_archive_delivery_items(item_id,delivery_id,product_id,bn,product_name,number) SELECT item_id,delivery_id,product_id,bn,product_name,number FROM sdb_ome_delivery_items WHERE delivery_id in(".$deliveryIdstr.") AND item_id NOT IN(SELECT item_id FROM sdb_archive_delivery_items)";
           
            $this->db->exec($copy_items_sql);
            $deliveryorder_sql = "INSERT INTO sdb_archive_delivery_order(order_id,delivery_id) SELECT order_id,delivery_id FROM sdb_ome_delivery_order WHERE delivery_id in(".$deliveryIdstr.")";
           
            $this->db->exec($deliveryorder_sql);
            $logi_sql = "INSERT INTO sdb_archive_delivery_bill(logi_id,delivery_id,logi_no)SELECT log_id,delivery_id,logi_no FROM sdb_ome_delivery_bill WHERE delivery_id in(".$deliveryIdstr.") AND status='1'";
          
            $this->db->exec($logi_sql);

            //看是否有自有仓数据
            
            $wmsdeliveryIds = $this->getdeliveryidByOutbn($outdeliverybn);
            if (count($wmsdeliveryIds)>0) {
                $this->copyWmsdelivery($wmsdeliveryIds);
                $this->deleteWmsdelivery($wmsdeliveryIds);
            }
            //删除发货单
           $this-> _delete_delivery($deliveryIdstr);
         }
        
      
        return true;
    }



    /**
     * 冻结库存
     * @param  
     * @return  
     * @access  public
     * @author sunjng@shopex.cn
     */

     function _storefreeze_order($order_list)
     {

        $orderIds = array();

        foreach ($order_list as $order ) {
            $orderIds[] = $order['order_id'];
        }
        $oProduct = app::get('ome')->model("products");
        $orderIdstr = implode(',',$orderIds);
        //释放冻结

        $items = $this->db->select("SELECT product_id,nums FROM sdb_ome_order_items WHERE order_id in (".$orderIdstr.")");
        foreach($items as $v){
            $num = $v['nums'];
            $oProduct->chg_product_store_freeze($v['product_id'],$num,"-");
        }
        echo "冻结库存释放完成\n";
     }

     
     /**
      * 删除订单相关信息
      * @param  
      * @return 
      * @access  public
      * @author sunjing@shopex.cn
      */
     function _delete_order($order_list)
     {
        $orderIds = array();
       
        foreach ($order_list as $order ) {
            $orderIds[] = $order['order_id'];
        }
        if ($orderIds) {
            $orderIdstr = implode(',',$orderIds);
            $ordersql = "DELETE FROM sdb_ome_orders WHERE order_id in(".$orderIdstr.")";
           
            $this->db->exec($ordersql);
            $orderobjectsql = "DELETE FROM sdb_ome_order_objects WHERE order_id in(".$orderIdstr.")";
            
            $this->db->exec($orderobjectsql);
            $orderitemsql = "DELETE FROM sdb_ome_order_items WHERE order_id in(".$orderIdstr.")";
            $this->db->exec($orderitemsql);
           
            //异常备注不删除，LMZ20150120401。2015.2.11 liuzecheng
            //$this->db->exec("DELETE FROM sdb_ome_abnormal WHERE order_id in(".$orderIdstr.")");
            //$this->_operation_log('orders@ome',$orderIdstr);
            error_log("订单删除完成\n",3,__FILE__.'.page.log');
            echo "订单删除完成\n";
        }
     }
    
    /**
      * 删除订单相关信息
      * @param  
      * @return 
      * @access  public
      * @author sunjing@shopex.cn
      */
     function _delete_delivery($deliveryIdstr)
     {
        $deliverysql = "DELETE FROM sdb_ome_delivery WHERE delivery_id in(".$deliveryIdstr.")";
     
        $this->db->exec($deliverysql);
        $deliveryordsql = "DELETE FROM sdb_ome_delivery_order WHERE delivery_id in(".$deliveryIdstr.")";
       
        $this->db->exec($deliveryordsql);
        $deliverybillsql = "DELETE FROM sdb_ome_delivery_bill WHERE delivery_id in(".$deliveryIdstr.")";
        
        $this->db->exec($deliverybillsql);
        $itemsql = "DELETE FROM sdb_ome_delivery_items WHERE delivery_id in(".$deliveryIdstr.")";
       
        $this->db->exec($itemsql);
        $detailsql = "DELETE FROM sdb_ome_delivery_items_detail WHERE delivery_id in(".$deliveryIdstr.")";
      
        $this->db->exec($detailsql);
        //$this->_operation_log('delivery@ome',$deliveryIdstr);
        echo "发货单删除完成\n";
     }

    
    /**
     *发货单子集.
     * @param   
     * @return  
     * @access  
     * @author sunjing@shopex.cn
     */
    function _childrendeliveryId($delivery_id)
    {
        $deliveryList = $this->db->select("SELECT delivery_id FROM sdb_ome_delivery where parent_id=".$delivery_id);
        $deliveryIds = array();
        if ($deliveryList) {
            foreach ( $deliveryList as $delivery ) {
                $deliveryIds[] = $delivery['delivery_id'];
            }
        }
        
        return $deliveryIds;
    }
  

    /**
     * 订单操作日志
     * @param   
     * @return  
     * @access  public
     * @author  sunjing@shopex.cn
     */
    function _operation_log($obj_type,$orderIdstr)
    {
        $sql = "DELETE FROM sdb_ome_operation_log WHERE obj_id in(".$orderIdstr.") AND obj_type in('".$obj_type."')";

         $this->db->exec($sql);
    }

    
    /**
     * 更新关联单据为已归档
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function archive_bill($order_list)
    {
        $orderIds = array();
        foreach ($order_list as $order ) {
            $orderIds[] = $order['order_id'];
        }
        if ($orderIds) {
            $orderIdstr = implode(',',$orderIds);
            $apply_sql = "UPDATE sdb_ome_refund_apply SET archive='1' WHERE order_id in(".$orderIdstr.")";
           
            $this->db->exec($apply_sql);
            $return_sql = "UPDATE sdb_ome_return_product SET archive='1' WHERE order_id in(".$orderIdstr.")";
         
            $this->db->exec($return_sql);
            $reship_sql = "UPDATE sdb_ome_reship SET archive='1' WHERE order_id in(".$orderIdstr.")";
           
            $this->db->exec($reship_sql);
            $sales_sql = "UPDATE sdb_ome_sales SET archive='1' WHERE order_id in(".$orderIdstr.")";
          
            $this->db->exec($sales_sql);
            $aftersale_sql = "UPDATE sdb_sales_aftersale SET archive='1' WHERE order_id in(".$orderIdstr.")";
           
            $this->db->exec($aftersale_sql);

            $payments_sql = "UPDATE sdb_ome_payments SET archive='1' WHERE order_id in(".$orderIdstr.")";
           
            $this->db->exec($payments_sql);
            $refunds_sql = "UPDATE sdb_ome_refunds SET archive='1' WHERE order_id in(".$orderIdstr.")";
        
            
            $this->db->exec($refunds_sql);
       
            echo "相关单据更新为已归档完成\n";
        }
    }
    
   
   
    
    /**
     * 判断是否归档类型.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function is_archive($source)
    {
        $result = false;
        if (($source && in_array($source,array('archive'))) || $source=='1') {
            $result = true;
        }
        return $result;
    }

    
    /**
     * 最新归档时间.
     * @param
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_archive_time()
    {
        $archive_log = $this->db->selectrow("SELECT archive_time FROM sdb_archive_operation_log WHERE archive_time>0 ORDER BY archive_time DESC");
        return $archive_log['archive_time'];
    }

    function optimize($table)
    {
        $sql = 'OPTIMIZE TABLE '.$table;
        $this->db->exec($sql);

    }

    
    
    function getdeliveryidByOutbn($delivery_bns)
    {
        $wmsdeliveryIds = array();
        $delivery_bns = implode(',',$delivery_bns);
        $deliverys = $this->db->select("SELECT delivery_id FROM sdb_wms_delivery WHERE outer_delivery_bn in(".$delivery_bns.")");
        if ($deliverys) {
            foreach ($deliverys as $delivery) {
                $wmsdeliveryIds[] = $delivery['delivery_id'];
            }
        }
        
        return $wmsdeliveryIds;
    }

    
    
    function deleteWmsdelivery($delivery_ids)
    {
        $delivery_ids = implode(',',$delivery_ids);
        $this->db->select("DELETE FROM sdb_wms_delivery WHERE delivery_id in(".$delivery_ids.")");
        $this->db->select("DELETE FROM sdb_wms_delivery_bill WHERE delivery_id in(".$delivery_ids.")");
        $this->db->select("DELETE FROM sdb_wms_delivery_items WHERE delivery_id in(".$delivery_ids.")");
    }

    
    
    function copyWmsdelivery($delivery_ids)
    {
        $delivery_ids = implode(',',$delivery_ids);
        $keys = 'delivery_id,idx_split,skuNum,itemNum,bnsContent,delivery_bn,member_id,is_protect,cost_protect,is_cod,logi_id,logi_name,logi_number,delivery_logi_number,ship_name,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,create_time,STATUS,print_status,process_status,memo,disabled,branch_id,last_modified,delivery_time,delivery_cost_expect,delivery_cost_actual,bind_key,`type`,shop_id,is_sync,order_createtime,ship_time,op_id,op_name,outer_delivery_bn';
        $deliverysql = "INSERT INTO sdb_archive_wmsdelivery(".$keys.") SELECT ".$keys." FROM sdb_wms_delivery WHERE delivery_id in (".$delivery_ids.")";
        $itemkeys = 'item_id,delivery_id,product_id,bn,product_name,number,price,sale_price,pmt_price';
        $itemsql = "INSERT INTO sdb_archive_wmsdelivery_items(".$itemkeys.") SELECT ".$itemkeys." FROM sdb_wms_delivery_items WHERE delivery_id in (".$delivery_ids.")" ;
        $billkeys = 'b_id,delivery_id,logi_no,`type`,print_status,`status`,net_weight,weight,create_time,delivery_time';
        $billsql = "INSERT INTO sdb_archive_wmsdelivery_bill(".$billkeys.") SELECT ".$billkeys." FROM sdb_wms_delivery_bill WHERE delivery_id in (".$delivery_ids.")";
        $this->db->exec($deliverysql);
        $this->db->exec($itemsql);
        $this->db->exec($billsql);

    }

    function copyTables($keys,$datarow)
    {
        $order_key = explode(',',$keys);
        $values = array();
        foreach ($order_key as $ordervalue ) {
            $ordervalue = str_replace('`','',$ordervalue);
             $datavalue = $datarow[$ordervalue];
            if (is_string($datavalue)) {
                $datavalue = addslashes($datavalue);
            }
            $values[] = "'".$datavalue."'";
        }
        $values = "(".implode(',',$values).")";
        return $values;
    }

    
    
    function get_total($orderfilter)
    {
        $sqlstr = "WHERE (`archive`='1' OR `status` in('dead') ";
        $archive_time = $this->archivetimeFilter($orderfilter['archive_time']);
        
        $status = $orderfilter['status'];
        
        if ($status) {
            if (in_array('fail',$status)) {
                
                $sqlstr.= " OR (pay_status='0' AND process_status in ('unconfirmed'))";
            }
            if (in_array('unpayed',$status)) {
                
                $sqlstr.= " OR (is_fail='true' AND pay_status in ('0','1'))";
            }
        }
        $sqlstr.=') AND createtime<'.$archive_time;
        $order_total = $this->db->selectrow("SELECT count(order_id) as _count FROM sdb_ome_orders ".$sqlstr);
        
        $total = $order_total['_count'];
        return $total;
    }

    
    /**
     *处理操作归档
     * @param   
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function process($orderfilter)
    {
        $sqlstr = "WHERE (`archive`='1' OR `status` in('dead') ";
        $archive_time = $this->archivetimeFilter($orderfilter['archive_time']);
        
        $status = $orderfilter['status'];
        
        if ($status) {
            if (in_array('fail',$status)) {
                
                $sqlstr.= " OR (pay_status='0' AND process_status in ('unconfirmed'))";
            }
            if (in_array('unpayed',$status)) {
                
                $sqlstr.= " OR (is_fail='true' AND pay_status in ('0','1'))";
            }
        }
        $sqlstr.=') AND createtime<'.$archive_time;
        $order_list = $this->db->select("SELECT order_id FROM sdb_ome_orders ".$sqlstr." ORDER BY createtime ASC LIMIT 0,500");
         if ($order_list) {
        
            $this->_create_order($order_list);
         
            $this->_create_delivery($order_list);
           
            $this->_delete_order($order_list);
        
            $this->archive_bill($order_list);
        }
        unset($order_list);
        return true;
    }
    
    
}

?>