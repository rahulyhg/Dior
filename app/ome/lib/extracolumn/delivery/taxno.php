<?php
/**
 * 发货单的扩展字段发票头
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class ome_extracolumn_delivery_taxno extends ome_extracolumn_abstract implements ome_extracolumn_interface{

    protected $__pkey = 'delivery_id';

    protected $__extra_column = 'column_tax_no';

    /**
     *
     * 获取发货单相关订单的客服备注
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $deliveryObj = &app::get('ome')->model('delivery');

        $sql = 'SELECT DO.'.$this->__pkey.',O.tax_no FROM sdb_ome_orders AS O LEFT JOIN 
            sdb_ome_delivery_order AS DO ON DO.order_id=O.order_id WHERE DO.delivery_id in ('.implode(',',$ids).')';
        $orders = $deliveryObj->db->select($sql);
        $tax_no = array();
        $tmp_array = array();
        foreach($orders as $order){
            $tax_no[$order[$this->__pkey]][] = $order['tax_no'];
            
        }

        foreach($tax_no as $k =>$val){
            $tmp_array[$k] = '<span title="'.implode('、',$val).'">'.implode('、',$val).'</span>';
        }
        return $tmp_array;
    }

}