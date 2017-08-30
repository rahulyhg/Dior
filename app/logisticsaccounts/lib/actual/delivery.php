<?php
class logisticsaccounts_actual_delivery extends eccommon_analysis_abstract implements eccommon_analysis_interface{
      public $type_options = array(
        'display' => 'true',
    );

   function _views(){
        $sub_menu = $this->_viewsestimate_low();
        return $sub_menu;
    }
    function _viewsestimate_low(){


        $sub_menu = array();
        $sub_menu[] = array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false);
        $sub_menu[] = array('label' => app::get('base')->_('未记账'), 'filter' => array(), 'optional' => false);
        $sub_menu[] = array('label' => app::get('base')->_('已记账'), 'filter' => array(), 'optional' => false);
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = '10';
            $sub_menu[$k]['href'] = 'index.php';
        }
        return $sub_menu;
    }
    function __construct(&$app){
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');
        $task_id = intval($_GET['task_id']);
        $status = $_GET['status'];
        $this->_render->pagedata['task_id'] = $task_id;
        $this->_render->pagedata['status'] = $status;
        $actualObj = kernel::single('logisticsaccounts_actual');
        $actual_status = $actualObj->get_allstatus($task_id);

        $hasmatched_accounted = $actualObj->get_status_list($task_id,array('1'),array('1','2','3'),'');
        $actual_status['hasmatched_accounted'] = $hasmatched_accounted;
        $hasmatched_unaccounted = $actualObj->get_status_list($task_id,array('1'),array('0'),'');
        $actual_status['hasmatched_unaccounted'] = $hasmatched_unaccounted;
        $actual_status['hasmatched_confirm'] = $actualObj->get_status_list($task_id,array('1'),array('2','3'),'');
        //已记账
        $hasaccounted = $actualObj->get_status_list($task_id,array('1','2','3'),array('1','2','3'),'');
        $actual_status['hasaccounted'] = $hasaccounted;
        //未记账
        $unaccounted = $actualObj->get_status_list($task_id,array('1','2','3'),array('0'),'');
        $actual_status['unaccounted'] = $unaccounted;
        $actual_status['overpayment_accounted'] = $actualObj->get_status_list($task_id,array('3'),array('1','2','3'),'');
        $actual_status['overpayment_unaccounted'] = $actualObj->get_status_list($task_id,array('3'),array('0'),'');
        $actual_status['lesspayment_accounted'] = $actualObj->get_status_list($task_id,array('2'),array('1','2','3'),'');
        $actual['lesspayment_unaccounted'] = $actualObj->get_status_list($task_id,array('2'),array('0'),'');
        $this->_render->pagedata['actual_status'] = $actual_status;

        unset( $actual_status );
        $this->_extra_view = array('logisticsaccounts' => 'extra_view.html');
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


    public function finder(){
        $status = intval($_GET['status']);
        $task_id = intval($_GET['task_id']);
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
        $actual_task = $actual_taskObj->dump($task_id, 'task_bn');
        $this->title = '对账';
        $action = array();
        if($_GET['flt']=='accounted'){
            if($status=='1' || $status=='2' || $status=='3'){
                if($_GET['confirm']!=1){
                $action[] = array('label' =>'批量记账', 'submit' => 'index.php?app=logisticsaccounts&ctl=admin_actual&act=do_accounted&flt='.$_GET['flt'].'&task_id='.$_GET['task_id'].'&status='.$_GET['status'].'&filter[task_id]='.$_GET['task_id'].'&filter[status]='.$_GET['status'],'target'=>'dialog::{width:500,height:300,title:\'批量记账\'}');
                }

            }
            $accounted = $_GET['accounted'];

        }else{
            $action = array();
        }

        $filter['task_id'] = $_GET['task_id'];
        #区分对账结果
        if(isset($status)){
            $filter['status'] = $status;
        }
        
        $params =  array(
            'title'=>"<font color='red'>".$actual_task['task_bn']."</font>".app::get('logisticsaccounts')->_('物流账单'),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
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