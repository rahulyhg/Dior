<?php
class wms_event_receive_purchase extends wms_event_response{

    /**
     * 采购通知单创建事件
     * @param array $data
     */
    public function create($data){
        //检查采购通知单数据信息

        //创建采购通知单

        
        //error_log('purchase:'.var_export($data,1),3,__FILE__.".log");
        return $this->send_succ();
    }

    /**
     * 采购通知单状态变更事件
     * @param array $data
     */
    public function updateStatus($data){
        //检查采购通知单是否存在
        
        //检查当前采购通知单状态是否有效

        //更新采购通知单状态
        
        //error_log('cancel\n',3,__FILE__.".log");
        return $this->send_succ();
    }

}
