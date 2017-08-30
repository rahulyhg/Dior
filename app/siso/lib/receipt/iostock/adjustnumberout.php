<?php
class siso_receipt_iostock_adjustnumberout extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{
    /**
     *
     * 出入库类型id
     * @var int
     */
    protected $_typeId = 80;

    /**
     *
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 0;
    /**
     *
     * 根据直接入库出入库单组织明细内容
     * @param array $data
     */
    function get_io_data($data){
        $iostock_data = array();
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $create_time = time();
        if ($data){
            foreach ($data as $k=>$v){
                $iostock_data[] = array(
                    'branch_id' => $v['branch_id'],
                    'original_bn' => '',
                    'original_id' => '',
                    'original_item_id' => '',
                    'supplier_id' => '',
                    'supplier_name' => '',
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price']!='' ? $v['price']: '0',
                    'nums' => $v['nums'],
                    'cost_tax' => '',
                    'oper' => $operator,
                    'create_time' => $create_time,
                    'operator' => $operator,
                    'memo'=>$v['memo'],
  
                );
            }
        }
        
        return $iostock_data;
    }
}