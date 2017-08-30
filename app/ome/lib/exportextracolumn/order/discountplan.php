<?php
/**
 * 订单导出扩展字段优惠方案
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class ome_exportextracolumn_order_discountplan extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'order_id';

    protected $__extra_column = 'column_discount_plan';

    /**
     *
     * 获取订单相关的优惠方案
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据订单ids获取相应的优惠方案
        $orderPmtObj = &app::get('ome')->model('order_pmt');
        $discountplan_lists = $orderPmtObj->db->select('select pmt_describe,'.$this->__pkey.' from  sdb_ome_order_pmt where order_id in ('.implode(',',$ids).')');

        $tmp_array= array();
        foreach($discountplan_lists as $k=>$row){
             if(isset($tmp_array[$row[$this->__pkey]])){
                $tmp_array[$row[$this->__pkey]] .= $row['pmt_describe'].";";
             }else{
                $tmp_array[$row[$this->__pkey]] = $row['pmt_describe'].";";
             }
        }
        return $tmp_array;
    }

}