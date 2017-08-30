<?php
class omestorage_ctl_admin_stockdumpConfirm extends desktop_controller{

    var $name = "确认转储单";
    var $workground = "console_center";
    function index(){
		
        $this->title = '确认转储单';
        $params = array(
            'title'=>$this->title,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
        );
        $params['base_filter'] = array('otype'=>2,'in_status'=>9,'confirm_type'=>1,'self_status'=>1);
        $user_branch = kernel::single("ome_userbranch");
        $branch_id= $user_branch->get_user_branch_id();
        if($branch_id){
            $where_branch_id = '('.implode(',', $branch_id).')';
            $params['base_filter']['filter_sql'] = "(from_branch_id in ".$where_branch_id." or to_branch_id in ".$where_branch_id.")";
        }
        $this->finder('omestorage_mdl_stockdump',$params);
    }

    


}