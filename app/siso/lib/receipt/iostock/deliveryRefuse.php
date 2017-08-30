<?php
class siso_receipt_iostock_deliveryRefuse implements siso_receipt_iostock_interface{

    /**
     *
     * 售后拒收退货入库组织出入库单明细内容
     * @param 
     */
    function get_io_data($reship_id){
        $reshipObj = app::get('ome')->model('reship');
        $reship_items = $reshipObj->getItemList($reship_id);
        $reshipInfo = $reshipObj->dump($reship_id,'reship_bn');
        $iostock_data = array();
        if ($reship_items){
            foreach ($reship_items as $k=>$v){
                $iostock_data[$v['item_id']] = array(
                    'branch_id' => $v['branch_id'],
                    'original_bn' => $reshipInfo['reship_bn'],
                    'original_id' => $reship_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => 0.000,
                    'nums' => $v['num'],
                    'cost_tax' => 0,
                    'oper' => $op_name,
                    'create_time' => time(),
                    'operator' => $op_name,
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
}