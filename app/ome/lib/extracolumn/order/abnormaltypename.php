<?php
/**
 * 订单的扩展字段异常类型名称
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class ome_extracolumn_order_abnormaltypename extends ome_extracolumn_abstract implements ome_extracolumn_interface{

    protected $__pkey = 'order_id';

    protected $__extra_column = 'column_abnormal_type_name';

    /**
     *
     * 获取订单相关的异常类型名称
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $deliveryObj = &app::get('ome')->model('orders');
        $abtpName_lists = $deliveryObj->db->select('select abnormal_type_name,'.$this->__pkey.' from  sdb_ome_abnormal where order_id in ('.implode(',',$ids).')');

        $tmp_array= array();
        foreach($abtpName_lists as $k=>$row){
             $tmp_array[$row[$this->__pkey]] = $row['abnormal_type_name'];
        }
        return $tmp_array;
    }

}