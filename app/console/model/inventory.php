<?php
class console_mdl_inventory extends dbeav_model{
    
    var $has_many = array(
        'inventory_items' => 'inventory_items',
    );
    
    function gen_id(){
        return 'P'.date("ymdHis").rand(0,9).rand(0,9);
    }
    
    
    function do_save($applySdf, $branch_bn){
        if (!$applySdf || !$branch_bn) return false;
        $oInvApply  = &app::get('console')->model("inventory_apply");
        $inventoryObj = kernel::single('console_receipt_inventory');
        //先生成盘点，再生成出入库
        
        if(!$inventoryObj->finish_inventory($applySdf['inventory_apply_bn'],$branch_bn,1,$applySdf['inventory_apply_items'])) return false;
        
        //确认盘点申请
        $rs = $oInvApply->update(array('status'=>'confirmed','process_date'=>time()), array('inventory_apply_id'=>$applySdf['inventory_apply_id']));
        if (!$rs) return false;
        return true;
    }
    

    public function modifier_out_id($row){
        $branchObj = kernel::single('console_iostockdata');
        $branch = $branchObj->getBranchBybn($row);
        return $branch['name'];
        
    }

    public function modifier_branch_bn($row){
        $branchObj = kernel::single('console_iostockdata');
        $branch = $branchObj->getBranchBybn($row);
        return $branch['name'];
    }
}