<?php
class siso_receipt_iostock_reship extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{
    /**
     *
     * 出入库类型id
     * @var int
     */
    protected $_typeId = 30;

    /**
     *
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 1;
    /**
     *
     * 退货入库
     * @param array $data
     */
    function get_io_data($data){
        $oReship = &app::get('ome')->model('reship');
        $oReship_items = &app::get('ome')->model('reship_items');
        $reship_id = $data['reship_id'];
        $reship = $oReship->dump($reship_id,'reship_bn,t_end,order_id');
        $reship_items = $oReship_items->getlist('*',array('reship_id'=>$reship_id,'return_type'=>array('return','refuse'),'normal_num|than'=>0),0,-1);
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $iostock_data = array();
        if ($reship_items) {
            foreach($reship_items as $k=>$v){
                $iostock_data[] = array(
                    'branch_id' => $v['branch_id'],
                    'original_bn' => $reship['reship_bn'],
                    'original_id' => $reship_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => '',
                    'supplier_name' => '',
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price']!='' ? $v['price']: '0',
                    'nums' => $v['normal_num'],
                    'cost_tax' => '',
                    'oper' => $operator,
                    'create_time' => $reship['t_end'],
                    'operator' => $operator,
                   'order_id'=>$reship['order_id'],
                   # 'memo' => $data['memo'],
                );
            }
        }
        
        
        return $iostock_data;
    }
}