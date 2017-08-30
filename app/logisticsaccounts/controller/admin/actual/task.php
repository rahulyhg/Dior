<?php
class logisticsaccounts_ctl_admin_actual_task extends desktop_controller{
    var $workground = 'logisticaccounts';
    var $name = '物流账单任务';

    function index(){
     $finder_id = $_GET['_finder']['finder_id'];
     $action = array();
      if($_GET['flt']=='accounted'){
          $action = array(
              array('label' =>'新建任务', 'href' => 'index.php?app=logisticsaccounts&ctl=admin_actual_task&act=add&_finder[finder_id]='.$finder_id.'&finder_id='.$finder_id, 'target'=>'dialog::{width:600,height:400,title:\'新建对账任务\'}'),
              array('label' =>'下载模板', 'href' => 'index.php?app=logisticsaccounts&ctl=admin_actual&act=export_template', 'target' => '_blank'),
              array('label' =>'删除', 'submit' => 'index.php?app=logisticsaccounts&ctl=admin_actual_task&act=delete','target'=>'dialog::{width:400,height:200,title:\'批量删除\'}'),


           );
           $filter = array();
      }else if($_GET['flt']=='confirm'){
          if($_POST['status']){
              $status = $_POST['status'];
                if($status==1 || $status==2 || $status==3 || $status==5)
              {
                    $filter['status']=$status;
              }else{
                  $filter['status']=array('1','2','3','5');
              }
          }else{
                $filter['status']=array('1','2','3','5');
          }
      }

      /**
        *获取管理员管辖仓库
        */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $filter['branch_id'] = $branch_ids;
            }else{
                $filter['branch_id'] = 'false';
            }
        }
          # 在列表上方添加搜索
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('actual_task_finder_top');
            $panel->setTmpl('finder/finder_panel_filter.html');
            $panel->show('logisticsaccounts_mdl_actual_task', $params);
        }
      $params = array(
                            'title'=>'物流账单任务',
                            'use_buildin_new_dialog' => false,
                            'use_buildin_set_tag'=>false,
                            'use_buildin_recycle'=>false,

                            'use_buildin_filter'=>true,
                            'base_filter' => $filter,
                            'actions'=>$action,
                        );
   
      $this->finder('logisticsaccounts_mdl_actual_task',$params);
     }

 /**
 * 下载模板
 */
    function export_template(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=实际账单".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $actualObj = $this->app->model('actual');
        $title1 = $actualObj->exportTemplate('export');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";


 }

    /**
     * @新建对账任务
     * @access public
     * @param void
     * @return void
     */
    public function add()
    {
        $Oestimate = logisticsaccounts_estimate::delivery();
        $branch_list =$Oestimate->branch_list();
        $logi_list = $Oestimate->logi_list();

        $this->pagedata['branch_list'] = $branch_list;
        $this->pagedata['logi_list'] = $logi_list;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->page('actual/task/add.html');
    }


   /**
    * @保存对账任务
    * @access public
    * @param void
    * @return bool
    */
    public function save()
    {
        $this->begin();
        $data = $_POST;
        $actual_taskObj = $this->app->model('actual_task');
        $actual_task = $actual_taskObj->dump(array('task_bn'=>$data['task_bn']),'task_id');
        if($actual_task){
            $this->end(false,'任务名称已存在!');
        }
        $result = $actual_taskObj->create($data);

        if($result){
            $this->end(true,'创建成功','index.php?app=logisticsaccounts&ctl=admin_actual_task&act=import&action=import&_finder[finder_id]='.$data['finder_id'].'&finder_id='.$data['finder_id'].'&task_id='.$result);
        }else{
            $this->end(false,'创建失败','index.php?app=logisticsaccounts&ctl=admin_actual_task&act=index&flt=accounted');
        }
    }



    public function import()
    {
        $actual_taskObj = $this->app->model('actual_task');
        /*判断是否是同一个人否则不可以上传*/

        $actual_task = $actual_taskObj->getlist('logi_name,task_id,branch_name,task_bn',array('task_id'=>$_GET['task_id']),0,1);
        $this->pagedata['actual_task'] = $actual_task[0];
        unset($actual_task);

        $this->display('actual/task/import.html');
    }

     /**
     * @关闭账单
     * @access public
     * @param void
     * @return void
     */
    public function close_actual()
    {
        $this->begin('index.php?app=logisticsaccounts&ctl=admin_actual_task&act=index&flt=confirm');
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
        $actualObj = &app::get('logisticsaccounts')->model('actual');
        $task_id = $_GET['task_id'];
        $actual_task_data = array();
        $actual_task_data['task_id'] = $task_id;
        $actual_task_data['status'] = '3';
        $result=$actual_taskObj->update_actual_task($actual_task_data);
        $this->end(true,'关闭成功');
    }

    /**
     * @删除账单
     * @access public
     * @param void
     * @return void
     */
    public function delete()
    {
        $data = $_POST;

        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
        if($data){
            foreach($data['task_id'] as $k=>$v){

                $actual_task = $actual_taskObj->getlist('status,task_bn',array('task_id'=>$v),0,1);

                if($actual_task[0]['status']!='3' && $actual_task[0]['status']!='0'){
                    echo $actual_task[0]['task_bn'].'即不是已关账又不是未记账，不可以删除';
                    exit;
                }
            }
            $task_id = serialize($data['task_id']);
            $this->pagedata['finder_id'] = $_GET['finder_id'];
            $this->pagedata['task_id'] = $task_id;
            $this->page('actual/delete.html');
        }
    }

    /**
     * @批量删除账单
     * @access public
     * @param void
     * @return void
     */
    public function do_delete()
    {
        $this->begin('javascript:finderGroup["'.$_POST['finder_id'].'"].refresh();');
        $data = $_POST;
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');

        $actual_taskObj->delete($data['task_id']);
        $this->end(true,'删除成功');

    }

    /**
    * 获取仓库对应物流公司
    *
    */
    function get_corps(){
        $branch_id = intval($_POST['branch_id']);
        $branchObj = &app::get('ome')->model('branch');
        $branch = $branchObj->get_corp($branch_id);
        echo json_encode($branch);
    }

}
?>