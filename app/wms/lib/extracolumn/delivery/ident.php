<?php
/**
 * 发货单的扩展字段批次号
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class wms_extracolumn_delivery_ident extends wms_extracolumn_abstract implements wms_extracolumn_interface{

    protected $__pkey = 'delivery_id';

    protected $__extra_column = 'column_ident';

    /**
     *
     * 获取发货单相关订单的批次号
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //批次号处理
        $printqiObj = app::get('ome')->model('print_queue_items');
        $ident_lists = $printqiObj->db->select('SELECT DISTINCT ident FROM sdb_ome_print_queue_items WHERE delivery_id in ('.implode(',',$ids).')');

        $ident_ids = array();
        foreach($ident_lists as $k =>$val){
            $ident_ids[] = $val['ident'];
        }

        $tmp_array= array();
        $ident_arr = $printqiObj->db->select('SELECT ident,'.$this->__pkey.',ident_dly FROM sdb_ome_print_queue_items WHERE delivery_id in ('.implode(',',$ids).')');
        foreach($ident_arr as $k => $ident){
                $ident_dly = $ident['ident'] . '_' . $ident['ident_dly']; //加上批次号序列
                $tmp_array[$ident[$this->__pkey]] = $ident_dly;
        }
        return $tmp_array;
    }

}