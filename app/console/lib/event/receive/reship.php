<?php
class console_event_receive_reship extends console_event_response{

    /**
     *
     * 退货单事件处理
     * @param array $datainStorage
     */
    public function updateStatus($data){
        
        $status = $data['status'];
        $reshipObj = kernel::single('console_receipt_reship');
        $msg = '';
        $check = $reshipObj->checkValid($data['reship_bn'],$status,$msg);
       
        if (!$check){
            
            return $this->send_error($msg);

        }
        switch($status){
            case 'PARTIN':
            case 'FINISH':
                
                $result = kernel::single('console_receipt_reship')->updateStatus($data,$msg);
                break; 
               
            case 'DENY':
            case 'CLOSE':
            case 'FAILED':
            case 'ACCEPT':
                $result = kernel::single('console_receipt_reship')->cancel($data,$msg);
                break;    
               
        }
        

        if ($result){
            return $this->send_succ('退货操作成功');
        }else{
            $msg = $msg!='' ? $msg :'更新失败';
            return $this->send_error($msg);
        }
        
        
    }

//    public function getBranchId($reship_bn)
//    {
//        $oReship = &app::get('ome')->model('reship');
//        $reship = $oReship->dump(array('reship_bn'=>$reship_bn),'branch_id');
//        return $reship['branch_id'];
//    }
}