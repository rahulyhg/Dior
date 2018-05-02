<?php

class ome_mdl_reship_items extends dbeav_model{
    /*
     * 统计某订单货号生成退款单数
     *
     * @param int $order_id ,varchar $bn
     *
     * @return int
     */
    function Get_refund_count($order_id,$bn,$reship_id='',$return_type='return')
    {
        $sql = "SELECT sum(nums) as count FROM sdb_ome_order_items WHERE order_id='".$order_id."' AND bn='".$bn."' AND `delete`='false' ";

        $order=$this->db->selectrow($sql);

		//MCD换货不管成功与否都无法再次申请
		if($return_type=="change"){
			
			$arrAfterSales=array();
			$arrAfterSales=$this->db->select("SELECT r.return_type FROM sdb_ome_reship r LEFT JOIN sdb_ome_reship_items  i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.order_id='".$order_id."' AND i.bn='".$bn."'");
			 //相同SKU申请过，就无法申请另一个
			foreach($arrAfterSales as $after){
			    if($after['return_type']=="change"){
			 		return 0;
				}
			}
			
		}
 
        $sql = "SELECT sum(i.normal_num) as normal_count,sum(i.defective_num) as defective_count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check in ('11','7') AND r.order_id='".$order_id."' AND i.bn='".$bn."'";

        if($reship_id != ''){
            $sql .= ' AND r.reship_id!='.$reship_id;
        }//已收获的取入库数量
        $refund = $this->db->selectrow($sql);

        $sql1 = "SELECT sum(i.num) as nums FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check not in ('5','7','11') AND r.order_id='".$order_id."' AND i.bn='".$bn."'";//未入仓库的取申请数量
        if($reship_id != ''){
            $sql1 .= ' AND r.reship_id!='.$reship_id;
        }
        $refund1 = $this->db->selectrow($sql1);

        return $order['count']-$refund['normal_count']-$refund['defective_count']-$refund1['nums'];
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

    /*
    *  统计退换货明细中，退入商品和换出商品的个数
    *
    * @param int $reship_id
    *
    * * return array
    */
     function Get_items_count($reship_id,&$result){
        $return = $this->db->select('select count(*) as c from sdb_ome_reship_items where reship_id = '.$reship_id.' and return_type="return"');
        $change = $this->db->select('select count(*) as c from sdb_ome_reship_items where reship_id = '.$reship_id.' and return_type="change"');
        $result['return'] = $return[0]['c'];
        $result['change'] = $change[0]['c'];
     }
}