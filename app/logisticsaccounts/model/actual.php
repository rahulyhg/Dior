<?php
class logisticsaccounts_mdl_actual extends dbeav_model{
     var $defaultOrder = array('aid','DESC');
    function io_title( $filter, $ioType='csv' ){
		//账单类型、承运商名称、运单号、重量、到货城市、到货区域、到货地址、实际运费、实际代收款、备注
        switch( $filter ){

            case 'exporttemplate':
                 $this->oSchema['csv'][$filter] = array(
                     '*:物流单号' => 'logi_no',
                    '*:物流称重(KG)' => 'logi_weight',
                    '*:到货城市' => 'ship_city',
                    '*:实际运费' => 'delivery_cost_actual',
                );
                break;
             case 'export':
                  $this->oSchema['csv'][$filter] = array(
                    '*:物流单号' => 'logi_no',
                    '*:物流称重(G)' => 'logi_weight',
                    '*:到货城市' => 'ship_city',
                     '*:账单金额' => 'delivery_cost_actual',
                      '*:预估费用' => 'delivery_cost_expect',
                    '*:记账金额' => 'actual_amount',
                    '*:出库称重' => 'weight',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     /**
		*导出模板
     */
     function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);

        }
        return $title;
    }


    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){

        @ini_set('memory_limit','64M');
        if( !$data['title']['actual']){
            $title = array();
            foreach( $this->io_title('export') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['actual'] = '"'.implode('","',$title).'"';
        }
        $list=$this->getList('logi_no,logi_weight,weight,ship_city,delivery_cost_actual,delivery_cost_expect,actual_amount',$filter,0,-1);

        if(!$list)return false;
        foreach( $list as $aFilter ){
            $aFilter['logi_no'] = $aFilter['logi_no'];
            $aFilter['delivery_cost_actual'] = $aFilter['delivery_cost_actual'];
            $aFilter['delivery_cost_expect'] = $aFilter['delivery_cost_expect'];
            $aFilter['actual_amount'] = $aFilter['actual_amount'];
            //$aFilter['delivery_bn'] = $aFilter['delivery_bn']."\t";
            foreach( $this->oSchema['csv']['export'] as $k => $v ){
                $iostockRow[$k] = $this->charset->utf2local($aFilter[$v]);
            }
            $data['content']['actual'][] = '"'.implode('","',$iostockRow).'"';
        }

    }

    public function exportName(&$data){

        $data['name'] = '对账账单'.date('Ymd');
    }
     function prepared_import_csv(){
        set_time_limit(0);
        $this->ioObj->cacheTime = time();
    	   $this->kvdata = '';
        $this->aa = 0;

    }

     function finish_import_csv(){
        $data = $this->kvdata;
        $queueObj = &app::get('base')->model('queue');
        unset($this->kvdata);

        $sdfs = array();


        foreach ($data['actual']['contents'] as $k => $v){
            $sdf = array();

            $sdf['logi_no']         = trim($v[0]);
            $sdf['logi_weight']          = trim($v[1]);
            $sdf['ship_city']   = trim($v[2]);

            $sdf['delivery_cost_actual']    = trim($v[3]);
            $sdfs[] = $sdf;

        }


            $queueData = array(
                'queue_title'=>'导入账单',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$sdfs,
                    'app' => 'logisticsaccounts',
                    'mdl' => 'actual'
                ),
                'worker'=>'logisticsaccounts_actual_import.run',
            );
            $queue_result = $queueObj->save($queueData);
            $queueObj->flush();
            return null;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){
            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }

        echo implode("\n",$output);
    }
    function searchOptions(){
            $parentOptions = parent::searchOptions();
            $childOptions = array(
                 
                'logi_no'=>$this->app->_('物流单号'),
                'delivery_bn'=>$this->app->_('发货单号'),

            );
            return $Options = array_merge($parentOptions,$childOptions);
    }


    /**
     * @根据id返回物流单详情
     * @access public
     * @param void
     * @return void
     */
    public function detail_actual($aid)
    {

        $sql = 'SELECT a.actual_time,a.actual_name,a.logi_weight,a.delivery_cost_actual,a.actual_amount,a.confirm,a.memo,a.aid,a.task_id,a.weight,a.delivery_cost_expect,a.logi_no,a.ship_city,a.confirm_name,a.confirm_time,a.delivery_bn FROM sdb_logisticsaccounts_actual as a WHERE a.aid='.$aid;
        $db = kernel::database();
        $actual = $db->selectrow($sql);
        $estimate_sql = 'SELECT e.delivery_time,e.order_bn,e.ship_name,e.ship_addr FROM sdb_logisticsaccounts_estimate as e WHERE e.logi_no=\''.$actual['logi_no'].'\' AND e.delivery_bn=\''.$actual['delivery_bn'].'\'';
        
        $estimate = $db->selectrow( $estimate_sql );
        $actual['delivery_time'] = $estimate['delivery_time'];
        $actual['order_bn'] = $estimate['order_bn'];
        $actual['ship_name'] = $estimate['ship_name'];
        $actual['ship_addr'] = $estimate['ship_addr'];
        $taskObj = $this->app->model('actual_task');

        $task = $taskObj->getlist('logi_name,branch_name,status',array('task_id'=>$actual['task_id']),0,1);

        $actual['logi_name'] = $task[0]['logi_name'];
        $actual['branch_name'] = $task[0]['branch_name'];
        $actual['confirm_flag'] = $this->return_confirm($actual['confirm']);
        
        return $actual;
    }

    /**
     * @保存物流账单
     * @access public
     * @param void
     * @return void
     */
    public function save_actual($data){
        $estimateObj = &app::get('logisticsaccounts')->model('estimate');
        $actual_data = array();
        $estimate_data = array();
        $actual_data['aid'] = $data['aid'];
        if($data['actual_amount']){
            $actual_data['actual_amount'] = $data['actual_amount'];
            $estimate_data['actual_amount'] = $data['actual_amount'];

        }
        if($data['memo']){
            $actual_data['memo'] = $data['memo'];
        }
        $check_flag=0;
        if($data['action']=='accounted'){
            if($data['oper']=='doedit'){
                //编辑时只更新备注和金额不变更状态
            }else{
                $actual_data['confirm']='1';
                $actual_data['actual_name']=kernel::single('desktop_user')->get_name();
                $actual_data['actual_time']=time();
                $estimate_data['status'] = '2';
                $estimate_data['confirm'] = '1';
            }
        }else if($data['action']=='confirm'){
            //是否已记账的都审核了

            if($data['oper']=='backconfirm'){//反审核 将物流单打回已记账状态
                $actual_data['confirm']='1';//confirm_time
            }else{
                $actual_data['confirm']='2';//审核
                $actual_data['confirm_name']=kernel::single('desktop_user')->get_name();
                $actual_data['confirm_time']=time();
                $estimate_data['status'] = '3';
                $estimate_data['confirm'] = '2';
            }
        }
        $result = $this->save($actual_data);
        if($result){
            $estimate_data['aid'] = $data['aid'];
            $estimate_data['task_id'] = $data['task_id'];
            $estimateObj->update_estimate_status($estimate_data);
        }
        return $result;
    }

    /**
     * @批量审核
     * @access public
     * @param void
     * @return void
     */
    public function batch_accounted($data,$action,$task_id)
    {
        set_time_limit(0);
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
        if(is_array($data)){
            //更新对应任务状态
           /*更新任务记账总金额*/
           $actual_task_data = array();
           $actual_task_data['task_id'] = $task_id;
           $actual_task_data['aid'] = $data;
           $actual_task_data['status'] ='2';
           $check_flag=2;
           $actual_taskObj->update_actual_task($actual_task_data);
           $this->check_confirm($check_flag,$task_id);
        }
        return true;
    }

   /**
    * @返回物流单状态
    * @access public
    * @param void
    * @return void
    */
   public function return_confirm($confirms)
   {
        $confirm = array(
        '0'=>'未记账',
        '1'=>'已记账',
        '2'=>'已审核',
        '3'=>'已关账',
        );

        return $confirm[$confirms];
   }

   /**
    * @检查某个状态
    * @access public
    * @param void
    * @return void
    */
   public function check_confirm($confirm,$task_id)
   {
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');

        $task = $actual_taskObj->dump(array('task_id'=>$task_id),'actual_name,confirm_name');

        $sql = "SELECT aid FROM sdb_logisticsaccounts_actual WHERE confirm!='$confirm' AND `status` in ('1','2','3') AND task_id=".$task_id;

        $actual = $this->db->select($sql);
        $actual_task = array();
        $actual_task['task_id'] = $task_id;

        if($confirm==1){//记账
            if($actual){
                $actual_task['status']='4';
            }else{
                $actual_task['status']='1';
            }
            if(empty($task['actual_name'])){//如果没有记账人，更新
                $actual_task['actual_name']=kernel::single('desktop_user')->get_name();
            }

        }else if($confirm==2){//审核
            if($actual){
                $actual_task['status']='5';
            }else{
                $actual_task['status']='2';
            }
            if(empty($task['confirm_name'])){//如果没有审核人，更新
                 $actual_task['confirm_name']=kernel::single('desktop_user')->get_name();
            }
        }
        $sql = "SELECT aid FROM sdb_logisticsaccounts_actual WHERE confirm!='0' AND `status` in ('1','2','3') AND task_id=".$task_id;
        $actual_accounts = $this->db->select($sql);
        $actual_task['actual_number'] = count($actual_accounts);
        $actual_taskObj->save($actual_task);


   }

    function modifier_confirm($row){
         
        if($row == '1'){
            return "<div style='width:48px;padding:2px;height:16px;background-color:green;float:left;'><span style='color:#eeeeee;'>已记账</span></div>";
        }else if($row == '2'){
            return '已审核';
        }else if($row == '3'){
            return '已关账';
        }else{
            return '未记账';
        }
    }

  
}

?>