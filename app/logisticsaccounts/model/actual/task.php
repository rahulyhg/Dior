<?php
class logisticsaccounts_mdl_actual_task extends dbeav_model
{
    var $defaultOrder = array('task_id','DESC');
    /**
     * @对账批次号
     * @access public
     * @param void
     * @return void
     */
    public function gen_id()
    {
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $task_bn = 'dz'.date('YmdH').'10'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT task_id from sdb_logisticsaccounts_actual where task_bn ='.$task_bn);
        }while($row);
        return $task_bn;
    }

    /**
     * @保存对账任务
     * @access public
     * @param void
     * @return void
     */
    public function create($data)
    {
        $Oestimate = logisticsaccounts_estimate::delivery();

        $branch = $Oestimate->get_branch($data['branch_id']);
        $logi = $Oestimate->get_logi($data['logi_id']);
        $task_bn = $branch['name'].'_'.$logi['name'].'_'.date('Ymd');
        $data = array(
            'task_bn'=>$data['task_bn'],
            'logi_id'=>$data['logi_id'],
            'branch_id'=>$data['branch_id'],
            'add_time'=>time(),
            'branch_name'=>$branch['name'],
            'logi_name'=>$logi['name'],
            'op_id'=>kernel::single('desktop_user')->get_id()

        );

        $result = $this->save($data);
        if($result){
        return $data['task_id'];
        }else{
            return false;
        }
    }

     function io_title( $filter, $ioType='csv' ){
  //账单类型、承运商名称、运单号、重量、到货城市、到货区域、到货地址、实际运费、实际代收款、备注
        switch( $filter ){
            case 'import':
                $this->oSchema['csv'][$filter] = array(
                    '*:物流单号' => 'logi_no',
                    '*:物流称重(KG)' => 'logi_weight',
                    '*:到货城市' => 'ship_city',
                    '*:实际运费' => 'delivery_cost_actual',

                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }



     function prepared_import_csv(){
        set_time_limit(0);
        $this->ioObj->cacheTime = time();
        $this->kvdata = '';
        $this->aa = 0;
    }

     function finish_import_csv(){
         set_time_limit(0);
        @ini_set('memory_limit','640M');
        $data = $this->kvdata;
        $queueObj = &app::get('base')->model('queue');
        unset($this->kvdata);
        $number = 0;
        $page = 0;
        $limit = 50;
        $sdfs = array();

        $psdfs['task_id'] = $_POST['task_id'];
        $psdfs['task_bn'] = $_POST['task_bn'];
        foreach ($data['actual']['contents'] as $k => $v){
            $sdf = array();

            $sdf['logi_no']         = trim($v[0]);
            $sdf['logi_weight']          = $v[1];
            $sdf['ship_city']   = $v[2];

            $sdf['delivery_cost_actual']    = $v[3];

             $sdfs[$page][] = $sdf;

        }
            foreach ($sdfs as $i){
                $psdfs['actual']  = $i;
                $queueData = array(
                    'queue_title'=>'导入账单',
                    'start_time'=>time(),
                    'params'=>array(
                        'sdfdata'=>$psdfs,
                        'app' => 'logisticsaccounts',
                        'mdl' => 'actual'
                    ),
                    'worker'=>'logisticsaccounts_actual_import.run',
                );
                $queue_result = $queueObj->save($queueData);
                $queueObj->flush();
                return null;
            }
    }

    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        $this->aa++;
        $fileData = $this->kvdata;
        $task_id = $_POST['task_id'];
        if( !$fileData ) $fileData = array();

        if($row){

            if( substr($row[0],0,1) == '*' ){
                $titleRs =  array_flip($row);
                $mark = 'title';
                if(!$this->check_csv($titleRs)){
                    $msg['error'] = "导入的csv文件字段与本操作所需不符，请使用正确的csv文件。";
                }
                return $titleRs;
            }else{


                  if(trim($row[0])==''){
                    $msg['error']='物流单号不可为空!';
                    return false;
                  }else if(trim($row[3])==''){
                    $msg['error']='实际运费不可为空!';
                    return false;
                  }
                  else{
                    $actualObj = &app::get('logisticsaccounts')->model('actual');
                    $actual = $actualObj->getlist('aid,confirm,task_id,status',array('logi_no'=>$row[0]),0,-1);

                    foreach($actual as $k=>$v){
                        if($v['task_id']==$task_id){
                            if($v['confirm']!='0'){
                                if($v['status']!='4'){
                                    $msg['error']=$row[0].'已记账,无法再继续上传，请新建其他批次';
                                    return false;
                                }
                            }
                        }else{
                            if($v['confirm']=='0'){
                                if($v['status']!='0'){
                                    $msg['error']=$row[0].'号已存在且未记账,不可以再上传!';
                                    return false;
                                }
                            }
                        }
                    }


                  }

                $fileData['actual']['contents'][] = $row;

            }

        $this->kvdata = $fileData;
        }
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){

        return null;
    }

     function check_csv($title){
        $arrFrom = array_flip(array_filter(array_flip($title)));
        $this->io_title('main');
        $arrFieldsAll = array_merge($this->oSchema['csv']['main'],$this->oSchema['csv']['item']);
        $arrResult = array_diff_key($arrFrom,$arrFieldsAll);
        return empty($arrResult) ?  true : false;
    }

    /**
     * @更新对账任务状态
     * @access public
     * @param void
     * @return void
     */
    public function update_actual_task($data)
    {
        $task_data = array();
        $actualObj = &app::get('logisticsaccounts')->model('actual');
        $task_data['task_id'] = $data['task_id'];
        if($data['status']){
            $task_data['status']=$data['status'];

        }
        if($data['update_money']){
            $summary_actual = kernel::single('logisticsaccounts_actual')->summary_actual_money($data['task_id']);
            $actual_accounts = $this->db->selectrow("SELECT sum(actual_amount) as actual_amount FROM sdb_logisticsaccounts_actual WHERE confirm!='0' AND `status` in ('1','2','3') AND task_id=".$data['task_id'].'');
            $task_data['actual_amount'] = $actual_accounts['actual_amount'];
            $task_data['delivery_cost_actual'] = $summary_actual['total_delivery_cost_actual'];
            $task_data['delivery_cost_expect'] = $summary_actual['total_delivery_cost_expect'];

            $actual = $actualObj->getlist('aid',array('task_id'=>$data['task_id']),0,-1);
            $task_data['actual_total']=count($actual);

        }
        $result = $this->save($task_data);
        if($result){
            if($data['status']=='3'){//关账成功后
                    $actual_sql = 'UPDATE sdb_logisticsaccounts_actual SET confirm=\'3\' WHERE task_id='.$data['task_id'].' AND `status` in (\'1\',\'2\',\'3\')';
                    $estimate_sql = 'UPDATE sdb_logisticsaccounts_estimate SET status=\'4\' WHERE task_id='.$data['task_id'];
                    $this->db->exec($actual_sql);
                    $this->db->exec($estimate_sql);

            }else if($data['status']=='2'){//审核

                    if($data['aid']){
                        $aids = implode(',',$data['aid']);
                        $sqlstr.=' AND aid in ('.$aids.')';
                    }
                    $confirm_name=kernel::single('desktop_user')->get_name();
                    $actual_sql = 'UPDATE sdb_logisticsaccounts_actual SET confirm=\'2\',confirm_name=\''.$confirm_name.'\',confirm_time='.time().' WHERE task_id='.$data['task_id'].' AND `status` in (\'1\',\'2\',\'3\') '.$sqlstr;
                    $estimate_sql = 'UPDATE sdb_logisticsaccounts_estimate SET status=\'3\',confirm_name=\''.$confirm_name.'\' WHERE task_id='.$data['task_id'].$sqlstr;
                    $this->db->exec($actual_sql);
                    $this->db->exec($estimate_sql);
             }
         }
        if($data['update_money']){
            return $actual_accounts['actual_amount'];
        }else{
            return $result;
        }

    }

   public function _filter($filter, $tableAlias=null, $baseWhere=null) {
       
        $is_super = kernel::single('desktop_user')->is_super();
          if (!$is_super) {
                $op_id = kernel::single('desktop_user')->get_id();
                if ($op_id) {
                    if (!$filter['op_id']) {
                        $filter['op_id'] = $op_id;
                    }else{
                        if($filter['op_id']=='all'){
                            unset($filter['op_id']);
                        }
                    }
                }
            }else{
                if($filter['op_id']=='all'){
                            unset($filter['op_id']);
                 }
            }

        return parent::_filter($filter, $tableAlias, $baseWhere) . $where;
    }

    /**
     * @删除账单任务
     * @access public
     * @param array task_id
     * @return bool
     */
    public function delete($data)
    {

        $data = unserialize($data);

        foreach($data as $k=>$v){
            $result = $this->db->exec('delete from sdb_logisticsaccounts_actual_task WHERE task_id='.$v);
            if($result){
                $this->db->exec('delete from sdb_logisticsaccounts_actual WHERE task_id='.$v);
            }
        }
        return true;
    }


}

