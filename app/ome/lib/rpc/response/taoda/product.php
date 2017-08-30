<?php
class ome_rpc_response_taoda_product
{
    
    /**
     * 前端推送所有平台商品信息接口
     *
     * 
     */
    
    function get_pos($result){
        $start = json_decode($result['start_time']);
        $end = json_decode($result['end_time']);
        $page = json_decode($result['page']);
        $limit = json_decode($result['limit']);
        //if (empty($bn)) return false;
        //$bn = array('311141','311142','311143','311144','311145');
        //$strbns = implode(',', $bn);
        //$bns = explode(',', $strbns);
        $bObj   = &app::get('ome')->model('branch');
        $branch = $bObj->getList('branch_id');
        $branch_id = $branch[0]['branch_id'];
        $back = array();
        $limit_a = ($page-1)*$limit;
        $limit_b = $page*$limit;
        $where = '';
        if ($end != ''){
            $where = " AND bpp.create_time < $end ";
        }
        //$back['branch'] = $branch_id;
        //foreach ($bns as $k => $item){
        $sql = "SELECT p.bn,bp.pos_id,bp.store_position,bpp.store FROM sdb_ome_branch_pos bp 
                                JOIN sdb_ome_branch_product_pos bpp 
                                    ON bp.pos_id=bpp.pos_id 
                                LEFT JOIN sdb_ome_products p 
                                    ON bpp.product_id=p.product_id 
                                WHERE bp.branch_id='$branch_id' 
                                    AND bpp.default_pos='true' 
                                    AND bpp.create_time >= $start $where ";
        //$back['sql'] = $sql;
        $data = $bObj->db->select($sql);
        $num = 0;
        $sum = 0;
        foreach ($data as $k => $i){
            if ($num >= $limit_a && $num < $limit_b){
                $back['pos'][$sum]['bn'] = $i['bn'];
                //$back[$item]['pos_id'] = $i['pos_id'];
                $back['pos'][$sum]['pos'] = $i['store_position'];
                $back['pos'][$sum]['store'] = $i['store'];
                $sum++;
            }
            $num++;
        }
        //}
        $back['count'] = count($data);
        $back['time'] = time();
        //echo "<pre>";
        //print_r($back);
        return $back;
    }
}
?>