<?php
class taoguaninventory_ctl_admin_queue extends desktop_controller
{
    var $name = "导入中盘点";
    var $workground = "storage_center";
    public function index(){
        $base_filter = array(
           'queue_title'=>'盘点导入',

         );
            $params = array(
            'title'=>app::get('desktop')->_('导入中盘点'),
            'actions'=>array(
                array('label'=>app::get('desktop')->_('全部启动'),'submit'=>'index.php?app=taoguaninventory&ctl=admin_queue&act=run'),
                array('label'=>app::get('desktop')->_('全部暂停'),'submit'=>'index.php?app=desktop&ctl=queue&act=pause'),
                ),
            'base_filter' => $base_filter
            );

            $this->finder('base_mdl_queue',$params);
        }

        
        /**
         * 盘点表启动.
         * @
         * @return 
         * @access  public
         * @author cyyr24@sina.cn
         */
        function run()
        {
            $this->begin('index.php?app=taoguaninventory&ctl=admin_queue&act=index');
             if( $_POST['isSelectedAll']=='_ALL_'){
                 $this->end(false,'不支持全选全部启动!');
             }
            $queue_id = $_POST['queue_id'];
            $queue_model = app::get('base')->model('queue');
            foreach ($queue_id as $_id ) {
                $queue_model->runtask($_id);
            }
            $this->end(true,app::get('desktop')->_('启动成功'));
        }
}
?>