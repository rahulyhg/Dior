<?php
/**
* 出库单
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_request_stockout extends middleware_wms_selfwms_request_common{

    /**
    * 出库单创建
    * @access public
    * @param Array $sdf 出库单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockout_create(&$sdf,$sync=false){
        
        $type = $sdf['io_type'];
        switch($type){
            case 'PURCHASE_RETURN':#采购退货
                $wms_class = 'wms_event_receive_purchasereturn';
                $wms_method = 'create';
                break;
            
            case 'ALLCOATE':#调拨出库
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'outcreate';
                break;
            case 'DIRECT':#直接出库
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'create';
                break;
            default:#其它出库 OTHER
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'create';
                break;
        }
        return $this->request($wms_class,$wms_method,$sdf);
    }

    /**
    * 出库单取消
    * @access public
    * @param Array $sdf 出库单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockout_cancel(&$sdf,$sync=false){
        
        $type = $sdf['io_type'];
        switch($type){
            case 'PURCHASE_RETURN':#采购退货
                $wms_class = 'wms_event_receive_purchasereturn';
                $wms_method = 'updateStatus';
                break;
            case 'ALLCOATE':#调拨出库
                $wms_class = 'wms_event_receive_allocate';
                $wms_method = 'updateStatus';
                break;
            case 'DIRECT':#直接出库
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'updateStatus';
                break;
            default:#其它出库 OTHER
                $wms_class = 'wms_event_receive_otheroutstorage';
                $wms_method = 'updateStatus';
                break;
        }
        return $this->request($wms_class,$wms_method,$sdf);
    }

}