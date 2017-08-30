<?php
class wms_event_receive_reship extends wms_event_response{

    /**
     * 退货单创建事件
     * @param array $data
     */
    public function create($data){
               
        #error_log('reship:'.var_export($data,1),3,__FILE__.".log");
        return $this->send_succ();
    }

    /**
     * 退货单通知单状态变更事件
     * @param array $data
     */
    public function updateStatus($data){
       

        //error_log('cancel\n',3,__FILE__.".log");
        return $this->send_succ();;
    }

}
