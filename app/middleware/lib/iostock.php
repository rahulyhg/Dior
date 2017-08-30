<?php
/**
* 出入库事务
*
* @copyright shopex.cn 2013.4.10
* @author dongqiujin<123517746@qq.com>
*/
class middleware_iostock{

    /**
    * 入库单结果回传
    */
    public function stockin_result($sdf=''){
        return kernel::single('console_event_receive_iostock')->stockin_result($sdf);
    }

    /**
    * 出库单结果回传
    */
    public function stockout_result($sdf=''){
        return kernel::single('console_event_receive_iostock')->stockout_result($sdf);
    }

    /**
    * 转储单状态结果回传
    */
    public function stockdump_result($params=array()){
        return kernel::single('console_event_receive_iostock')->stockdump_result($params);
    }

    /**
    * 发货单状态结果回传
    */
    public function delivery_result($params=array()){
        $params['delivery_time'] = $params['operate_time'];
        return kernel::single('ome_event_receive_delivery')->update($params);
    }

    /**
    * 退货单状态结果回传
    */
    public function reship_result($params=array()){
        return kernel::single('console_event_receive_iostock')->reship_result($params);
    }

    /**
    * 库存对账状态结果回传
    */
    public function stock_result($params=array()){
        return kernel::single('console_event_receive_iostock')->stock_result($params);
    }

    /**
    * 盘点状态结果回传
    */
    public function inventory_result($params=array()){
        return kernel::single('console_event_receive_iostock')->inventory_result($params);
    }

    /**
    * 单据是否取消
    * @param String $io_bn 单据号
    * @param String $io_type 单据类型
    * @return bool
    */
    public function iscancel($io_bn='',$io_type=''){
        
        $iscancel = false;
        switch($io_type){
            case 'stockin':
            case 'stockout':
            case 'stockdump':
            case 'reship':
                $iscancel = kernel::single('console_service_commonstock')->iscancel($io_bn);
                break;
            case 'delivery':
                $iscancel = kernel::single('ome_interface_delivery')->iscancel($io_bn);
                break;
        }
        return $iscancel;
    }


}