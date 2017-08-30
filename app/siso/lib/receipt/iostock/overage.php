<?php
class siso_receipt_iostock_overage extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{

    /**
     *
     * 出入库类型id
     * @var int
     */
    protected $_typeId = 60;

    /**
     *
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 1;
    /**
     *
     * 根据盘盈组织出入库单明细内容
     * @param int $data 是数据内容
     */
    function get_io_data($data){
        
        $oInventory = &app::get('console')->model('inventory');
        #$inventory = $oInventory->getlist('inventory_bn,branch_bn,inventory_id',array('inventory_id'=>$data['inventory_id']),0,1);
        #$inventory = $inventory[0];
        $branchObj = kernel::single('console_iostockdata');
        $branch= $branchObj->getBranchBybn($data['branch_bn']);
        $create_time = $data['operate_time'] == '' ? time(): $data['operate_time'];
        $items = $data['items'];
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        foreach ($items as $k=>$item){
                $iostock_data[] = array(
                    'branch_id' => $branch['branch_id'],
                    'original_bn' => $data['inventory_bn'],
                    'original_id' => $data['inventory_id'],
                    'original_item_id' => $item['item_id'],
                    'supplier_id' => 0,
                    'bn' => $item['bn'],
                    'iostock_price' => $item['price']!='' ? $item['price']: '0',
                    'nums' => $item['normal_num'],
                    'oper' => $operator,
                    'create_time' => $create_time,
                    'operator' => $operator,
                    'memo' => $data['memo'],
                );
            }
       return $iostock_data;
    }
}