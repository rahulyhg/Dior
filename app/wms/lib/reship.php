<?php
/**
* 退货相关方法
*/
class wms_reship{
    /**
    * 根据status返回退货单明细列表
    *
    * return array data
    */
    function reship_data($reship_id,$status){
        $oReship = &app::get('ome')->model('reship');
        $reship_data = $oReship->dump(array('reship_id'=>$reship_id),'reship_bn,logi_no,logi_id');
        
        $Oreship_items = &app::get('ome')->model('reship_items');
        $oProcess_items = &app::get('ome')->model('return_process_items');
        $Odly_corp = &app::get('ome')->model('dly_corp');
        $dly_corp = $Odly_corp->dump($reship_data['logi_id'],'type');
        $data = array();
        if($status=='DENY'){
            $reship_list = $Oreship_items->getlist('bn,product_name,product_id,num,branch_id',array('reship_id'=>$reship_id),0,-1);
        }else if($status=='FINISH'){
            $reship_list = $oProcess_items->getlist('bn,product_id,num,branch_id',array('reship_id'=>$reship_id),0,-1);
        }
        foreach ($reship_list as $list) {
            $branch_id = $list['branch_id'];
            if(isset($data[$branch_id])){
                $data[$branch_id]['item'][] = $list;
            }else{
                $data[$branch_id]['item'][] = $list;
                $data[$branch_id]['reship_bn'] = $reship_data['reship_bn'];
                $data[$branch_id]['reship_bn'] = $reship_data['reship_bn'];
                $data[$branch_id]['logi_no'] = $reship_data['logi_no'];
                $data[$branch_id]['logistics'] = $dly_corp['type'];
            }
        }
        return $data;
    }
    
    /**
    * 向oms发起通知状态更新数据组织
    *
    */
    function notify_reship_data($reship_id,$is_check,$remark){
        if($is_check='9' || $is_check='10'){
            $status = 'DENY';
        }else{
            $status = 'FINISH';
        }
        $data = $this->reship_data($reship_id,$status);
        foreach($data as $k=>$v){
            $wms_id = kernel::single('ome_branch')->getWmsIdById($k);
            $tmp = array();
            $tmp=$v;
            $tmp['is_check'] = $is_check;
            $tmp['remark'] = $remark;
            $tmp['operate_time'] = date('Y-m-d H:i:s');
            kernel::single('wms_event_trigger_reship')->updateStatus($wms_id, $tmp, true);
        }

    }
}
?>