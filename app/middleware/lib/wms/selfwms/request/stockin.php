<?php
/**
* 入库单
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_request_stockin extends middleware_wms_selfwms_request_common{

    /**
    * 入库单创建
    * @access public
    * @param Array $sdf 入库单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockin_create(&$sdf,$sync=false){
        
        $type = $sdf['io_type'];
        switch($type){
            case 'PURCHASE':#采购
                $wms_class = 'wms_event_receive_purchase';
                $wms_method = 'create';
                break;
           

            case 'ALLCOATE':#调拨
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'increate';
                break;
            case 'DIRECT':#直接
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'create';
                break;
            default:#其它 OTHER
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'create';
                break;
        }
        return $this->request($wms_class,$wms_method,$sdf);
    }

    /**
    * 入库单取消
    * @access public
    * @param Array $sdf 入库单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockin_cancel(&$sdf,$sync=false){
        
        $type = $sdf['io_type'];
        switch($type){
            case 'PURCHASE':#采购
                $wms_class = 'wms_event_receive_purchase';
                $wms_method = 'updateStatus';
                break;
            case 'ALLCOATE':#调拨
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'updateStatus';
                break;
            case 'DIRECT':#直接
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'updateStatus';
                break;
            default:#其它
                $wms_class = 'wms_event_receive_otherinstorage';
                $wms_method = 'updateStatus';
                break;
        }
        return $this->request($wms_class,$wms_method,$sdf);
    }

}