<?php
/**
 * 发货单的扩展字段客服备注
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class ome_extracolumn_delivery_marktext extends ome_extracolumn_abstract implements ome_extracolumn_interface{

    protected $__pkey = 'delivery_id';

    protected $__extra_column = 'column_mark_text';

    /**
     *
     * 获取发货单相关订单的客服备注
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $deliveryObj = &app::get('ome')->model('delivery');
        $customMark_lists = $deliveryObj->db->select('select mark_text,'.$this->__pkey.' from sdb_ome_orders orders join sdb_ome_delivery_order  on orders.order_id=sdb_ome_delivery_order.order_id and sdb_ome_delivery_order.delivery_id in ('.implode(',',$ids).')');

        $tmp_array= array();
        foreach($customMark_lists as $k=>$row){
            $mark_text = '';#买家留言
            $custom = kernel::single('ome_func')->format_memo($row['mark_text']);
             if($custom){
                 // 取最后一条
                 $custom = array_pop($custom);
                 $mark_text = $custom['op_content'].'；'."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp";
             }

             if(isset($tmp_array[$row[$this->__pkey]])){
                $tmp_array[$row[$this->__pkey]] .= $mark_text;
             }else{
                $tmp_array[$row[$this->__pkey]] = $mark_text;
             }
        }
        return $tmp_array;
    }

}