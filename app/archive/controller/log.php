<?php
class archive_ctl_log extends desktop_controller{
    
    
    function index()
    {
        $params = array(
            'title'=>'归档日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
         );

        $this->finder('archive_mdl_operation_log',$params);
    }

    
   

}
?>