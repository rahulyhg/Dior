<?php
class console_event_receive_inventory extends console_event_response{

    /**
     *
     * 盘点单事件处理
     * @param array $datainStorage
     */
    public function create($data){
       
        $inventoryObj = kernel::single('console_receipt_inventory');
        $result = $inventoryObj->do_inventory($data,$msg);
        $io_source = $data['io_source'];
        if($result){
            if ($io_source == 'selfwms'){
                $inventoryObj->finish_inventory($data['inventory_bn'],$data['branch_bn'],$data['inventory_type'],$data['items']);
                return $this->send_succ('盘点单操作成功');
            }else{
                return $this->send_succ('盘点单操作成功');
            }
        }else{
            return $this->send_error($msg);
        }
        
        
    }

    
}