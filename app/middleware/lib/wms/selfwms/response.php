<?php
/**
* 自有仓储接收调用类
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_response extends middleware_wms_responseInterface{

    /**
    * 入库单结果回传
    * @return Array 标准输出格式
    */
    public function stockin_result($params=array()){
        return kernel::single('middleware_wms_selfwms_response_stockin')->result($params);
    }

    /**
    * 出库单结果回传
    * @return Array 标准输出格式
    */
    public function stockout_result($params=array()){
        return kernel::single('middleware_wms_selfwms_response_stockout')->result($params);
    }

    /**
    * 转储单结果回传
    * @return Array 标准输出格式
    */
    public function stockdump_result($params=array()){
        return kernel::single('middleware_wms_selfwms_response_stockdump')->result($params);
    }

    /**
    * 发货单结果回传
    * @return Array 标准输出格式
    */
    public function delivery_result($params=array()){
        return kernel::single('middleware_wms_selfwms_response_delivery')->result($params);
    }

    /**
    * 退货单结果回传
    * @return Array 标准输出格式
    */
    public function reship_result($params=array()){
        return kernel::single('middleware_wms_selfwms_response_reship')->result($params);
    }

    /**
    * 库存对账状态结果回传
    */
    public function stock_result($params=array()){
        return kernel::single('middleware_wms_selfwms_response_stock')->result($params);
    }

    /**
    * 盘点状态结果回传
    */
    public function inventory_result($params=array()){
        return kernel::single('middleware_wms_selfwms_response_inventory')->result($params);
    }

}