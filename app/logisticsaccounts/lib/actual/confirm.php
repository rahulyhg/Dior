<?php
class logisticsaccounts_actual_confirm extends eccommon_analysis_abstract implements eccommon_analysis_interface{
      public $type_options = array(
        'display' => 'true',
    );

    function __construct(&$app){
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');
        $task_id = intval($_GET['task_id']);
        $status = $_GET['status'];
        $actualObj = kernel::single('logisticsaccounts_actual');

        $actual_status = $actualObj->get_allstatus($task_id,array('1','2','3'),'',array('1','2','3'));
        $actual_status['all_confirm'] = $actualObj->get_status_list($task_id,array('1','2','3'),array('2','3'),'');
        //已匹配审核
        $actual_status['hasmatched_confirm'] = $actualObj->get_status_list($task_id,array('1'),array('2','3'),'');
        //已审核
        $actual_status['overpayment_confirm'] = $actualObj->get_status_list($task_id,array('2'),array('2','3'),'');
        //已审核
        $actual_status['lesspayment_confirm'] = $actualObj->get_status_list($task_id,array('3'),array('2','3'),'');

        $this->_render->pagedata['actual_status'] = $actual_status;
        unset( $actual_status );
        $this->_render->pagedata['task_id'] = $task_id;
        $this->_render->pagedata['status'] = $status;
        $this->_extra_view = array('logisticsaccounts' => 'confirm_extra_view.html');
    }


    public function get_type(){

        $return = array(
            'lab' => $lab,
            'data' => $data,

        );
        return $return;
    }

    public $graph_options = array(
        'hidden' => true,
    );


     public function ext_detail(&$detail){
        $filter = $this->_params;



    }
    public function set_params($params)
    {
        $this->_params = $params;

       if(!$params['status'] && $params['status']!='0'){
            $this->_params['status'] =  array('1','2','3');
        }



        return $this;
    }

    public function finder(){
        $status = $_GET['status'];
        $task_id = intval($_GET['task_id']);
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
        $actual_task = $actual_taskObj->dump($task_id, 'task_bn');

        $action = array();
        if($_GET['flt']=='confirm'){

            if(($status!='0' && $status!='4')){

            $action[] = array('label' =>'批量审核', 'submit' => 'index.php?app=logisticsaccounts&ctl=admin_actual&act=do_accounted&flt='.$_GET['flt'].'&task_id='.$_GET['task_id'].'&status='.$_GET['status'].'&filter[task_id]='.$_GET['task_id'].'&filter[status]='.$_GET['status'], 'target'=>'dialog::{width:400,height:200,title:\'审核提示\'}');
            }

        }
        $filter=array('confirm'=>array('1','2','3'));

        $params =  array(


                'title'=>"<font color=red>".$actual_task['task_bn']."</font>".app::get('logisticsaccounts')->_('物流账单'),
                   'use_buildin_new_dialog' => false,
                    'use_buildin_set_tag'=>false,
                    'use_buildin_recycle'=>false,
                    'use_buildin_export'=>true,
                    'use_buildin_import'=>false,
                    'use_buildin_filter'=>false,
                    'base_filter' => $filter,
                    'actions'=>$action,

            );
       $params['actions']= $action;
        return array(
            'model' => 'logisticsaccounts_mdl_actual',
            'params' => $params,
        );


    }

}