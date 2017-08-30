<?php
/**
 * 订单余单撤销金额计算商品类型goods
 * 有关订单余单撤销金额计算商品类型goods
 * @author Chris.Zhang
 * @package ome_service_order_object_goods
 * @copyright www.shopex.cn 2011.04.06
 *
 */
class ome_order_remain_goods {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        $amount = 0;
        if ($obj['order_items']){
            foreach ($obj['order_items'] as $item){
                if ($item['delete'] == 'true') continue;
                $amount += ($item['quantity']-$item['sendnum'])*$item['price'];
            }
        }
        return $amount;
    }
    
    /*
     * 余单撤销处理
     */
    public function remain_cancel($obj){
        if ($obj){
            $sql = "UPDATE `sdb_ome_order_items` SET `nums`=`sendnum` WHERE `obj_id`='".$obj['obj_id']."' AND `sendnum`<`nums` AND `sendnum` <> '0' ";
            kernel::database()->exec($sql);
            $sql = "UPDATE `sdb_ome_order_items` SET `delete`='true' WHERE `obj_id`='".$obj['obj_id']."' AND `sendnum`<`nums` AND `sendnum` = '0' ";
            kernel::database()->exec($sql);
        }
        return true;
    }
    
}