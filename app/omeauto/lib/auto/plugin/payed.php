<?php
/**
 * 订单付款属性的简单检查
 *
 * @author ExBOY
 * @version 0.1b
 */

class omeauto_auto_plugin_payed extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__PAY_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {

        $orderNum = $group->getOrderNum();
        $groupPayStatus = $group->getGroupByField('pay_status');
        $codStatus = $group->getGroupByField('is_cod');

        //检查有无未支付订单[货到付款_支持自动审单 ExBOY]
        if (isset($codStatus['true'])) {// && $confirmRoles['autoCod'] == '1'
 
            return;
        } 

        if ((isset($groupPayStatus[0]) || isset($groupPayStatus[2]) || isset($groupPayStatus[3]))) {

            //设置订单状态
            foreach ($groupPayStatus as $stataus => $orderIds) {

                if ($stataus <> 0 || $stataus <> 2 || $stataus <> 3) {

                    foreach ($orderIds as $orderId) {

                        $group->setOrderStatus($orderId, $this->getMsgFlag());
                    }
                }
            }

            if (count($groupPayStatus) <= 1) { 
                //多是未支付
                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
            } else {
                //有未支付
                $group->setStatus(omeauto_auto_group_item::__OPT_ALERT, $this->_getPlugName());
            }
        }
    }

     /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '有未付订单';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => 'RED', 'flag' => '款' ,'msg' => '有可合并的未支付订单');
    }
}