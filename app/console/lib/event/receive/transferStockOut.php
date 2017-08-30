<?php
class console_event_receive_transferStockOut extends console_event_response{

    /**
     *
     * 调拔单出库事件处理
     * @param array $data
     */
    public function outStorage($data){
        //
        //接收调拔出库发起调拔入库通知
        $io = '0';
        #自有仓储不作处理
        if ($data['io_source'] == 'selfwms'){
            //return $this->send_succ();
        }
        #
        $stockObj = kernel::single('console_receipt_stock');

        $io_status = $data['io_status'];
        #查询单据是否存在
        if(!$stockObj->checkExist($data['io_bn'])){
           return $this->send_error('单据不存在');
        }

        #查询状态是否可操作
        $msg = '';
        if(!$stockObj->checkValid($data['io_bn'],$io_status,$msg)){
           return $this->send_error($msg);
        }
        #根据状态执行对应操作
        switch($io_status){
            case 'PARTIN':
            case 'FINISH':
                $result = kernel::single('console_receipt_stock')->do_save($data,$io,$msg);
            break;
            case 'FAILED':
            case 'CANCEL':
            case 'CLOSE':
                $result = kernel::single('console_receipt_stock')->cancel($data);
                break;
            default:
                return $this->send_succ('未定义的调拔出库单指令');
                break;
        }
        if($result){
            return $this->send_succ('调拔出库单操作成功');
        }else{
            return $this->send_error('更新失败');
        }
        
    }

//    public function getBranchId($io_bn)
//    {
//        $Oiso = &app::get('taoguaniostockorder')->model("iso");
//        $iso = $Oiso->dump(array('iso_bn'=>$io_bn),'branch_id');
//        return $iso['branch_id'];
//    }

}