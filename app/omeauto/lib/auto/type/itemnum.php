<?php

/**
 * 订单产品 (会有时间概念)
 */
class omeauto_auto_type_itemnum extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 检查输入的参数
     *
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {
        $type = intval($params['type']);
        if (!in_array($type, array(1,2,3,4))) {

            return "你还没有选择订单商品数的范围类型\n\n请选择以后再试！！";
        }

        $minItemNum = trim($params['min_itemnum_'.$type]);
        $maxItemNum = trim($params['max_itemnum_'.$type]);

        if (intval($minItemNum)<1) {

            return "你还没有输入订单商品数的最小值\n\n请正确输入以后再试！！";
        }

        if (intval($maxItemNum)<1) {

            return "你还没有输入订单商品数的最大值\n\n请正确输入以后再试！！";
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

        $type = intval($params['type']);
        $minItemNum = trim($params['min_itemnum_'.$type]);
        $maxItemNum = trim($params['max_itemnum_'.$type]);

        $caption = '';
        $role = array('role' => 'itemnum', 'caption' => '', 'content'=> array('min' => $minItemNum, 'max'=>$maxItemNum , 'type' => $type));
        switch ($type) {
            case '1':
                $role['caption'] = sprintf('订单商品数小于 %s 个', $maxItemNum);
                break;
            case '2':
                $role['caption'] = sprintf('订单商品数大于等于 %s 个', $minItemNum);
                break;
            case '3':
                $role['caption'] = sprintf('订单商品数位于 %s 个(包含) - %s 个(不包含) 之间', $minItemNum, $maxItemNum);
                break;
            case '4':
                $role['caption'] = sprintf('订单商品数等于 %s 个', $minItemNum);
                break;
        }

        return json_encode($role);
    }

    /**
     * 检查订单数据是否符合要求
     *
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {
        $itemnum = 0;
        //所有合并订单的总金额做计算
        foreach ($item->getOrders() as $order) {
            foreach($order['items'] as $value){
                $itemnum += $value['nums'];
            }
        }

        switch($this->content['type']) {
            case 1:
                return ($itemnum < $this->content['max'] ? true : false);
                break;
            case 2:
                return ($itemnum >= $this->content['min'] ? true : false);
                break;
            case 3:
                return (($itemnum >= $this->content['min'] && $money < $this->content['max']) ? true : false);
                break;
            case 4:
                return (($itemnum == $this->content['min']) ? true : false);
                break;
            default:
                return false;
                break;
        }
    }
}