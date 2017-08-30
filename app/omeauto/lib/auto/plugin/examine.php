<?php

/**
 * 增加单订单标识 
 */
class omeauto_auto_plugin_examine extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {
    
    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = true;
    
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__EXAMINE_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(&$group, &$confirmRoles) {
        /*$allow = true;
        $orders = $group->getOrders();
        $fields = $this->_getCheckField($confirmRoles);
        $mark = kernel::single('omeauto_auto_group_mark');

        if (count($orders) <= 1) {
            foreach ((array) $orders as $key => $order) {
                //检查标记是否已经确认
                $markText = $this->getMark($order['mark_text']);
                $customText = $this->getMark($order['custom_mark']);
                if ($mark->isConfirm($markText, $customText)) {
                    //如需检查客户留言，订单可过，但打上有备注的标记
                    if (in_array('custom_mark', $fields)) {
                        if ($customText) {
                            $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                        }
                    }
                } else {
                    //备注
                    foreach ($fields as $field) {
                        if ($order[$field]) {
                            $allow = false;
                            $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                        }
                    }
                }
            }

            if (!$allow) {
                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
            }
        }*/
    }

    /**
     * 获取配置信息
     *
     * @param void
     * @return array
     */
    private function _getCheckField($configRoles) {
        $result = array();
        if ($configRoles['mark'] == '1') {
            $result[] = 'custom_mark';
        }

        if ($configRoles['memo'] == '1') {
            $result[] = 'mark_text';
        }

        return $result;
    }
    
    public function getTitle() {
        return '批量订单';
    }
    
    public function getAlertMsg(& $order) {
        return array ('color' => 'BLUE', 'flag' => '批', 'msg' => '单个有备注的订单，可以批量审单' );
    }

}