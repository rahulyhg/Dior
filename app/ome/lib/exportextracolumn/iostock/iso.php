<?php
/**
 * [出入库明细]导出扩展字段出入单名称
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class ome_exportextracolumn_iostock_iso extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'iostock_id';

    protected $__extra_column = 'column_iostock_name';

    /**
     *
     * 获取[出入库明细]相关的出入单名称
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids)
    {
        //根据订单ids获取相应的出入单名称
        $sql    = "SELECT a.".$this->__pkey.", b.name FROM sdb_ome_iostock AS a LEFT JOIN sdb_taoguaniostockorder_iso AS b 
                    ON a.original_id=b.iso_id 
                    WHERE a.iostock_id in(".implode(',', $ids).")";
        
        $data_lists    = kernel::database()->select($sql);

        $tmp_array= array();
        foreach($data_lists as $k=>$row)
        {
            $tmp_array[$row[$this->__pkey]] = $row['name'];
        }
        
        return $tmp_array;
    }

}