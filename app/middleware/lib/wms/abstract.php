<?php
/**
* WMS全局功能抽象类
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_abstract{

    /**
    * 获取RPC的CALLBACK地址
    *
    * @access public
    * @param String $adapterCallbackClass 适配器callback类名
    * @param String $adapterCallbackMethod 适配器callback方法
    * @param String $adapterCallbackParams 适配器callback参数
    * @return
    */
    public function getRpcCallback($adapterCallbackClass,$adapterCallbackMethod,$adapterCallbackParams){
        return kernel::single('rpc_caller')->getCallback($adapterCallbackClass,$adapterCallbackMethod,$adapterCallbackParams);
    }

    /**
    * 获取日志ID
    *
    * @access public
    * @return Number
    */
    public function getLogId(){
        $logObj = kernel::single('middleware_log');
        return $logObj->getLogId();
    }


    /**
    * 添加日志
    *
    * @access public
    * @return
    */
    public function writeLog($log_id,$log_title,$retry_class,$retry_method,$retry_params,$memo='',$api_type='request',$status='fail',$msg='请求中',$addon='',$log_type='other',$original_bn=''){
        return kernel::single('middleware_log')->writeLog($log_id,$log_title,$retry_class,$retry_method,$retry_params,$memo,$api_type,$status,$msg,$addon,$log_type,$original_bn);
    }

    /**
    * 更新日志
    *
    * @access public
    * @return
    */
    public function updateLog($log_id,$msg=NULL,$status=NULL,$params=NULL,$addon=NULL){
        return kernel::single('middleware_log')->updateLog($log_id,$msg,$status,$params,$addon);
    }

    /**
    * 查询日志信息
    *
    * @access public
    * @return
    */
    public function getLogDetail($filter,$field = '*',$subSdf = null){
        return kernel::single('middleware_log')->dump($filter,$field);
    }

    /**
    * 消息输出
    *
    * @access public
    * @return
    */
    public function msgOutput($rsp='fail', $msg=null, $msg_code=null, $data=array()){
        return kernel::single('middleware_message')->output($rsp, $msg, $msg_code, $data);
    }

    /**
    * 创建队列
    * @access public
    * @return
    */
    public function createQueue($queue_title,$run_class,$run_method,$params){
        
        $queueObj = &app::get('base')->model('queue');
        $queueData = array(
            'queue_title' => $queue_title,
            'start_time' => time(),
            'params'=> $params,
            'worker' => $run_class.'.'.$run_method,
        );
        return $queueObj->save($queueData);
    }

    /*
    * 获取随机数
    */
    static public function uniqid(){
        return middleware_func::uniqid();
    }

    /*
    * 判断类是否存在
    */
    static function class_exists($class_name){
        return middleware_func::class_exists($class_name);
    }

    /**
    * 获取商品是否同步成功
    */
    public function issync($product_bn,$node_id=''){
        return kernel::single('console_foreignsku')->issync($product_bn,$node_id);
    }

    /**
    * 更新商品同步状态
    */
    public function set_sync_status($data,$node_id=''){
        return kernel::single('console_foreignsku')->set_sync_status($data,$node_id);
    }

    /**
    * 根据node_id获取wms_id
    * @param String $node_id
    * @return String 
    */
    public function getWmsIdByNodeId($node_id=''){
        return kernel::single('middleware_adapter')->getWmsIdByNodeId($node_id);
    }

    /**
    * WMS物流公司编号转换
    * @param String $node_id
    * @return String 
    */
    public function getWmslogiCode($wms_id,$logi_code=''){
        $wms_logi_code = kernel::single('wmsmgr_func')->getWmslogiCode($wms_id,$logi_code);
        return $wms_logi_code ? $wms_logi_code : $logi_code;
    }

    /**
    * 获取WMS店铺售达方编号转换
    * @param String $node_id
    * @return String 
    */
    public function getWmsShopCode($wms_id,$shop_bn=''){
        $wms_shop_bn = kernel::single('wmsmgr_func')->getWmsShopCode($wms_id,$shop_bn);
        return $wms_shop_bn ? $wms_shop_bn : $shop_bn;
    }

    /**
    * 标准物流公司编号转换
    * @param String $node_id
    * @return String 
    */
    public function getlogiCode($wms_id,$wms_logi_code=''){
        $logi_code = kernel::single('wmsmgr_func')->getlogiCode($wms_id,$wms_logi_code);
        return $logi_code ? $logi_code : $wms_logi_code;
    }

    /**
    * 单据是否取消
    * @param String $io_bn 单据号
    * @param String $io_type 单据类型
    * @return bool
    */
    public function iscancel($io_bn='',$io_type=''){
        return kernel::single('middleware_iostock')->iscancel($io_bn,$io_type);
    }

    /*
    * 获取随机数
    */
    static public function gen_batchId(){
        return middleware_func::gen_batchId();
    }

     
    /**
     * 获取WMS
     * @param   wms_id
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function getAdapterTypeByWmsId($wms_id=''){
        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('node_type',array('channel_id'=>$wms_id),0,1);
        return isset($detail[0]) ? $detail[0]['node_type'] : '';
    }

    /**
     * 退货地区格式化处理
     * @param   
     * @return  
     * @access  private
     * @author sunjing@shopex.cn
     */
    public function _formate_receiver_citye($receiver_city)
    {
        $zhixiashi = array('北京','上海','天津','重庆');
        $zizhiqu = array('内蒙古','宁夏回族','新疆维吾尔','西藏','广西壮族');
        $zxdata = array();
        $zzq = array();
        $prov = array();
        
        preg_match('/(.*?)市/',$receiver_city,$zxdata);///^def/
        preg_match('/(.*?)自治区/',$receiver_city,$zzq);
        preg_match('/(.*?)省/',$receiver_city,$prov);

        if (!$zxdata && in_array($receiver_city,$zhixiashi)) {
           $receiver_city = $receiver_city.'市';
        }else if (!$zzq && in_array($receiver_city,$zizhiqu)) {
            $receiver_city = $receiver_city.'自治区';
        }elseif(!$prov){
            $receiver_city = $receiver_city.'省';
        }
        return $receiver_city;
    }

     
    /**
     * 获取外部发货单号
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function _getout_delivery_bn($delivery_bn)
    {
        $delivery_extObj = app::get('console')->model('delivery_extension');
        $detail = $delivery_extObj->dump(array('delivery_bn'=>$delivery_bn),'original_delivery_bn');
        return isset($detail) ? $detail['original_delivery_bn'] : '';
    }
}