<?php
/**
* 仓库类
*
* @copyright shopex.cn 2013.4.10
* @author dongqiujin<123517746@qq.com>
*/
class ome_branch{

    /**
    * 获取仓库所关联的WMS
    *
    * @access public
    * @param String $branch_bn 仓库编号
    * @return String wms_id
    */
    public function getWmsId($branch_bn=''){
        $branchMdl = app::get('ome')->model('branch');
        $branch = $branchMdl->db->selectRow("select wms_id from sdb_ome_branch WHERE branch_bn='$branch_bn'");
        return isset($branch) ? $branch['wms_id'] : '';
    }

    /**
    * 获取WMS渠道列表
    *
    * @access public
    * @return Array
    */
    public function getWmsChannelList(){
        return kernel::single('channel_func')->getWmsChannelList();
    }

    /**
     *
     * 获取绑定过仓储类型的仓库列表
     * @access public
     * @return Array
     */
    public function getBindWmsBranchList(){
        $branchMdl = app::get('ome')->model('branch');
        $branch = $branchMdl->db->select("select * from sdb_ome_branch where wms_id > 0");
        return isset($branch) ? $branch : '';
    }

    /**
     *
     * 根据仓库ID获取仓库编码BN
     * @param string $branch_id 仓库ID
     * @return string $branch_bn 仓库编号
     */
    public function getBranchBnById($branch_id){
        $branchMdl = app::get('ome')->model('branch');
        $branch = $branchMdl->db->selectrow("select branch_bn from sdb_ome_branch WHERE branch_id=".$branch_id);
        return isset($branch) ? $branch['branch_bn'] : '';
    }

    /**
     *
     * 根据仓库ID获取仓库编码BN
     * @param string $branch_id 仓库ID
     * @return string $wms_id 仓库类型ID
     */
    public function getWmsIdById($branch_id){
        $branchMdl = app::get('ome')->model('branch');
        $branch = $branchMdl->db->selectrow("select wms_id from sdb_ome_branch WHERE branch_id=".$branch_id);
        return isset($branch) ? $branch['wms_id'] : '';
    }

    
    /**
     * 根据仓库ID返回节点类型.
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function getNodetypBybranchId($branch_id)
    {

        $wms_id = $this->getWmsIdById($branch_id);

        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->dump(array('channel_id'=>$wms_id),'node_type');
        
        return $detail['node_type'];
    }
}