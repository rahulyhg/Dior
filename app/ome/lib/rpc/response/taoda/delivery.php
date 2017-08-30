<?php
class ome_rpc_response_taoda_delivery
{
    
    /**
     * 前端推送所有平台商品信息接口
     *
     * 
     */
    
    
    function get_finish($result){
        $bns = json_decode($result['bns']);

        $dObj   = &app::get('ome')->model('delivery');
        $filter['status'] = 'succ';
        $filter['process'] = 'true';
        $filter['parent_id'] = 0;
        $back = array();

        foreach ($bns as $k => $item){
            $filter['status'] = 'succ';
            $filter['process'] = 'true';
            $filter['parent_id'] = 0;

            $filter['delivery_bn'] = $item;
            $num = 0;
            $dly = $dObj->dump($filter,'delivery_id,delivery_bn,logi_no,logi_name',array('delivery_items'=>array('*')));
            
            $back[$k]['delivery_id'] = $dly['delivery_bn'];
            $back[$k]['logi_no']     = $dly['logi_no'];
            $back[$k]['logi_name']   = $dly['logi_name'];
           
        }

        return $back;
    }
}
?>