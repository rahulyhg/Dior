<?php
/**
 * 订单余单撤销金额计算商品类型giftpackage
 * 有关订单余单撤销金额计算商品类型giftpackage
 * @author Chris.Zhang
 * @package ome_service_order_object_giftpackage
 * @copyright www.shopex.cn 2011.04.06
 *
 */
class ome_order_remain_giftpackage {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        $amount = 0;
        if ($obj['order_items']){
            foreach ($obj['order_items'] as $item){
                if ($item['delete'] == 'true') return 0;
                break;
            }
            $leave = ($item['quantity']-$item['sendnum'])*($obj['quantity']/$item['quantity']);
            $amount = $obj['price']*$leave;
        }
        return $amount;
    }
    
    /*
     * 余单撤销处理
     */
    public function remain_cancel($obj){
        if ($obj) {
            $delete = false;
            foreach ($obj['order_items'] as $item){
                if ($item['sendnum'] == 0){
                    $delete = true;
                    break;
                }else {
                    $num = $obj['quantity'] / $item['quantity'] * $item['sendnum'];
                }
            }
            if ($delete == true){
                $sql = "UPDATE `sdb_ome_order_items` SET `delete`='true' WHERE `obj_id`='".$obj['obj_id']."'";//"' AND `sendnum`<`nums` AND `sendnum` = '0' ";
                kernel::database()->exec($sql);
            }else {
                $sql = "UPDATE `sdb_ome_order_items` SET `nums`=`sendnum` WHERE `obj_id`='".$obj['obj_id']."' AND `sendnum`<`nums` AND `sendnum` <> '0' ";
                kernel::database()->exec($sql);
                $sql = "UPDATE `sdb_ome_order_objects` SET `quantity`=".$num." WHERE `obj_id`='".$obj['obj_id']."'";
                kernel::database()->exec($sql);
            }
            return true;
        }
        return false;
    }
    
}