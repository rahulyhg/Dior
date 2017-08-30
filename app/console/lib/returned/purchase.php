<?php
class console_returned_purchase{

    /**
    * 采购退货单详情
    *
    * return $array
    */
    function detail($rp_id){
        $returned_purchaseObj = &app::get('purchase')->model('returned_purchase');
        $branchObj = &app::get('ome')->model('branch');
        $supplierObj = &app::get('purchase')->model('supplier');

        $data = $returned_purchaseObj->dump($rp_id,'*',array('returned_purchase_items' =>array("*")));
        $data['branch_name'] = $branchObj->Get_name($data['branch_id']);
        $supplier = $supplierObj->supplier_detail($data['supplier_id'],'name');
        $data['supplier_name'] = $supplier['name'];
        $data['memo'] = unserialize(($data['memo']));
        return $data;
    }

    /**
    * 更新采购退货单状态
    */
    function update_status($check_status,$rp_id){
        $db = kernel::database();
        $sql = 'UPDATE sdb_purchase_returned_purchase SET check_status='.$check_status.' WHERE rp_id='.$rp_id;
        $result = $db->exec($sql);
        return $result;
    }
}


?>