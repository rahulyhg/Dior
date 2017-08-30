<?php
/**
 * 更新售后单
 * 说明: 依据完成的退、换货单和售后退款单来生成售后单
 * @package default
 * @author 
 **/
class sales_updatescript_updateaftersale
{

    /**
     * 修复售后单数据
     *
     * @return void
     * @author 
     **/
    function updateAftersale($pre_time,$last_time){
         
        $pre_time = strtotime($pre_time.' 00:00:00');
        $last_time = strtotime($last_time.' 23:59:59');
        $offset = 0;
        $Oreship = app::get("ome")->model("reship");
        $Orefund = app::get("ome")->model("refund_apply");

        $sql = 'delete A,AI from sdb_sales_aftersale A left join sdb_sales_aftersale_items AI on A.aftersale_id=AI.aftersale_id where A.return_type in ("return","change") and A.aftersale_time>='.$pre_time.' and A.aftersale_time<='.$last_time;

        $Oreship->db->exec($sql);

        //处理已完成的退换货单
        while($this->getreship_data($Oreship,$offset,$pre_time,$last_time)){
            $offset++;
        }

        $sql = 'delete A,AI from sdb_sales_aftersale A left join sdb_sales_aftersale_items AI on A.aftersale_id=AI.aftersale_id where A.return_type = "refund" and A.aftersale_time>='.$pre_time.' and A.aftersale_time<='.$last_time;

        $Orefund->db->exec($sql);

        #echo "refund source delete succ";

        //处理已完成的退款申请单
        while($this->getrefund_data($Orefund,$offset,$pre_time,$last_time)){
            $offset++;
        }

    }

    function getrefund_data($Orefund,$offset,$pre_time,$last_time){
        $limit = 1000;

        $where = 'refund_refer="1" and status="4" and last_modified >='.$pre_time.' and last_modified<='.$last_time;


        @ini_set('memory_limit','1024M');
        @set_time_limit(0);
                
        $sql = 'select apply_id from sdb_ome_refund_apply where '.$where.' limit '.$offset*$limit.','.$limit;

        $refundinfo = $Orefund->db->select($sql);

        if(!$refundinfo) return false;
        $aftersaleLib = kernel::single('sales_aftersale');

        foreach($refundinfo as $k=>$v){
            $aftersaleLib->generate_aftersale($v['apply_id'],'refund');         
        }

        return true;
    }


    function getreship_data($Oreship,$offset,$pre_time,$last_time){
        $limit = 1000;

        $where = 'status="succ" and t_end >='.$pre_time.' and t_end<='.$last_time;


        @ini_set('memory_limit','1024M');
        @set_time_limit(0);
                
        $sql = 'select reship_id,return_type from sdb_ome_reship where '.$where.' limit '.$offset*$limit.','.$limit;

        $reshipinfo = $Oreship->db->select($sql);

        if(!$reshipinfo) return false;
        $aftersaleLib = kernel::single('sales_aftersale');

        foreach($reshipinfo as $k=>$v){
            $aftersaleLib->generate_aftersale($v['reship_id'],$v['return_type']);         
        }

        return true;
    }

} // END class 


