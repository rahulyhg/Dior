<?php
class ome_goods_serial{
    /**
     * 唯一码状态
     * @access public
     * @return Array
     */
    static function serial_status(){
        $serial_status = array (
            '0' => '入库',
            '1' => '出库',
            '2' => '无效',
        );
        return $serial_status;
    }

    /**
     * 操作类型
     * @access public
     * @return Array
     */
    static function act_type(){
        $act_type = array (
            '0' => '出库效验',
            '1' => '入库效验',
        );
        return $act_type;
    }

    /**
     * 单据类型
     * @access public
     * @return Array
     */
    static function bill_type(){
        $bill_type = array (
            '0' => '发货单',
            '1' => '退货单',
        );
        return $bill_type;
    }
}