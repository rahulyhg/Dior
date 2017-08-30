<?php

/**
 * 订单产品 (会有时间概念)
 */
class omeauto_auto_type_sku extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {

    /**
     * 检查输入的参数
     *
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params) {

        if (empty($params['s_skus'])) {

            return "你没有输入活动商品的SKU\n\n请输入以后再试！！";
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

        if (!empty($params['s_start'])) {
            $sStart = strtotime(sprintf('%s %s:%s:00', $params['s_start'], $params['_DTIME_']['H']['s_start'], $params['_DTIME_']['M']['s_start']));
        } else {
            $sStart = '';
        }

        if (!empty($params['s_end'])) {
            $sEnd = strtotime(sprintf('%s %s:%s:00', $params['s_end'], $params['_DTIME_']['H']['s_end'], $params['_DTIME_']['M']['s_end']));
        } else {
            $sEnd = '';
        }

        if (!empty($sStart) && !empty($sEnd)) {
            $caption = sprintf('在 %s 至 %s 仅有 %s 的订单', date('Y-m-d H:i', $sStart), date('Y-m-d H:i', $sEnd), $params['s_skus']);
        } else if (!empty($sStart) && empty($sEnd)) {
            $caption = sprintf('从 %s 开始仅有 %s 的订单', date('Y-m-d H:i', $sStart), $params['s_skus']);
        } else if (empty($sStart) && !empty($sEnd)) {
            $caption = sprintf('到 %s 为止仅有 %s 的订单', date('Y-m-d H:i', $sEnd), $params['s_skus']);
        } else {
            $caption = sprintf('仅有商品 %s 的订单', $params['s_skus']);
        }

        $role = array('role' => 'sku', 'caption' => $caption, 'content' => array('sku' => $params['s_skus'], 'start' => $sStart, 'end' => $sEnd));

        return json_encode($role);
    }

    /**
     * 设置已经创建好的配置内容
     *
     */
   public function setRole($params) {

       $this->content = $params;
       if (!empty($this->content['sku'])) {

           $this->content['sku'] = explode(',', $this->content['sku']);
           foreach ($this->content['sku'] as $key => $sku) {
               $this->content['sku'][$key] = strtolower(trim($sku));
           }
       }
   }

    /**
     * 检查订单数据是否符合要求
     *
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {
        if (!empty($this->content)) {
            //先检查开始结束时间
            foreach ($item->getOrders() as $order) {

                //检查订单创建时间
                if (intval($this->content['start']) > 0 && $order['createtime'] < intval($this->content['start'])) {
                    return false;
                }

                if(intval($this->content['end']) > 0 && $order['createtime'] > intval($this->content['end'])){
                    return false;
                }

                //检查订单item
                foreach ($order['items'] as $item) {

                    if (!in_array(strtolower($item['bn']), $this->content['sku'])) {
                        return false;
                    }
                }
            }

            return true;
        } else {

            return false;
        }
    }
}