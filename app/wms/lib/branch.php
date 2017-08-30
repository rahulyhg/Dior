<?php
class wms_branch{

    /*
     * 获取操作员管辖仓库
     * 
     */

    function getBranchwmsByUser($is_super) {

        $oBranch = &app::get('ome')->model('branch');
        $oBops = &app::get('ome')->model('branch_ops');
        $filter = array();   
        if(!$is_super){
            $opInfo = kernel::single('ome_func')->getDesktopUser();
            $op_id = $opInfo['op_id'];
            $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
            if ($bops_list)
            foreach ($bops_list as $k => $v) {
                $bps[] = $v['branch_id'];
            }
            $filter['branch_id'] = $bps;
            if(!$bps){
                return false;
            }
        }
        
        $filter['wms_id'] = $this->getBranchByselfwms();

        $branch_list = $oBranch->getList('branch_id', $filter, 0, -1);

        if ($branch_list)
        $branch_ids = array();
        foreach ($branch_list as $branch_list) {
            $branch_ids[] = $branch_list['branch_id'];

        }
        return $branch_ids;
    }

   /**
   * 返回主仓和售后仓
   */
    function getBranchs($field='*') {
        $rows = $this->db->select('select ' . $field . ' from sdb_ome_branch where attr="true"  AND `type` in (\'main\',\'damaged\') order by weight desc');

        return $rows;
    }

    /**
    * 获取自有仓储仓库
    */
    function getBranchByselfwms(){
        $wms_list = kernel::single('channel_func')->getWmsChannelList();
        
        $self_wmslist = array();
        foreach($wms_list as $wms){
            if ($wms['adapter']=='selfwms'){
                $self_wmslist[]=$wms['wms_id'];
            }
        }
        
        return $self_wmslist;
    }

    
}
