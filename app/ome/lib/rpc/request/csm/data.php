<?php
/**
 * CSM平台数据
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2011
 *
 */
class ome_rpc_request_csm_data {
   var $shop;
   var $last_request_time;
   var $curr_request_time;

   function getData($shop,$last_request_time,$curr_request_time){
       $this->last_request_time = $last_request_time;
       $this->curr_request_time = $curr_request_time;
       $this->shop = $shop;
       $params = array();
       foreach(get_class_methods($this) as $method){
          if(substr($method,0,4) == 'get_'){
              $params[substr($method,4,strlen($method)-1)] = $this->{$method}();
          }
       }
       
       return $params;
   }
  
   
   /**
    * 返回上传淘宝订单数
    * 
    * @return String
    */
   function get___stat_upload(){
       $model_orders = app::get('ome')->model('orders');
       $row = $model_orders->db->selectrow('SELECT count(*) as _count FROM '.$model_orders->table_name(1).' WHERE shop_id ="'.$this->shop['shop_id'].'" AND up_time IS NOT NULL');
       $last_request_upload = app::get('ome')->getConf('request_csm_last_upload');
       $last_request_upload = $last_request_upload ? $last_request_upload : 0;
       $count = intval($row['_count'])-$last_request_upload;
       app::get('ome')->setConf('request_csm_last_upload',intval($row['_count']));
       
       if($count < 0){//出负数，可能数据被归档，之后数据就会变正常
           return  0;
       }else{
           return $count;
       }
   }
   
   /**
    * 返回下载订单数
    * 
    * @return String
    */
   function get___stat_download(){
       $model_orders = app::get('ome')->model('orders');
       $row = $model_orders->db->selectrow('SELECT count(*) as _count FROM '.$model_orders->table_name(1).' WHERE shop_id ="'.$this->shop['shop_id'].'" AND download_time IS NOT NULL');
       $last_request_download = app::get('ome')->getConf('request_csm_last_download');
       $last_request_download = $last_request_download ? $last_request_download : 0;
       $count = intval($row['_count'])-$last_request_download;
       app::get('ome')->setConf('request_csm_last_download',intval($row['_count']));
       
       if($count < 0){//出负数，可能数据被归档，之后数据就会变正常
           return  0;
       }else{
           return $count;
       }
   }
   
   /**
    * 返回打印发货单数
    * 
    * @return String
    */
   function get___stat_prtdelv(){
       $model_delivery = app::get('ome')->model('delivery');
       $row = $model_delivery->db->selectrow('SELECT count(*) as _count FROM '.$model_delivery->table_name(1).' WHERE shop_id ="'.$this->shop['shop_id'].'" AND deliv_status="true" AND is_bind="false"');
       $last_request_prtdelv = app::get('ome')->getConf('request_csm_last_prtdelv');
       $last_request_prtdelv = $last_request_prtdelv ? $last_request_prtdelv : 0;
       $count = intval($row['_count'])-$last_request_prtdelv;
       app::get('ome')->setConf('request_csm_last_prtdelv',intval($row['_count']));
       
       if($count < 0){//出负数，可能数据被归档，之后数据就会变正常
           return  0;
       }else{
           return $count;
       }
   }
   
   /**
    * 返回打印快递单数
    * 
    * @return Int
    */
   function get___stat_prtexpres(){
       $model_delivery = app::get('ome')->model('delivery');
       $row = $model_delivery->db->selectrow('SELECT count(*) as _count FROM '.$model_delivery->table_name(1).' WHERE shop_id ="'.$this->shop['shop_id'].'" AND expre_status="true" AND is_bind="false"');
       $last_request_prtexpres = app::get('ome')->getConf('request_csm_last_prtexpres');
       $last_request_prtexpres = $last_request_prtexpres ? $last_request_prtexpres : 0;
       $count = intval($row['_count'])-$last_request_prtexpres;
       app::get('ome')->setConf('request_csm_last_prtexpres',intval($row['_count']));
       
       if($count < 0){//出负数，可能数据被归档，之后数据就会变正常
           return  0;
       }else{
           return $count;
       }
   }
   
   /**
    * 返回打印备货单数
    * 
    * @return String
    */
   function get___stat_prtstock(){
       $model_delivery = app::get('ome')->model('delivery');
       $row = $model_delivery->db->selectrow('SELECT count(*) as _count FROM '.$model_delivery->table_name(1).' WHERE shop_id ="'.$this->shop['shop_id'].'" AND stock_status="true" AND is_bind="false"');
       $last_request_prtstock = app::get('ome')->getConf('request_csm_last_prtstock');
       $last_request_prtstock = $last_request_prtstock ? $last_request_prtstock : 0;
       $count = intval($row['_count'])-$last_request_prtstock;
       app::get('ome')->setConf('request_csm_last_prtstock',intval($row['_count']));
       
       if($count < 0){//出负数，可能数据被归档，之后数据就会变正常
           return  0;
       }else{
           return $count;
       }
   }
   
   /**
    * 返回销售货品数
    * 
    * @return String
    */
   function get___stat_sellcount(){
       $model_orders = app::get('ome')->model('orders');
       $model_order_items = app::get('ome')->model('order_items');
       $row = $model_orders->db->selectrow('SELECT SUM(oi.nums) AS _count FROM sdb_ome_orders AS o LEFT JOIN sdb_ome_payments as p ON o.order_id=p.order_id LEFT JOIN sdb_ome_order_items AS oi ON p.order_id = oi.order_id 
                                                WHERE o.shop_id ="'.$this->shop['shop_id'].'" 
                                                AND o.pay_status="1" 
                                                AND p.t_begin>='.$this->last_request_time.'
                                                AND p.t_begin<'.$this->curr_request_time.'
                                                AND oi.delete="false"');

       return intval($row['_count']);
   }
   
   /**
    * 返回销售订单金额
    * 
    * @return String
    */
   function get___stat_ordersum(){
       $model_orders = app::get('ome')->model('orders');
       $row = $model_orders->db->selectrow('SELECT SUM(o.total_amount) AS _count FROM sdb_ome_orders AS o LEFT JOIN sdb_ome_payments as p ON o.order_id=p.order_id 
                                                WHERE o.shop_id ="'.$this->shop['shop_id'].'" 
                                                AND o.pay_status="1" 
                                                AND p.t_begin>='.$this->last_request_time.'
                                                AND p.t_begin<'.$this->curr_request_time);    
       return floatval($row['_count']);
   }

}