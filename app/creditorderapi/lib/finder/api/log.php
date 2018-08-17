<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 13:40
 */
class creditorderapi_finder_api_log
{
    var $detail_basic = '基本信息';
    var $column_control = '操作';

    function detail_basic($id)
    {
        $render = app::get('creditorderapi')->render();
        $log_data= app::get('creditorderapi')->model('api_log')->getList('*', array('id' => $id));
        $render->pagedata['data']=$log_data[0];
        return $render->fetch('admin/api/detail.html');
    }


}
