<?php
class console_event_receive_otherStockin extends console_event_response{

    /**
     *
     * 其它入库事件处理
     * @param array $data
     */
    public function inStorage($data){
    

        $stockObj = kernel::single('console_receipt_stock');
        $io = 1;
        $io_status = $data['io_status'];
        if ($data['io_source'] == 'selfwms'){//自有仓储不作处理
            //return $this->send_succ();
        }
        #检查编号是否存在
        if (!$stockObj->checkExist($data['io_bn'])){
            return $this->send_error('入库单号不存在');
        }
        
        
        #检查状态是否可操作
        if (!$stockObj->checkValid($data['io_bn'],$io_status,$msg)){
            return $this->send_error($msg);
        }
        switch($io_status){
            case 'PARTIN':
            case 'FINISH':
 
                $result = kernel::single('console_receipt_stock')->do_save($data,$io,$msg);
            break;
            case 'FAILED':
            case 'CANCEL':
            case 'CLOSE':
                $result = kernel::single('console_receipt_stock')->cancel($data,$io);
                break;
            default:
                return $this->send_succ('无法识别的操作指令');
                break;
        }

        if ($result){
            return $this->send_succ('入库请求操作成功');
        }else{

        }
        
    }
//    public function getBranchId($io_bn)
//    {
//        $Oiso = &app::get('taoguaniostockorder')->model("iso");
//        $iso = $Oiso->dump(array('iso_bn'=>$io_bn),'branch_id');
//        return $iso['branch_id'];
//    }
}