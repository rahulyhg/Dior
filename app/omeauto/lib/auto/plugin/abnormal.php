<?php
/**
 * 一些订单属性的简单检查
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

class omeauto_auto_plugin_abnormal extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__ABNORMAL_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(&$group, &$confirmRoles) {
        $allow = true;
        foreach ((array)$group->getOrders() as $order) {
            if(!$order['order_combine_hash'] || !$order['order_combine_idx'] || $order['pause']=='true' || $order['abnormal']=='true'){
                $allow = false;
                $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
            }
        }
        if(!$allow){
            $group->setStatus(omeauto_auto_group_item::__OPT_ALERT, $this->_getPlugName());
        }
    }

     /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '异常订单';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => 'RED', 'flag' => '异' ,'msg' => '数据有异常的订单，如异常、暂停');
    }
}