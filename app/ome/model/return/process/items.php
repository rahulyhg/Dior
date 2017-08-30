<?php

class ome_mdl_return_process_items extends dbeav_model{

    /*
     * 统计某订单货号生成退款单数
     *
     * @param int $order_id ,varchar $bn
     *
     * @return int
     */
    function Get_refund_count($order_id,$bn)
    {
        $ORDER=$this->db->selectrow("SELECT sum(s.nums) as count FROM sdb_ome_order_items as s WHERE s.order_id='$order_id' AND s.bn='$bn' group by s.bn");
        $refund = $this->db->selectrow("SELECT sum(i.num) as count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE r.is_check!='5' AND r.order_id='".$order_id."' AND i.bn='".$bn."' group by i.bn");
        return $ORDER['count']-$refund['count'];
    }
    /*
    *  根据货号获取对应仓库和ID
    *
    * @param int $order_id ,varchar $bn
    *
    * * return array
    */
     function getBranchCodeByBnAndOd($bn,$orderid)
     {
         $oBranch=&$this->app->model('branch');
         $sqlstr = "SELECT s.branch_id,s.delivery_id FROM sdb_ome_delivery as s left join sdb_ome_delivery_items sdi on sdi.delivery_id = s.delivery_id left join sdb_ome_delivery_order as o on o.delivery_id=s.delivery_id WHERE o.order_id='$orderid' AND sdi.bn='$bn' AND s.type='normal'";

        $branch=$this->db->select($sqlstr);
        
        $branch_ids = array();
        $t_branch = $branch;
        foreach($t_branch as $k=>$v){
            if(!in_array($v['branch_id'],$branch_ids)){
                $branchs = $oBranch->dump($v['branch_id'],'name,branch_id');
                $branch[$k]['branch_name']=$branchs['name'];
                $branch_ids[] = $v['branch_id'];
            }else{
                unset($branch[$k]);
            }
        }
        
        return $branch;
     }

}