<?php
/**
* 转储单
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_request_stockdump extends middleware_wms_selfwms_request_common{

    /**
    * 转储单创建
    * @access public
    * @param Array $sdf 转储单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockdump_create(&$sdf,$sync=false){

        $wms_class = 'wms_event_receive_stockdump';
        $wms_method = 'create';
        return $this->request($wms_class,$wms_method,$sdf);
    }

    /**
    * 转储单取消
    * @access public
    * @param Array $sdf 转储单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockdump_cancel(&$sdf,$sync=false){
        
        $wms_class = 'wms_event_receive_stockdump';
        $wms_method = 'updateStatus';
        return $this->request($wms_class,$wms_method,$sdf);
    }

}