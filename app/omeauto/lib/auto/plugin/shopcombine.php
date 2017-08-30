<?php

/**
 * 检查是否需要检查同一店铺同一用户购买订单
 *
 * @author sunjing@shopex.cn
 * @version 0.1
 */
class omeauto_auto_plugin_shopcombine extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__COMBINE_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {
        $allow = true;
        if($this->_checkStatus($confirmRoles)){
            $orders = $group->getOrders();
            if (!empty($orders) && is_array($orders)) {
                $key = key($orders);
                $member_id = $orders[$key]['member_id'];
                $shop_id = $orders[$key]['shop_id'];
                $data = array();
                $data['ship_name'] = $orders[$key]['ship_name'];
                $data['ship_mobile'] = $orders[$key]['ship_mobile'];
                $data['ship_area'] = $orders[$key]['ship_area'];
                $data['ship_addr'] = $orders[$key]['ship_addr'];
                $data['is_cod'] = $orders[$key]['is_cod'];
                $data['shop_type'] = $orders[$key]['shop_type'];
                $data['shop_id'] = $shop_id;
                $data['member_id'] = $member_id;
                $count = kernel::single('omeauto_auto_combine')->getCombineShopMemberCount($data);
                unset($data);
                
                if ($count>count($orders)) {
                    $allow = false;
                    
                    foreach ($orders as $order) {
                        $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                    }
                }
            }
            if(!$allow){

                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
            }
        }
    }

    /**
     * 检查是否启用检查
     */
    private function _checkStatus($configRoles) {
        return true;
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {
       return '可合并订单';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {
        return array('color' => 'RED', 'flag' => '疑', 'msg' => '疑与其它订单可合并');
    }

    
}
