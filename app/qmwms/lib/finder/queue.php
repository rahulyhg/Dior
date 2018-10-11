<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 13:40
 */
class qmwms_finder_queue
{
    var $detail_basic   = '基本信息';
    var $column_control = '操作';

    public function detail_basic($id){
        $render    = app::get('qmwms')->render();
        $queueInfo = app::get('qmwms')->model('queue')->dump(array('id' => $id), '*');
        unset($queueInfo['api_params']) ;
        $render->pagedata['data'] = $queueInfo;
        return $render->fetch('admin/queue/detail.html');
    }

}
