<?php
class siso_receipt_iostock_purchaseReturn extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{

    /**
     *
     * 出入库类型id
     * @var int
     */
    protected $_typeId = 10;

    /**
     *
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 0;

    /**
     *
     * 根据出入库单组织明细内容
     * @data array
     */
    function get_io_data($data){
        $iso_id = $data['iso_id'];
        $objIsoItems = &app::get('taoguaniostockorder')->model('iso_items');

        $iostock_data = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguaniostockorder_iso` WHERE `iso_id`=\''.$iso_id.'\'';
        $iso_detail = $db->selectrow($sql);
        if ($data['items']){
            $iso_items_detail = $data['items'];
        }else{
            $iso_items_detail = $objIsoItems->getList('*', array('iso_id'=>$iso_id), 0, -1);
        }
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $create_time = $data['operate_time'] == '' ? $iso_detail['create_time']: $data['operate_time'];
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
                    'iostock_price' => $v['price']!='' ? $v['price']: '0',
                    'nums' => $v['nums'],
                    'cost_tax' => $iso_detail['cost_tax'],
                    'oper' => $iso_detail['oper'],
                    'create_time' => $iso_detail['create_time'],
                    'operator' => $operator,
                    'settle_method' => $iso_detail['settle_method'],
                    'settle_status' => $iso_detail['settle_status'],
                    'settle_operator' => $iso_detail['settle_operator'],
                    'settle_time' => $iso_detail['settle_time'],
                    'settle_num' => $iso_detail['settle_num'],
                    'settlement_bn' => $iso_detail['settlement_bn'],
                    'settlement_money' => $iso_detail['settlement_money'],
                    'memo' => $data['memo'],
                );
            }
        }
        

        return $iostock_data;
    }
}