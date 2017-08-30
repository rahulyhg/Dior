<?php
class wms_event_receive_goods extends wms_event_response{

    /**
     * 商品通知单创建事件
     * @param array $data
     */
    public function create($data){
        
        
        $new_tag = '1';
        $sync_status = '3';
        
        kernel::single('console_foreignsku')->batch_syncupdate($data['wms_id'],$new_tag,$sync_status,$bns);
        return $this->send_succ();
    }

    /**
     * 商品通知单状态变更事件
     * @param array $data
     */
    public function updateStatus($data){
        

        $new_tag = '1';
        $sync_status = '3';
        kernel::single('console_foreignsku')->batch_syncupdate($wms_id,$new_tag,$sync_status,$bns);
        return $this->send_succ();;
    }

}
