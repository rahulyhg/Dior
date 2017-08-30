<?php
class taoexlib_ctl_queue extends desktop_controller{
    var $name = "TG队列";
    var $workground = "setting_tools";
    
    function index(){
        $this->finder('taoexlib_mdl_queue',array(
            'title'=>'TG队列',
            'actions'=>array(
                          //array('label'=>'执行','submit'=>'index.php?app=taoexlib&ctl=queue&act=flush','target'=>'_blank'),
                        ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
         ));
    }
    
    /*function flush(){
        $post = $_POST;
        if ($post['queue_id'] && is_array($post['queue_id'])){
            foreach ($post['queue_id'] as $queue_id){
                $this->app->model('queue')->runtask($queue_id);
            }
            exit("执行成功!");
        }
        exit("无记录!");
    }*/
    
    function retry($queue_id=0){
        $url = 'index.php?app=taoexlib&ctl=queue&act=index';
        if (!$queue_id){
            $this->splash('error',$url,'请选择重试的任务');
        }
        
        $queue = $this->app->model('queue')->dump($queue_id);
        if ($queue['status'] == 'failed'){
            kernel::single('taoexlib_queue')->setNormalLevel()->resume($queue_id);
            $result = 'success';
        }else{
            $result = 'error';
        }
        $this->splash($result,$url,'重试成功');
    }
}
?>
