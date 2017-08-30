<?php
class wms_event_receive_stockdump extends wms_event_response{

    /**
     * 转储通知单创建事件
     * @param array $data
     */
    public function create($data){
        

        
        //error_log('purchase:'.var_export($data,1),3,__FILE__.".log");
        return $this->send_succ();
    }

    /**
     * 转储单通知单状态变更事件
     * @param array $data
     */
    public function updateStatus($data){
       
        
        //error_log('cancel\n',3,__FILE__.".log");
        return $this->send_succ();;
    }

}
