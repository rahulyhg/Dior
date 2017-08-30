<?php
class ome_freeze_stock_log{

    public function changeLog($data){
        $tmpdata = array(
                'log_type'=>$data['log_type'],
                'mark_no'=>$data['mark_no'],
                'shop_id'=> $GLOBALS['frst_shop_id'] ? $GLOBALS['frst_shop_id'] : '',
                'shop_type'=>$GLOBALS['frst_shop_type'] ? $GLOBALS['frst_shop_type'] : '',
                'order_bn'=>$GLOBALS['frst_order_bn'] ? $GLOBALS['frst_order_bn'] : '',
                'delivery_bn'=>$GLOBALS['frst_delivery_bn'] ? $GLOBALS['frst_delivery_bn'] : '',
                'oper_id'=>defined('FRST_OPER_ID') ? FRST_OPER_ID : kernel::single('desktop_user')->get_id(),
                'oper_name'=>defined('FRST_OPER_NAME') ? FRST_OPER_NAME : (kernel::single('desktop_user')->get_name() ? kernel::single('desktop_user')->get_name() : 'system'),
                'oper_time'=>$data['oper_time'],
                'trigger_object_type'=>defined('FRST_TRIGGER_OBJECT_TYPE') ? FRST_TRIGGER_OBJECT_TYPE : '',
                'trigger_action_type'=>defined('FRST_TRIGGER_ACTION_TYPE') ? FRST_TRIGGER_ACTION_TYPE : '',
                'branch_id'=>$data['branch_id'] ? $data['branch_id'] : '',
                'branch_name'=>$data['branch_name'] ? $data['branch_name'] : '',
                'product_id'=>$data['product_id'],
                'goods_id'=>$data['goods_id'] ? $data['goods_id'] : '',
                'bn'=>$data['bn'] ? $data['bn'] : '',
                'stock_action_type'=>$data['stock_action_type'],
                'last_num'=>$data['last_num'] ? $data['last_num'] : '0',
                'change_num'=>$data['change_num'],
                'current_num'=>$data['current_num'] ? $data['current_num'] : '0',
        );
        if(isset($GLOBALS['frst_domain']) && in_array($_SERVER['SERVER_NAME'],$GLOBALS['frst_domain'])){
            app::get('ome')->model('freeze_stock_log')->save($tmpdata);
        }
    }
}