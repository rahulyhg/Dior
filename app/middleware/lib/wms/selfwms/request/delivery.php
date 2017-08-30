<?php
/**
* 发货单
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_request_delivery extends middleware_wms_selfwms_request_common{

    /**
    * 发货单创建
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function delivery_create(&$sdf,$sync=false){

        $wms_class = 'wms_event_receive_delivery';
        $wms_method = 'create';
        return $this->request($wms_class,$wms_method,$sdf);
    }

    /**
    * 发货单暂停
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function delivery_pause(&$sdf,$sync=false){
        
        $wms_class = 'wms_event_receive_delivery';
        $wms_method = 'pause';
        return $this->request($wms_class,$wms_method,$sdf);
    }

    /**
    * 发货单恢复
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function delivery_renew(&$sdf,$sync=false){
        
        $wms_class = 'wms_event_receive_delivery';
        $wms_method = 'renew';
        return $this->request($wms_class,$wms_method,$sdf);
    }

    /**
    * 发货单取消
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function delivery_cancel(&$sdf,$sync=false){
        
        $wms_class = 'wms_event_receive_delivery';
        $wms_method = 'cancel';
        return $this->request($wms_class,$wms_method,$sdf);
    }

}