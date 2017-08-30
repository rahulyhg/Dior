<?php
class purchase_finder_appropriation{
    var $detail_basic = "调拔单详情";
     
    function detail_basic($appropriation_id){
       $render = &app::get('purchase')->render();
       $oAppropriation = &app::get('purchase')->model("appropriation_items");
       $appropriation = $oAppropriation->getList('*',array('appropriation_id'=>$appropriation_id),0,-1);
       $oBranch = &app::get('ome')->model('branch');
       $oPos = &app::get('ome')->model('branch_pos');
       if ($appropriation)
       foreach($appropriation as $k=>$v){
           if($v['from_branch_id']){
                $from_branch = $oBranch->dump(array('branch_id'=>$v['from_branch_id']),'name');
                $v['from_branch'] = $from_branch['name'];
           }
           if($v['from_pos_id']){
            $from_pos = $oPos->dump(array('branch_id'=>$v['from_branch_id'],'pos_id'=>$v['from_pos_id']),'store_position');
            $v['from_pos'] = $from_pos['store_position'];
           }
           if($v['to_branch_id']){
                $to_branch = $oBranch->dump(array('branch_id'=>$v['to_branch_id']),'name');
                $v['to_branch'] = $to_branch['name'];
           }
           if($v['to_pos_id']){
                $to_pos = $oPos->dump(array('branch_id'=>$v['to_branch_id'],'pos_id'=>$v['to_pos_id']),'store_position');
                $v['to_pos'] = $to_pos['store_position'];
           }
           $appropriation_list[] = $v;
       }
  
        $render->pagedata['appropriation'] = $appropriation_list;
        return $render->fetch('admin/appropriation/appropriation_detail.html');
    }
    
}

?>
