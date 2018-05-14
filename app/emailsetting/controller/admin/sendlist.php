<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class emailsetting_ctl_admin_sendlist extends desktop_controller{
    
    var $name = "发送列表";
    var $workground = "setting_tools";
	
    public function index(){
        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '发送列表';
        $params = array(
            'title'=>$this->title,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>false,
            'use_view_tab'=>false,
        );
        $this->finder('emailsetting_mdl_sendlist',$params);
    }
}