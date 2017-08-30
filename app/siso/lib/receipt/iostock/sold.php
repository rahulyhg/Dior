<?php
class siso_receipt_iostock_sold extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{

    /**
     *
     * 出入库类型id
     * @var int
     */
    protected $_typeId = 3;

    /**
     *
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 0;

    /**
     *
     * 根据发货单组织销售明细内容
     * @param array $params
     */
    public function get_io_data($params){

        $delivery_items_detailObj = &app::get('ome')->model('delivery_items_detail');
        $delivery_id = $params['delivery_id'];

        //发货单信息
        $sql = 'SELECT `branch_id`,`delivery_bn`,`op_name`,`delivery_time`,`is_cod` FROM `sdb_ome_delivery` WHERE `delivery_id`=\''.$delivery_id.'\'';
        $delivery_detail = $delivery_items_detailObj->db->selectrow($sql);
        $delivery_items_detail = $delivery_items_detailObj->getList('*', array('delivery_id'=>$delivery_id), 0, -1);

        $iostock_data = array();
        if ($delivery_items_detail){
            foreach ($delivery_items_detail as $k=>$v){
                $iostock_data[$v['item_detail_id']] = array(
                    'order_id' => $v['order_id'],
                    'branch_id' => $delivery_detail['branch_id'],
                    'original_bn' => $delivery_detail['delivery_bn'],
                    'original_id' => $delivery_id,
                    'original_item_id' => $v['item_detail_id'],
                    'supplier_id' => '',
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['number'],
                    'cost_tax' => '',
                    'oper' => $delivery_detail['op_name'],
                    'create_time' => $delivery_detail['delivery_time'],
                    'operator' => $delivery_detail['op_name'],
                    'settle_method' => '',
                    'settle_status' => '0',
                    'settle_operator' => '',
                    'settle_time' => '',
                    'settle_num' => '',
                    'settlement_bn' => '',
                    'settlement_money' => '0',
                    'memo' => '',
                );
            }
        }
        unset($delivery_detail,$delivery_items_detail);
        return $iostock_data;
    }
}