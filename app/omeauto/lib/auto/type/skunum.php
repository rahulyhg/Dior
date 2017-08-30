<?php

/**
 * 订单产品 (会有时间概念)
 */
class omeauto_auto_type_skunum extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 检查输入的参数
     *
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {
        $type = intval($params['type']);
        if (!in_array($type, array(1,2,3,4))) {

            return "你还没有选择订单商品种类数的范围类型\n\n请选择以后再试！！";
        }

        $minSkuNum = trim($params['min_skunum_'.$type]);
        $maxSkuNum = trim($params['max_skunum_'.$type]);

        if (intval($minSkuNum)<1) {

            return "你还没有输入订单商品种类数的最小值\n\n请正确输入以后再试！！";
        }

        if (intval($maxSkuNum)<1) {

            return "你还没有输入订单商品种类数的最大值\n\n请正确输入以后再试！！";
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
        $minSkuNum = trim($params['min_skunum_'.$type]);
        $maxSkuNum = trim($params['max_skunum_'.$type]);

        $caption = '';
        $role = array('role' => 'skunum', 'caption' => '', 'content'=> array('min' => $minSkuNum, 'max'=>$maxSkuNum , 'type' => $type));
        switch ($type) {
            case '1':
                $role['caption'] = sprintf('订单商品种类数小于 %s 个', $maxSkuNum);
                break;
            case '2':
                $role['caption'] = sprintf('订单商品种类数大于等于 %s 个', $minSkuNum);
                break;
            case '3':
                $role['caption'] = sprintf('订单商品种类数位于 %s 个(包含) - %s 个(不包含) 之间', $minSkuNum, $maxSkuNum);
                break;
            case '4':
                $role['caption'] = sprintf('订单商品种类数等于 %s 个', $minSkuNum);
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
        $skunum = 0;
        //所有合并订单的总金额做计算
        foreach ($item->getOrders() as $order) {
            $skunum += count($order['items']);
        }

        switch($this->content['type']) {
            case 1:
                return ($skunum < $this->content['max'] ? true : false);
                break;
            case 2:
                return ($skunum >= $this->content['min'] ? true : false);
                break;
            case 3:
                return (($skunum >= $this->content['min'] && $skunum < $this->content['max']) ? true : false);
                break;
            case 4:
                return (($skunum == $this->content['min']) ? true : false);
                break;
            default:
                return false;
                break;
        }
    }
}