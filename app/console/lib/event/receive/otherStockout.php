<?php
class console_event_receive_otherStockout extends console_event_response{

    /**
     *
     * 其它出库事件处理
     * @param array $data
     */
    public function outStorage($data){
       
        $stockObj = kernel::single('console_receipt_stock');
        $io = '0';
        if ($data['io_source'] == 'selfwms'){//自有仓储不作处理
            //return $this->send_succ();
        }
        if (!$stockObj->checkExist($data['io_bn'])){
            return $this->send_error('出库单号不存在');
        }
        $io_status = $data['io_status'];
        if (!$stockObj->checkValid($data['io_bn'],$io_status,$msg)){
            return $this->send_error('状态不可操作');
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
                break;
        }
        if ($result){
            return $this->send_succ('出库处理成功');
        }else{
            return $this->send_error($msg,'',$data);
        }
    }
    
//    public function getBranchId($io_bn)
//    {
//        $Oiso = &app::get('taoguaniostockorder')->model("iso");
//        $iso = $Oiso->dump(array('iso_bn'=>$io_bn),'branch_id');
//        return $iso['branch_id'];
//    }
}