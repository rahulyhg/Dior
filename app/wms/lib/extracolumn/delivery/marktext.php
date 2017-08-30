<?php
/**
 * 发货单的扩展字段客服备注
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class wms_extracolumn_delivery_marktext extends wms_extracolumn_abstract implements wms_extracolumn_interface{

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
        $sql ='select ome.delivery_id, wms.delivery_id AS wms_delivery_id from sdb_ome_delivery AS ome left join sdb_wms_delivery AS wms on ome.delivery_bn=wms.outer_delivery_bn where wms.delivery_id in('.implode(',',$ids).')';
        $temp = $deliveryObj->db->select($sql);
        foreach ($temp as $key => $val){
            $temp_data[$val['delivery_id']] = array('delivery_id'=>$val['delivery_id'], 'wms_delivery_id'=>$val['wms_delivery_id']);
            $outer_ids[] = $val['delivery_id'];
        }
        $customMark_lists = $deliveryObj->db->select('select mark_text,'.$this->__pkey.' from sdb_ome_orders orders join sdb_ome_delivery_order  on orders.order_id=sdb_ome_delivery_order.order_id and sdb_ome_delivery_order.delivery_id in ('.implode(',',$outer_ids).')');

        $tmp_array= array();
        foreach($customMark_lists as $k=>$row){
            $mark_text = '';#买家留言
            $custom = kernel::single('ome_func')->format_memo($row['mark_text']);
            if($custom){
                // 取最后一条
                $custom = array_pop($custom);
                $mark_text = $custom['op_content'].'；'."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp";
            }
            $wms_delivery_id = $temp_data[$row[$this->__pkey]]['wms_delivery_id'];
            if(isset($tmp_array[$wms_delivery_id])){
               $tmp_array[$wms_delivery_id] .= $mark_text;
            }else{
               $tmp_array[$wms_delivery_id] = $mark_text;
            }
        }
        return $tmp_array;
    }

}