<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 10:15
 */
class creditorderapi_ctl_admin_api_log extends desktop_controller
{
    function index()
    {
        $base_filter = array();
        $params = array(
            'title' => '积分订单api日志列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_filter' => true,
            'base_filter' => $base_filter,
            'use_buildin_recycle'=>false,
        );
        $this->finder('creditorderapi_mdl_api_log', $params);
    }
}
