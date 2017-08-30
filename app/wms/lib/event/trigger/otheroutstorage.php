<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wms_event_trigger_otheroutstorage extends wms_event_trigger_stockoutabstract{

    function getStockOutData($data){
        $iso_id = $data['iso_id'];
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        $oIsoItems = &app::get('taoguaniostockorder')->model("iso_items");
        $Iso = $oIso->dump(array('iso_id'=>$iso_id),'iso_bn,branch_id,type_id,memo');
        $oSupplier = &app::get('purchase')->model("supplier");
        $iostockdataObj = kernel::single('wms_iostockdata');
        $oBranch = &app::get('ome')->model("branch");
        $supplier = $oSupplier->supplier_detail($Iso['supplier_id'],'bn');
        $branch = $iostockdataObj->getBranchByid($Iso['branch_id']);
        $iso_items = $oIsoItems->getList('product_id,bn,product_name,normal_num,defective_num,nums',array('iso_id'=>$iso_id));
        $data = array();
        $type_id = $Iso['type_id'];
        if ($type_id=='4' || $type_id=='40'){//调拨出入库
            $io_type = 'ALLCOATE';
        }else{//其他入库
            $io_type = 'OTHER';
        }
        
        $data['io_type'] = $io_type;//类型
        $data['io_bn'] = $Iso['iso_bn'];//类型
        $data['branch_id'] = $Iso['branch_id'];
        $data['io_source'] = 'selfwms';//来源
        $data['io_status'] = 'FINISH';
        $data['branch_bn'] = $branch['branch_bn'];
        $data['supplier_bn'] = $supplier['bn'];
        $data['memo'] = $Iso['memo'];
        foreach($iso_items as $ik=>$iv){
            $iso_items[$ik]['normal_num'] = $iv['nums'];#接收处区分
            $iso_items[$ik]['num'] = $iv['nums'];#
        }
        $data['items'] = $iso_items;
        
        return $data;
    } 
    
}

?>
