<?php
class logisticsaccounts_ctl_admin_actual extends desktop_controller{
    var $workground = 'logisticaccounts';
    var $name = '物流账单列表';


     /**
     * 下载模板
     */
    function export_template(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=物流账单模板".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
         //财务-物流对账-对账任务-模板下载-操作日志
        $logParams = array(
            'app' => $this->app->app_id,
            'ctl' => trim($_GET['ctl']),
            'act' => trim($_GET['act']),
            'modelFullName' => '',
            'type' => 'export',
            'params' => array(),
        );
        ome_operation_log::insert('finance_logisticsToAccount_toAccountTask_template_export', $logParams);
        $actualObj = $this->app->model('actual');
        $title1 = $actualObj->exportTemplate('exporttemplate');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";


 }
    /**
     *编辑实际账单
     */

    function edit($aid){
        
        $actualObj = $this->app->model('actual');
        $actual = $actualObj->detail_actual($aid);
        $actual['order_bn'] = str_replace(',','<br>',$actual['order_bn']);
        $this->pagedata['action'] = $_GET['action'];
        $this->pagedata['oper'] = $_GET['oper'];
        $this->pagedata['actual'] = $actual;
        unset($actual);

        $this->page('actual/edit_actual.html');

    }

    /**
    * 编辑保存实际账单
    */
    function do_save_actual(){
        $this->begin();
        $actualObj = $this->app->model('actual');
        $data = $_GET;
        //查询操作是否可以
        $actual = $actualObj->getlist('confirm',array('aid'=>$data['aid']),0,1);
        $actual_confirm = $actual[0]['confirm'];

        
        if($data['action']=='accounted'){
            if($data['oper']=='doedit'){
                if($actual_confirm=='0' || $actual_confirm=='3'){
                    $this->end(false,'不可编辑','index.php?app=logisticsaccounts&ctl=admin_actual&act=edit&p[0]='.$data['aid']);
                }
            }else{
                if($actual_confirm!='0'){
                    $this->end(false,'记账失败','index.php?app=logisticsaccounts&ctl=admin_actual&act=edit&p[0]='.$data['aid']);
                }
            }
        }else if($data['action']=='confirm'){
            if($data['oper']=='backconfirm'){
                if($actual_confirm!='2'){
                    $this->end(false,'未审核不可以反审核','index.php?app=logisticsaccounts&ctl=admin_actual&act=edit&p[0]='.$data['aid']);
                }
            }else{
                if($actual_confirm!='1'){
                    $this->end(false,'未记账不可审核','index.php?app=logisticsaccounts&ctl=admin_actual&act=edit&p[0]='.$data['aid']);
                }
            }
        }
        $actualObj = $this->app->model('actual');
        $result = $actualObj->save_actual($data);
        if($result){
           /*更新任务记账总金额*/
           $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
           $actual_task_data = array();
           $actual_task_data['task_id'] = $data['task_id'];
           $actual_task_data['update_money']=1;
           $actual_taskObj->update_actual_task($actual_task_data);
           if($data['action']=='accounted'){
                $actualObj->check_confirm(1,$data['task_id']);
           }else if($data['action']=='confirm'){
                $actualObj->check_confirm(2,$data['task_id']);
           }

            if($result){
                $this->end(true,'修改成功','index.php?app=logisticsaccounts&ctl=admin_actual&act=edit&p[0]='.$data['aid']);
            }else{
                $this->end(false,$msg,'index.php?app=logisticsaccounts&ctl=admin_actual&act=edit&p[0]='.$data['aid']);
            }
        }
    }



    /**
    * 查看实际账单明细
    */
    function detail_basic($aid){
        $actualObj = $this->app->model('actual');
        $actual = $actualObj->detail_actual($aid);

        $this->pagedata['actual'] = $actual;

        $this->page('actual/detail_basic.html');
    }

    /**
     * @账单详情
     * @access public
     * @param void
     * @return void
     */
    public function detail_actual($task_id)
    {
        $actual_taskObj = $this->app->model('actual_task');
        $task_id = intval($_GET['task_id']);
        $status = intval($_GET['status']);
        $flt = $_GET['flt'];
        $actualObj = kernel::single('logisticsaccounts_actual');
        if($flt=='accounted' || $flt=='view'){
            
            $actual_status = $actualObj->get_allstatus($task_id,$confirm,'');
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
        }else{
            $actual_status = $actualObj->get_allstatus($task_id,array('1','2','3'),'',array('1','2','3'));
            $actual_status['all_confirm'] = $actualObj->get_status_list($task_id,array('1','2','3'),array('2','3'),'');
            //已匹配审核
            $actual_status['hasmatched_confirm'] = $actualObj->get_status_list($task_id,array('1'),array('2','3'),'');
            //已审核
            $actual_status['overpayment_confirm'] = $actualObj->get_status_list($task_id,array('3'),array('2','3'),'');
            //已审核
            $actual_status['lesspayment_confirm'] = $actualObj->get_status_list($task_id,array('2'),array('2','3'),'');
        }
            $this->pagedata['actual_status'] = $actual_status;
            $this->pagedata['task_id'] = $task_id;
            $this->pagedata['status'] = $status;

            $adjust_money = app::get('logisticsaccounts')->getConf('logisticsaccounts.accounted.money.'.$acutal_task[0]['branch_id'].'_'.$acutal_task[0]['logi_id'].'_'.$task_id);
            //
            
            $this->pagedata['task_bn'] = $acutal_task[0]['task_bn'];
            $this->pagedata['acutal_task'] = $acutal_task[0];
            $this->pagedata['adjust_money'] = sprintf('%.2f',$adjust_money);
            unset($actual_status);
            if ($_GET['a']=='c'){
                $this->page('actual/detail_actual.html');
            }else{
                $this->singlepage('actual/detail_actual.html');
            }
            
//        }else{
//            kernel::single('logisticsaccounts_actual_confirm')->set_params($_GET)->display();
//        }

    }



    public function detail_actual_item()
    {
        kernel::single('logisticsaccounts_actual_delivery')->set_params($_GET)->display();

    }

    public function detail_actual_confirm()
    {
        kernel::single('logisticsaccounts_actual_confirm')->set_params($_GET)->display();

    }

    
    /**
     * 记账
     * @批量记账均摊运费
     *  当实际记账=预估记账，无需进行运算；
        当实际记账 不=预估记账，公式如下
        该笔实际记账金额=该笔预收费用-（预收总金额-实际支付金额）/总笔数
     * @access public
     * @param void
     * @return void
     */
    public function batch_accounted(){
        $this->begin();
        $data = $_POST;
        $task_id = $data['task_id'];
        $adjust_money = $data['adjust_money'];
        $accounted_type = $data['accounted_type'];
        if($data['oper']=='sub_accounted'){
            $actual_data = unserialize($data['aid']);
        }
        $result = kernel::single('logisticsaccounts_actual')->batch_accounted($task_id,$actual_data,$adjust_money,$accounted_type);
        if($result) {
            $this->end(true,'记账成功');
        }else{
            $this->end(false,'记账失败');
        }


    }



    /**
     * @批量记账
     * @access public
     * @param void
     * @return void
     */
    public function do_accounted(){
        $data = $_POST;
        $task_id = $_GET['task_id'];
        $actualObj = &app::get('logisticsaccounts')->model('actual');
         if($_POST['isSelectedAll']=='_ALL_'){
            $actual = $actualObj ->getlist('aid',array('task_id'=>$task_id,'status'=>$_GET['status']));

            $actual_list = array();
            foreach($actual as $k=>$v){
                array_push($actual_list,$v['aid']);
            }
            $data['aid'] =  $actual_list;
        }

        if($_GET['flt']=='accounted'){
            foreach($data['aid'] as $k=>$v){
                $actual = $actualObj->dump(array('aid'=>$v),'confirm,logi_no');
                if($actual['confirm']!='0'){

                   echo $actual['logi_no']."已记账或已审核,不可以再记账";
                   exit;
                }

            }
        }else if($_GET['flt']=='confirm'){

               foreach($data['aid'] as $k=>$v){
                $actual = $actualObj->dump(array('aid'=>$v),'confirm,logi_no');
                if($actual['confirm']!='1'){

                   echo $actual['logi_no']."已审核,不可以再审核";
                   exit;
                }

            }

        }

        $summary_actual_money = kernel::single('logisticsaccounts_actual')->summary_actual_money($task_id,$data['aid']);
        $this->pagedata['summary_actual'] = $summary_actual_money;
        unset($summary_actual_money);
        $this->pagedata['aiddata'] = serialize($data['aid']);
        $this->pagedata['task_id'] = $task_id;
        if($_GET['flt']=='accounted'){
            if($_GET['status']=='1'){
                $this->page('actual/accounted.html');
            }else{
                $this->page('actual/matched_accounted.html');
            }
        }else if($_GET['flt']=='confirm'){
            $this->page('actual/confirm.html');
        }else{
            echo '参数错误';
            exit;
        }

    }

    /**
     * @批量审核
     * @access public
     * @param void
     * @return void
     */
    public function batch_confirm()
    {
        $this->begin();

        $data = $_POST;

        $task_id = $data['task_id'];
        $actualObj = &app::get('logisticsaccounts')->model('actual');
        /***/
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
        $actual_task = $actual_taskObj->getlist('confirm_name',array('task_id'=>$task_id),0,1);
        $user_name = kernel::single('desktop_user')->get_name();
        if($actual_task[0]['confirm_name']){
            if($user_name!=$actual_task[0]['confirm_name']){
                $this->end(false,'您没有权限对此账单审核');
            }
        }
        $actual_data = unserialize($data['aid']);
        $actualObj->batch_accounted($actual_data,'confirm',$task_id);
        $this->end(true,'成功');
    }

   


}
?>