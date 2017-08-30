<?php
/**
* 所有单据公用方法
*/
class console_service_commonstock{

    function iscancel($io_bn){
        $io_bn = $io_bn;
        $prefix = substr( $io_bn, 0, 1 );
        $type_id = $this->get_stocktype($prefix);
        switch($type_id){
            case '1':#采购
                $result = kernel::single('console_receipt_purchase')->checkExist($io_bn);
                if ($result['po_status'] == '2'){
                    return true;
                }else{
                    return false;
                }
                break;
            case '10':#采购退货
                $result = kernel::single('console_receipt_purchasereturn')->checkExist($io_bn);
                if ($result['return_status'] == '3'){
                    return true;
                }else{
                    return false;
                }
                break;
            case '30':#退货入库
                break;
            case '31':#换货入库
                break;
            case '4':#调拨入库
                //break;
            case '40':#调拨出库
                //break;
            case '5':#残损出库
                //break;#
            case '50':#残损入库
            case '7':#直接出库
                
            case '70':#直接入库
               
            case '100':#赠品出库
                
            case '200':#赠品入库
                
            case '300':#样品出库
            
            case '400':#样品入库
                $result = kernel::single('console_receipt_stock')->checkExist($io_bn);
                if ($result['iso_status'] == '4'){
                    return true;
                }else{
                    return false;
                }
            break;
            case '500':#转储出库
            
            case '600':#转储入库
                $result = kernel::single('console_receipt_stockdump')->checkExist($io_bn);
                if ($result['self_status'] == '0'){
                    return true;
                }else{
                    return false;
                }
            case '6':#盘亏
                break;
            case '60':#盘盈
                break;
        }
        
    }

    function get_stocktype($prefix){
         #根据前缀区分类型
        $iostock_types = kernel::single('ome_iostock')->iostock_types;
       
        foreach($iostock_types as $ik=>$iv){
           
            if ($iv['code'] == $prefix){
                
                $type_id = $ik;
                break;
            }
        }
        return $type_id;
    }

}

?>