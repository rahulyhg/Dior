<?php
class console_ctl_admin_deliveryrefuse extends desktop_controller {

    var $name = "拒收服务";
    var $workground = "console_center";


    /**
     *
     * 拒收退货单列表
     */
    function index(){

        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
        );

        $params['base_filter']['return_type'] = array('refuse');
        $this->finder ( 'ome_mdl_reship_refuse' , $params );
    }

   
}
