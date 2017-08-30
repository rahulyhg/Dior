<?php
/**
 * 发货单的扩展字段买家留言
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class ome_extracolumn_delivery_custommark extends ome_extracolumn_abstract implements ome_extracolumn_interface{

    protected $__pkey = 'delivery_id';

    protected $__extra_column = 'column_custom_mark';

    /**
     *
     * 获取发货单相关订单的买家留言
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $deliveryObj = &app::get('ome')->model('delivery');
        $customMark_lists = $deliveryObj->db->select('select custom_mark,'.$this->__pkey.' from sdb_ome_orders orders join sdb_ome_delivery_order  on orders.order_id=sdb_ome_delivery_order.order_id and sdb_ome_delivery_order.delivery_id in ('.implode(',',$ids).')');

        $tmp_array= array();
        foreach($customMark_lists as $k=>$row){
            $custom_mark = '';#买家留言
            $custom = kernel::single('ome_func')->format_memo($row['custom_mark']);
             if($custom){
                 // 取最后一条
                 $custom = array_pop($custom);
                 $custom_mark = $custom['op_content'].'；'."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp";
             }

             if(isset($tmp_array[$row[$this->__pkey]])){
                $tmp_array[$row[$this->__pkey]] .= $custom_mark;
             }else{
                $tmp_array[$row[$this->__pkey]] = $custom_mark;
             }
        }
        return $tmp_array;
    }

}