<?php

class console_event_receive_stockaccount extends console_event_response{
    
    function create($data){
        $result = kernel::single('console_receipt_stockaccount')->checking($data);
        if ($result){
            return $this->send_succ();
        }else{
            return $this->send_error('更新失败');
        }
        
    }

}

?>