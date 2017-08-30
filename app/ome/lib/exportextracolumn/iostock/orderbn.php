<?php
/**
 * [出入库明细]导出扩展字段订单号
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class ome_exportextracolumn_iostock_orderbn extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'iostock_id';

    protected $__extra_column = 'column_order_bn';

    /**
     *
     * 获取[出入库明细]相关的出入单名称
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids)
    {
        //根据订单ids获取相应的出入单名称
        $sql    = "SELECT ".$this->__pkey.", type_id, original_id, iostock_id FROM sdb_ome_iostock WHERE iostock_id in(".implode(',', $ids).")";
        
        $iostock_lists    = kernel::database()->select($sql);

        foreach($iostock_lists as $k=>$row)
        {
            if($row['type_id'] == 3){
                if(isset($row['original_id'])){
                    //获取本次导出记录所有的original_id
                    $_originalId[] = $row['original_id'];
                }
            }
        }

        $_count = count($_originalId);
        if($_count > 1){
            $_originalId = array_unique($_originalId);
            $_originalId = implode(',', $_originalId);//包含分割一个数组元素
        }elseif(1 == $_count){
            $_originalId = $_originalId[0];
        }
        
        if($_originalId){
            //获取本次导出记录所有销售订单号
            $orderObj = app::get('ome')->model('orders');
            $_orderInfo = $orderObj->getOrdersBnById($_originalId);
        }

        //生成成delivery_id和order_bn的键值对
        foreach( (array) $_orderInfo as $_val){
            if(array_key_exists($_val['delivery_id'],$id_bn)){
                //把同一个delivery_id对应的多个order_bn逗号拼接起来
                $id_bn[$_val['delivery_id']] = $id_bn[$_val['delivery_id']].' , '.$_val['order_bn'];
            }else{
                $id_bn[$_val['delivery_id']] = $_val['order_bn'];
            }
        }

        $tmp_array= array();
        foreach($iostock_lists as $k=>$row)
        {
            if($row['type_id'] == 3){
                if(isset($row['original_id'])){
                    $tmp_array[$row[$this->__pkey]] = (string)$id_bn[$row['original_id']];
                }
            }else{
                $tmp_array[$row[$this->__pkey]] = '-';
            }
        }

        return $tmp_array;
    }

}