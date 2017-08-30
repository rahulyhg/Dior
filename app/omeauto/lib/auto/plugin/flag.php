<?php

/**
 * 检查备注和旗标
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */
class omeauto_auto_plugin_flag extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = true;
    
    /** 
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__FLAG_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group , &$confirmRoles) {

        $allow = true;
        $fields = $this->_getCheckField($confirmRoles);
        
        $mark = kernel::single('omeauto_auto_group_mark');
        foreach ($group->getOrders() as $order) {

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
    }

    /**
     * 获取配置信息
     *
     * @param void
     * @return array
     */
    private function _getCheckField($configRoles) {

        $result = array();
        if ($configRoles['memo'] == '1') {

            $result[] = 'custom_mark';
        }

        if ($configRoles['mark'] == '1') {

            $result[] = 'mark_text';
        }

        return $result;
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '有备注订单';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        $html = '';
        if ($order['custom_mark']) {

            $html .= '<b>客户留言：</b><br />';
            $custom_mark = kernel::single('ome_func')->format_memo($order['custom_mark']);
            if (!empty($custom_mark)) {
                foreach ((array) $custom_mark as $k => $v) {
                    $html .= $v['op_content'] . "<br /><br/>";
                }
            } else {

                $html .= $order['custom_mark'] . "<br /><br/>";
            }
        }
        if ($order['mark_text']) {
            $html .= '<b>客服留言：</b><br />';
            $mark_text = kernel::single('ome_func')->format_memo($order['mark_text']);
            if (!empty($mark_text)) {
                foreach ((array) $mark_text as $k => $v) {
                    $html .= $v['op_content'] . "<br />";
                }
            } else {

                $html .= $order['mark_text'] . "<br />";
            }
        }
        
        $html = strip_tags(htmlspecialchars($html));
        
        return array('color' => '#666666', 'flag' => '备', 'msg' => $html);
    }
    
    /**
     * 获取用于快速审核的选项页，输出HTML代码
     * 
     * @param void
     * @return String
     */
    public function getInputUI() {
        
    }
}