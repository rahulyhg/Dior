<?php
/**
 * CSM平台数据联通
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2011
 *
 */
class ome_rpc_request_csm  extends ome_rpc_request {
   var $pid; 
   var $Certi;
   var $Node_id;
   var $ome_host;
   var $ome_ip;
   var $version = '1.0';
   var $format = 'json';
   var $shop;
   var $last_request_time;
   var $curr_request_time;

   function __construct(){
       $this->Certi = base_certificate::get('certificate_id');
       $this->Node_id = base_shopnode::node_id('ome');
       $this->ome_host = kernel::base_url(true);
       $this->ome_ip = $_SERVER["SERVER_ADDR"]?$_SERVER["SERVER_ADDR"]:'127.0.0.1';
       $deploy_info = base_setup_config::deploy_info();
       $this->pid = $deploy_info['product_id'];
   }
      
   function request($time_out=5){   
       $last_request_time = app::get('ome')->getConf('request_csm_last_time');
       $this->last_request_time = $last_request_time ? $last_request_time : 0;
       $this->curr_request_time = time();
       $shop_list = app::get('ome')->model('shop')->getList('shop_id,node_type', array('active'=>'true'), 0, -1);
       foreach($shop_list as $shop){
           if(empty($shop['shop_id']) || empty($shop['node_type']))continue;
            $this->shop = $shop;
            
            foreach(kernel::servicelist('service.ome.csm_data') as $object=>$instance){
                if(method_exists($instance,'getData')){
                    $extend_params = $instance->getData($shop,$this->last_request_time,$this->curr_request_time);
                }
            }
            
            if(is_array($extend_params) && count($extend_params)>0){
                $params = array();
                foreach(get_class_methods($this) as $method){
                   if(substr($method,0,4) == 'get_'){
                       $params[substr($method,4,strlen($method)-1)] = $this->{$method}();
                   }
                }
                    
               $params = array_merge($params,$extend_params);
           
               $params['sign'] = $this->make_sign($params);
               $this->direct_request(CSM_URL,$params);
            }
       }
       app::get('ome')->setConf('request_csm_last_time',$this->curr_request_time);
   }
    
   /**
    * 返回产品类型
    * 
    * @return String
    */
   function get_pid(){
       
       return $this->pid;
   }
   
   /**
    * 返回证书号
    * 
    * @return Int
    */
   function get_licid(){
       
       return $this->Certi;
   }
   
   /**
    * 返回节点号
    * 
    * @return Int
    */
   function get_nid(){
       
       return $this->Node_id;
   }
   
   /**
    * 返回父证书号，无父证书号填本身
    * 
    * @return String
    */
   function get_prtlicid(){
       
       return $this->Certi;
   }
   
   /**
    * 返回父节点号，无父节点号填本身
    * 
    * @return String
    */
   function get_prtnid(){
       
      return $this->Node_id;
   }
   
   /**
    * 返回来源ip
    * 
    * @return String
    */
   function get_ip(){
       
      return $this->ome_ip;
   }
   
   /**
    * 返回来源域名
    * 
    * @return String
    */
   function get_url(){
       
        return $this->ome_host;
   }
   
   /**
    * 返回接口版本号
    * 
    * @return String
    */
   function get_version(){
       
      return $this->version;
   }
   
   /**
    * 返回数据序列号
    * 
    * @return String
    */
   function get_serial(){
        $serial = app::get('ome')->getConf('request_csm_serial');
        $serial = $serial ? ($serial+1) : 1;
        app::get('ome')->setConf('request_csm_serial',$serial);
         
        return $serial;
   }
   
   /**
    * 返回参数格式
    * 
    * @return String
    */
   function get_format(){
       
       return $this->format;
   }
   
   /**
    * 返回店铺类型
    * 
    * @return String
    */
   function get___stat_shoptype(){
       return $this->shop['node_type'];
   }
   
   
   /**
    * 返回登录时长
    * 
    * 
    * @return String
    */
   function get___stat_onlinetime(){
       
       return 0;
   }

}