<?php
class siso_receipt_iostock_stockdumpOut extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{
     /**
     *
     * 出入库类型id
     * @var int
     */
    protected $_typeId = 9;

    /**
     *
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 0;
    /**
     *
     * 根据转储出库组织出入库单明细内容
     * @param array data
     */
    function get_io_data($data){
       
        $iostock_data = array();
        $items_detail = $data['items'];
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $create_time = $data['operate_time'] == '' ? $data['operate_time']: time();
        if ($items_detail){
            foreach ($items_detail as $k=>$v){
                $iostock_data[] = array(
                    'branch_id' => $data['branch_id'],
                    'original_bn' => $data['original_bn'],
                    'original_id' => $data['original_id'],
                    'original_item_id' => $v['iso_items_id'],
                    'supplier_id' => '',
                    'supplier_name' =>'',
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price']!='' ? $v['price']: '0',
                    'nums' => $v['nums'],
                    'cost_tax' => '',
                    'oper' => $data['operator'],
                    'create_time' => $create_time,
                    'operator' => $operator,
                   'memo'=>$data['memo'],
  
                );
            }
        }
       

        return $iostock_data;
    }
}