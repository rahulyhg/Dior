<?php
/**
 * 订单余单撤销金额计算商品类型gift
 * 有关订单余单撤销金额计算商品类型gift
 * @author Chris.Zhang
 * @package ome_service_order_object_gift
 * @copyright www.shopex.cn 2011.04.06
 *
 */
class ome_order_remain_gift {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        return 0;
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