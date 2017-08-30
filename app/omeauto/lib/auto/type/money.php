<?php
/**
 * 订单金额
 */

class omeauto_auto_type_money  extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {
    
    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {
        
        $type = intval($params['type']);
        if (!in_array($type, array(1,2,3))) {
            
            return "你还没有选择订单金额的范围类型\n\n请选择以后再试！！";
        }
        
        $minMenoy = trim($params['min_menoy_'.$type]);
        $maxMenoy = trim($params['max_menoy_'.$type]);
        
        if (empty($minMenoy) || (intval($minMenoy) != $minMenoy && $type != 1)) {
            
            return "你还没有输入订单金额的最小值\n\n请选择以后再试！！";
        }
        
        if (empty($maxMenoy) || (intval($maxMenoy) != $maxMenoy && $type != 2)) {
            
            return "你还没有输入订单金额的最大值\n\n请选择以后再试！！";
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
        $minMenoy = trim($params['min_menoy_'.$type]);
        $maxMenoy = trim($params['max_menoy_'.$type]);
        
        $caption = '';
        $role = array('role' => 'money', 'caption' => '', 'content'=> array('min' => $minMenoy, 'max'=>$maxMenoy , 'type' => $type));
        switch ($type) {
            case '1':
                $role['caption'] = sprintf('订单金额小于 %s 元', $maxMenoy);
                break;
            case '2':
                $role['caption'] = sprintf('订单金额大于等于 %s 元', $minMenoy);
                break;
            case '3':
                $role['caption'] = sprintf('订单金额位于 %s 元(包含) - %s 元(不包含) 之间', $minMenoy, $maxMenoy);
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
        $money = 0;
        //所有合并订单的总金额做计算
        foreach ($item->getOrders() as $order) {
            $money += $order['total_amount'];
        }

        switch($this->content['type']) {
            case 1:
                return ($money < $this->content['max'] ? true : false);
                break;
            case 2:
                return ($money >= $this->content['min'] ? true : false);
                break;
            case 3:
                return (($money >= $this->content['min'] && $money < $this->content['max']) ? true : false);
                break;
            default:
                return false;
                break;
        }
    }
}