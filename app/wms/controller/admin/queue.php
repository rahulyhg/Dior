<?php
class wms_ctl_admin_queue extends desktop_controller
{
    var $name = "导入中盘点";
    var $workground = "wms_center";
    public function index(){
        $base_filter = array(
           'queue_title'=>'盘点导入',

         );
            $params = array(
            'title'=>app::get('desktop')->_('导入中盘点'),
            'actions'=>array(
                array('label'=>app::get('desktop')->_('全部启动'),'submit'=>'index.php?app=desktop&ctl=queue&act=run'),
                array('label'=>app::get('desktop')->_('全部暂停'),'submit'=>'index.php?app=desktop&ctl=queue&act=pause'),
                ),
            'base_filter' => $base_filter
            );

            $this->finder('base_mdl_queue',$params);
        }
}
?>