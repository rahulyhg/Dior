<?php

/**
 * 来源店铺
 */
class omeauto_auto_type_shop extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 在显示前为模板做一些数据准备工作
     * 
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(& $tpl) {

        $shop = array();
        $rows = app::get('ome')->model('shop')->getList("shop_id,name", array(), 0, -1);
        if ($rows) {
            foreach ($rows as $v) {
                $shop[] = array('id' => $v['shop_id'], 'caption' => $v['name']);
            }
        }
        $tpl->pagedata['shops'] = $shop;
    }

    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {

        if (empty($params['shop']) && !is_array($params['shop'])) {

            return "你还没有选择相应的前端店铺\n\n请勾选以后再试！！";
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

        $rows = app::get('ome')->model('shop')->getList('name', array('shop_id' => $params['shop']));

        $caption = '';
        foreach ($rows as $row) {

            $caption .= ", " . $row['name'];
        }
        $caption = sprintf('来自店铺 %s', preg_replace('/^,/is', '', $caption));

        $role = array('role' => 'shop', 'caption' => $caption, 'content' => $params['shop']);

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
                if (!in_array($order['shop_id'], $this->content)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

}