<?php
/* 
 * 检查是否有未对应的产品
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

class omeauto_auto_plugin_product extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__PRODUCT_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {

        $allow = true;
        foreach ($group->getOrders() as $order) {

            //检查是否有匹配不上的产品
            foreach ($order['items'] as $product) {

                if ($product['bn'] == '' || empty($product['bn'])) {

                    $allow = false;
                    $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                }
            }
        } 

        if (!$allow) {

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

        return '有未匹配产品';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => 'GREEN', 'flag' => '货', 'msg' => '订单有产品未和商品库对应，需进行手工对应');
    }
}