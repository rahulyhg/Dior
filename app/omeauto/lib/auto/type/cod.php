<?php

/**
 * 货到付款
 */
class omeauto_auto_type_cod extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {

        if (!$params['is_cod'] || $params['is_cod']=='') {

            return "你还没有选择相应的订单类型\n\n请选择以后再试！！";
        }

        return true;
    }

    /**
     * 生成规则字串
     * 
     * @param Array $params
     * @return String
     */
    public function roleToString($params) {
        if($params['is_cod'] && $params['is_cod']=='true'){
            $caption = '货到付款订单';
        }else{
            $caption = '款到发货订单';
        }

        $role = array('role' => 'cod', 'caption' => $caption, 'content' => $params['is_cod']);

        return json_encode($role);
    }

    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {

        if (!empty($this->content)) {

            foreach ($item->getOrders() as $order) {
                //检查店铺
                if ($order['is_cod'] != $this->content) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

}