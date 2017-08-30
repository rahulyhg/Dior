<?php
class ome_ctl_admin_branchset extends desktop_controller{
    var $name = "仓库设置";
    var $workground = "setting_tools";

    function index(){
       $branchtype = app::get('wms')->getConf('wms.branchset.type');
       $this->pagedata['branchtype'] =  $branchtype;
       $this->display('admin/branch/branchset.html');
    }

    
    /**
     *保存设置
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function save()
    {
        $this->begin();
        $settype = $_POST['set']['branchtype'];
        app::get('wms')->setConf('wms.branchset.type',$settype);
        $this->end(true,'保存成功');
    }
     
}
?>
