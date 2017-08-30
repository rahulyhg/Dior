<?php
class console_event_trigger_purchasereturn extends console_event_trigger_stockoutabstract{

    
    protected $_io_type = 'PURCHASE_RETURN';

    /**
    *
    */
    function getStockOutParam($param){
        $rp_id = $param['rp_id'];
        $returned_purchaseObj = &app::get('purchase')->model('returned_purchase');
        $purchaseReturn = $returned_purchaseObj->dump($rp_id, '*', array('returned_purchase_items'=>array('product_id,num,bn,name,price')));

        $corp_id = $purchaseReturn['corp_id'];
        $branch_id = $purchaseReturn['branch_id'];
        $branch_detail = $this->getBranchByid($branch_id);
        $memo = $purchaseReturn['memo'];
        $supplier_id = $purchaseReturn['supplier_id'];
       
        
        $tmp = array(
            'io_type'=> 'PURCHASE_RETURN',
            'io_bn'=> $purchaseReturn['rp_bn'],
            'branch_bn'=> $branch_detail['branch_bn'],
            'storage_code'=> $branch_detail['storage_code'],
            'create_time'=> $purchaseReturn['returned_time'],
            'total_goods_fee'=> $purchaseReturn['amount'],
            'branch_id'=>$branch_id,
            
            'supplier_id'=>$supplier_id,
            );
        if ($purchaseReturn['corp_id']) {
            $corp_id = $purchaseReturn['corp_id'];
            $oDly_corp = app::get('ome')->model('dly_corp');
            $dly_corp = $oDly_corp->dump($corp_id,'type');
            $tmp['logi_code'] = $dly_corp['type'];
        }
   
        if ($memo){
            $memo = unserialize($memo);
            if($memo){
                $memo = array_pop($memo);
                $tmp['memo'] = $memo['op_content'];
            }
            
        }
        $tmp['items'] = $purchaseReturn['returned_purchase_items'];

        return $tmp;
    }

    
    /**
     * 更新外部单编号
     * @param   
     * @return  
     * @access  protected
     * @author cyyr24@sina.cn
     */
    protected function update_out_bn($io_bn,$out_iso_bn)
    {
        $returned_purchaseObj = &app::get('purchase')->model('returned_purchase');
        $data = array(
            'out_iso_bn'=>$out_iso_bn
        );
        $returned_purchaseObj->update($data,array('rp_bn'=>$io_bn));
    }
}