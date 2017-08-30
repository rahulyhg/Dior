<?php
class siso_receipt_iostock_aftersaleReturn implements siso_receipt_iostock_interface{

    /**
     *
     * 根据退货入库组织出入库单明细内容
     * @param int $delivery_id
     */
    function get_io_data($pro_id){
        $processObj = &app::get('ome')->model('return_process');
        $process_itemsObj = &app::get('ome')->model('return_process_items');
        $pro_items = $process_itemsObj->getList('*', array('por_id'=>$pro_id), 0, -1);
        $process = $processObj->dump($pro_id,'*');
        $iostock_data = array();
        foreach ($pro_items as $k=>$v) {
            $iostock_data[$v['item_id']] = array(
                    'branch_id' => $v['branch_id'],
                    'original_bn' => '',
                    'original_id' => $pro_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $v['need_money'],
                    'nums' => $v['num'],
                    'cost_tax' => 0,
                    'oper' => kernel::single('desktop_user')->get_name(),
                    'create_time' => $process['add_time'],
                    'operator' => kernel::single('desktop_user')->get_name(),
                    'settle_method' => '',
                    'settle_status' => '0',
                    'settle_operator' => '',
                    'settle_time' => '',
                    'settle_num' => '',
                    'settlement_bn' => '',
                    'settlement_money' => '',
                    
                );
        }
    }
}