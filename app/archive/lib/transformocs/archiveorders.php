<?php
/*
* 此类是把OCS数据转化为ERP归档表中
*/

class archive_transformocs_archiveorders  extends archive_transformocs_abstract
{
    static public $products = array();
   
    public function do_exec()
    {
        
        $wheresql = array();
        $sqlstr = '';
        if ($filter['shop_id']) {
            $wheresql[]= 'shop_id in(\''.implode('\',\'',$filter['shop_id']).'\')';
        }
        
        if ($wheresql) {
            $sqlstr.=" WHERE ".implode(' AND ',$wheresql);
        }
        $SQL = "SELECT count(order_id) as _count FROM sdb_ome_orders".$sqlstr." ORDER BY order_id ASC";
        $query = mysql_query($SQL,$this->conn) or die('no query'.$SQL);
        $orderRow = mysql_fetch_assoc($query);
        $total =$orderRow['_count'];
        //$total = 10;
        $pagelimit = 100;
        $page = ceil($total/$pagelimit);
        $fail = 0;$succ=0;
        for($i=1;$i<=$page;$i++){
           
            $offset = $pagelimit*($i-1);
            $offset = max($offset,0);
            $keys='order_id,order_bn,process_status,member_id,`status`,pay_status,ship_status,pay_bn,payment,itemnum,createtime,download_time,last_modified,shop_id,shop_type,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_email,ship_time,ship_mobile,consigner_name,consigner_area,consigner_addr,consigner_zip,consigner_email,consigner_mobile,consigner_tel,cost_item,is_tax,cost_tax,tax_company,cost_freight,cost_protect,is_cod,is_fail,discount,pmt_goods,pmt_order,total_amount,final_amount,payed,custom_mark,mark_text,tax_no,coupons_name,source,order_type,order_job_no, relate_order_bn';
            $querysql = "SELECT ".$keys." FROM sdb_ome_orders".$sqlstr;

            $queryrow = mysql_query($querysql."  ORDER BY order_id ASC LIMIT $offset,$pagelimit",$this->conn);
          
            $orderid_list = array();
            $member_list = array();
            while ($orderrow =mysql_fetch_assoc($queryrow) ) {
                
                $order_id = $orderrow['order_id'];
                //查询订单号是否已存在
                $order_bn = $orderrow['order_bn'];
                $orders = $this->db->selectrow("SELECT order_id FROM sdb_archive_orders WHERE order_bn='".$order_bn."'");
                if (!$orders) {
                    $this->db->exec('begin');
                    $orderrow['member_id'] = $this->transMember($orderrow['member_id']);
                    $orders_result = $this->copyOrders($keys,$orderrow);
                    $delivery_result = $this->copyDeliverys($order_id);
                    if ($orders_result && $delivery_result) {
                        $this->db->commit();
                    }else{
                        $this->db->rollBack();
                        error_log($order_bn.",",3,DATA_DIR.'.fail.log');
                    }
                }
                

            }
            mysql_free_result($queryrow);
        }
       
        // 执行完成更新所有单号基数值加5000000
        $this->db->exec("UPDATE sdb_archive_orders SET order_id=order_id+5000000 WHERE order_id<5000000");
        $this->db->exec("UPDATE sdb_archive_order_items SET order_id=order_id+5000000,item_id=item_id+5000000,obj_id=obj_id+5000000 WHERE order_id<5000000 AND item_id<5000000 AND obj_id<5000000");
        $this->db->exec("UPDATE sdb_archive_order_objects SET order_id=order_id+5000000,obj_id=obj_id+5000000 WHERE order_id<5000000 AND obj_id<5000000");
        $this->db->exec("UPDATE sdb_archive_delivery_order SET order_id=order_id+5000000,delivery_id=delivery_id+5000000  WHERE order_id<5000000 AND delivery_id<5000000");
        $this->db->exec("UPDATE sdb_archive_delivery SET delivery_id=delivery_id+5000000 WHERE delivery_id<5000000");
        $this->db->exec("UPDATE sdb_archive_delivery_items SET delivery_id=delivery_id+5000000,item_id=item_id+5000000 WHERE delivery_id<5000000 AND item_id<5000000");
    }

        
    public function copyOrders($keys,$orderrow)
    {
        $order_flag = true;
        $values = $this->copyTables($keys,$orderrow);
        $ordersql = "INSERT INTO sdb_archive_orders(".$keys.") VALUES".$values;
        $order_result = $this->db->exec($ordersql);
        
        if ($order_result) {
            $order_id = $orderrow['order_id'];
            $itemkey = 'item_id,order_id,obj_id,product_id,bn,name,cost,price,amount,pmt_price,sale_price,nums,sendnum,item_type,score,`delete`,sell_code';
            $itemquery = mysql_query("SELECT ".$itemkey." FROM sdb_ome_order_items WHERE order_id=".$order_id,$this->conn);
   
            while ($items = mysql_fetch_assoc($itemquery)) {
                $products = $this->transGoods($items['bn']);
                $items['product_id'] = $products['product_id'];
                $itemvalues = $this->copyTables($itemkey,$items);
                $itemsql = "INSERT INTO sdb_archive_order_items(".$itemkey.") VALUES".$itemvalues;
                $item_result = $this->db->exec($itemsql);
                if (!$item_result) {
                    $order_flag = false;
                    break;
                }
            }

            $objectkey = 'obj_id,order_id,oid,obj_type,goods_id,bn,name,price,amount,quantity,pmt_price,sale_price';
                             
            $objectquery = mysql_query("SELECT ".$objectkey." FROM sdb_ome_order_objects WHERE order_id=".$order_id,$this->conn);
            while($orderobject = mysql_fetch_assoc($objectquery)){
                $products = $this->transGoods($orderobject['bn']);
                $orderobject['goods_id'] = $products['goods_id'];
                $objectvalues = $this->copyTables($objectkey,$orderobject);
                $objectsql = "INSERT INTO sdb_archive_order_objects(".$objectkey.") VALUES".$objectvalues;
                $object_result = $this->db->exec($objectsql);
                if (!$object_result) {
                    $order_flag = false;
                    break;
                }
            }
            
        }
        return $order_flag;
    }
        
        
        
    public function copyDeliverys($order_id)
    {
        $deliveryquery = mysql_query("SELECT d.* FROM sdb_ome_delivery_order  as od LEFT JOIN sdb_ome_delivery as d on od.delivery_id=d.delivery_id LEFT JOIN sdb_ome_orders as o on o.order_id=od.order_id WHERE o.order_id='".$order_id."'",$this->conn);
        $delivery_flag = true;
        if ($deliveryquery) {
            while($delivery_detail = mysql_fetch_assoc($deliveryquery)){
                $delivery_id = $delivery_detail['delivery_id'];
                $deliverykey = 'shop_id,delivery_id,delivery_bn,member_id,is_protect,cost_protect,is_cod,delivery,logi_id,logi_name,logi_no,ship_name,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,create_time,status,memo,branch_id,last_modified,delivery_time,ship_time,op_id,op_name';
                $delivery_detail['member_id'] = $this->transMember($delivery_detail['member_id']);
                $deliveryvalues = $this->copyTables($deliverykey,$delivery_detail);
                $deliverysql = "INSERT INTO sdb_archive_delivery(".$deliverykey.") VALUES".$deliveryvalues;
                $delivery_result = $this->db->exec($deliverysql);
                if (!$delivery_result) {
                    $delivery_flag = false;
                    break;
                }
                if ($delivery_flag) {
                    $itemkey = 'item_id,delivery_id,product_id,bn,product_name,number';
                    $itemsquery = mysql_query("SELECT ".$itemkey." FROM sdb_ome_delivery_items WHERE delivery_id=".$delivery_id,$this->conn);
                    while($items = mysql_fetch_assoc($itemsquery)){
                        $products = $this->transGoods($items['bn']);
                        $items['product_id'] = $products['product_id'];

                        $itemvalues = $this->copyTables($itemkey,$items);
                        $itemsql = "INSERT INTO sdb_archive_delivery_items(".$itemkey.") VALUES".$itemvalues;
                        $items_result = $this->db->exec($itemsql);
                        if (!$items_result) {
                            $delivery_flag = false;
                            break 2;
                        }
                    }
                    $this->db->exec("INSERT INTO sdb_archive_delivery_order(order_id,delivery_id) VALUES('$order_id','$delivery_id')");
                }

            }
        }
        
        return $delivery_flag;
    }
} 


?>