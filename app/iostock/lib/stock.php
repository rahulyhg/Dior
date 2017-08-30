<?php
class iostock_stock{
    
    /**
     * 获取库存查询结果
     * @param  
     * @return  
     * @access  public
     * @author 
     */
    function get_stock_list($bn,$branch_id)
    {
        $db = kernel::database();
        $SQL = '';
        if ($branch_id) {
            $SQL.="  AND branch_id=".$branch_id;
        }
        if ($bn) {
            $SQL.=" AND bn='$bn'";
        }
        $io_types = $this->_get_iotypes();
        $in_typeid = implode(',',$io_types['in_type']);
        $out_typeid = implode(',',$io_types['out_type']);
        

        $in_sql = "SELECT sum(nums) as stock_count FROM sdb_ome_iostock WHERE type_id in (".$in_typeid.")  ".$SQL;

        $in_stock = $db->selectrow($in_sql);

        $in_stock_count = $in_stock['stock_count'];
        $out_sql = "SELECT sum(nums) as stock_count FROM sdb_ome_iostock WHERE type_id in (".$out_typeid.")  ".$SQL;
        
        $out_stock = $db->selectrow($out_sql);
        $out_stock_count = $out_stock['stock_count'];
        $sub_stock = $in_stock_count-$out_stock_count;
        $data = array('in_stock_count'=>$in_stock_count,'out_stock_count'=>$out_stock_count,'sub_stock'=>$sub_stock);
        $product = $db->selectrow("SELECT product_id FROM sdb_ome_products WHERE bn='$bn'");
        $freeze_sql = "SELECT sum(bp.store_freeze)  as store_freeze_count FROM sdb_ome_branch_product as bp WHERE bp.product_id=".$product['product_id']." AND bp.branch_id=".$branch_id;

        $freeze_stock = $db->selectrow($freeze_sql);

        $data['store_freeze_count'] = $freeze_stock['store_freeze_count'];
        return $data;
    }

    /**
     * 获取出入库ID数组
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    function _get_iotypes()
    {
        $iostock = kernel::single('ome_iostock');
        $iostock_types = $iostock->iostock_types;
        $data = array();
        $in_type = array();
        $out_type = array();
        foreach ( $iostock_types as $k=>$types ) {
            if ($types['io'] == '1') {
                $in_type[] = $k;
            }else{
                $out_type[] = $k;
            }
        }
        $data = array('in_type'=>$in_type,'out_type'=>$out_type);
        return $data;
    }


}

?>