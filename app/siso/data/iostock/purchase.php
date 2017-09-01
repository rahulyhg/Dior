<?php
class siso_data_iostock_purchase {

    /**
    *
    * 采购单数据
    */
    public function get_iostock_data($iso_id,&$type_id,$start=0,$limit=0){
        $objIsoItems = &app::get('taoguaniostockorder')->model('iso_items');

        $iostock_data = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguaniostockorder_iso` WHERE `iso_id`=\''.$iso_id.'\'';
        $iso_detail = $db->selectrow($sql);
        $iso_items_detail = $objIsoItems->getList('*', array('iso_id'=>$iso_id), 0, -1);
        if ($iso_items_detail){
            foreach ($iso_items_detail as $k=>$v){
                $iostock_data[$v['iso_items_id']] = array(
                    'branch_id' => $iso_detail['branch_id'],
                    'original_bn' => $iso_detail['iso_bn'],
                    'original_id' => $iso_id,
                    'original_item_id' => $v['iso_items_id'],
                    'supplier_id' => $iso_detail['supplier_id'],
                    'supplier_name' => $iso_detail['supplier_name'],
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['nums'],
                    'cost_tax' => $iso_detail['cost_tax'],
                    'oper' => $iso_detail['oper'],
                    'create_time' => $iso_detail['create_time'],
                    'operator' => $iso_detail['operator'],
                    'settle_method' => $iso_detail['settle_method'],
                    'settle_status' => $iso_detail['settle_status'],
                    'settle_operator' => $iso_detail['settle_operator'],
                    'settle_time' => $iso_detail['settle_time'],
                    'settle_num' => $iso_detail['settle_num'],
                    'settlement_bn' => $iso_detail['settlement_bn'],
                    'settlement_money' => $iso_detail['settlement_money'],
                    'memo' => $iso_detail['memo'],
                );
            }
        }
        $type = $iso_detail['type_id'];

        return $iostock_data;
    }
}



?>