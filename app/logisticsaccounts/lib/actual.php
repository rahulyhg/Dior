<?php
class logisticsaccounts_actual{
    /**
    *自动对账
    */

    function auto($data,$task_id){
        $estimateObj = &app::get('logisticsaccounts')->model('estimate');
        $actualObj = &app::get('logisticsaccounts')->model('actual');
        if($data){

            foreach( $data as $k=>$v ){
                $actual = $actualObj->dump(array('aid'=>$v),'logi_weight,status,logi_no,delivery_cost_actual,task_id');
                $estimate = $estimateObj->dump(array('logi_no'=>$actual['logi_no']),'eid,ship_name,status,delivery_cost_expect,delivery_time,delivery_bn,order_bn,weight');
                if($estimate){
                    $actual_data = array();
                    $actual_data['aid'] = $v;
                    $actual_data['delivery_time'] = $estimate['delivery_time'];
                    $actual_data['weight'] = $estimate['weight'];
                    $actual_data['delivery_bn'] = $estimate['delivery_bn'];
                    $actual_data['order_bn'] = $estimate['order_bn'];
                    $actual_data['ship_name'] = $estimate['ship_name'];
                    if($actual['status']!='4'){
                        $actual_data['delivery_cost_expect'] = $estimate['delivery_cost_expect'];
                        if($estimate['delivery_cost_expect']==$actual['delivery_cost_actual']){
                            $actual_data['status'] = '1';
                        }else if($actual['delivery_cost_actual']<$estimate['delivery_cost_expect']){
                            $actual_data['status'] = '2';
                        }else if($actual['delivery_cost_actual']>$estimate['delivery_cost_expect']){
                            $actual_data['status'] = '3';
                        }

                        $estimate_data = array();
                        $estimate_data['eid'] = $estimate['eid'];
                        $estimate_data['status'] = '1';
                        $estimate_data['aid'] = $v;

                        $estimate_data['task_id'] = $actual['task_id'];
                        $estimate_data['delivery_cost_actual'] = $actual['delivery_cost_actual'];
                        $estimate_data['logi_weight'] = $actual['logi_weight'];

                        $estimateObj->save($estimate_data);
                    }

                    $result = $actualObj->save( $actual_data );
              }
        }
            //更新所在任务预估预收总费用
           $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
           $actual_task_data = array();
           $actual_task_data['task_id'] = $task_id;
            $actual_task_data['update_money']=1;
           $actual_taskObj->update_actual_task($actual_task_data);
        }

      return true;
     }

    /**
     * @根据状态返回物流费用和包裹数
     * @access public($task_id,'',$confirm,$aid)
     * @param status 0 未匹配 1 已匹配 2 高预估 3 低预估 4 已记账
     * @param confirm 0未记账 1已记账 2 已审核
     * @return array
     */
    public function get_status_list($task_id,$status,$confirm='',$aid=''){
        $sqlstr = '';
        if($aid){
            $aid = implode(',',$aid);
            $sqlstr.=' AND aid in ('.$aid.')';
        }
        if($status || $status=='0'){
            if (is_array($status)) {
                $status = '\''.implode('\',\'',$status).'\'';
                $sqlstr.=' AND `status` in ('.$status.')';
            } else {
                $sqlstr.=' AND `status`=\''.$status.'\'';
            }
        }
        if($confirm){
            if(is_array($confirm)){
                $confirm = '\''.implode('\',\'',$confirm).'\'';
            }
            $sqlstr.=' AND confirm in ('.$confirm.')';
        }
        $db = kernel::database();
        $sql = 'SELECT count(task_id) as count,sum(delivery_cost_actual) as total_delivery_cost_actual,sum(actual_amount) as total_actual_amount,sum(delivery_cost_expect) as total_delivery_cost_expect FROM sdb_logisticsaccounts_actual WHERE task_id='.$task_id.$sqlstr;

        $result = $db->selectrow($sql);
        return $result;
    }

    /**
     * @获取当前所有状态总结果
     * @access public
     * @param void
     * @return void
     */
    public function get_allstatus($task_id,$confirm='',$aid='',$status=''){
        $actual = array();
        //全部开始
        //汇总及金额
        $all = $this->get_status_list($task_id,$status,$confirm,$aid);
        $actual['all'] = $all;
        
        
        //-------全部
        //未匹配数及金额
        $no_match = $this->get_status_list($task_id,'0',$confirm,$aid);
        $actual['no_match'] = $no_match;
        //-------未匹配

        //已匹配数及金额
        $hasmatched = $this->get_status_list($task_id,'1',$confirm,$aid);
        $actual['hasmatched'] = $hasmatched;
        
        
        //-------已匹配
        //比预估多数及金额
        $overpayment = $this->get_status_list($task_id,'3',$confirm,$aid);
        $actual['overpayment'] = $overpayment;
        
        
        
        //-------比预估多
        //比预估少及金额
        $lesspayment = $this->get_status_list($task_id,'2',$confirm,$aid);
        $actual['lesspayment'] = $lesspayment;
        
        
        
        //-------比预估少
        //已记账数及金额
        $accounted = $this->get_status_list($task_id,'4',$confirm,$aid);
        $actual['accounted'] = $accounted;
        
        //有效账单金额
        $actual['effective'] = $hasmatched['count']+$overpayment['count']+$lesspayment['count'];
        $actual['effective_money_expect'] = $actual['hasmatched']['total_delivery_cost_expect']+$actual['overpayment']['total_delivery_cost_expect']+$actual['lesspayment']['total_delivery_cost_expect'];
        $actual['effective_money_actual'] = $actual['hasmatched']['total_delivery_cost_actual']+$actual['overpayment']['total_delivery_cost_actual']+$actual['lesspayment']['total_delivery_cost_actual'];
        $actual['effective_diff_money'] = $actual['effective_money_expect'] - $actual['effective_money_actual'];

        return $actual;
    }

    /**
     * @汇总物流账单
     * @access public
     * @param void
     * @return void
     */
    public function summary_actual_money($task_id,$aid='',$status='',$confirm=''){
        $result = $this->get_status_list($task_id,'','',$aid);
        return $result;
    }

    /**
     * @批量记账
     * @access public
     * @param aid array
     * @return void
     */
    public function batch_accounted($task_id,$aid,$adjust_money,$accounted_type){
        set_time_limit(0);
        $acutalObj = &app::get('logisticsaccounts')->model('actual');
        $estimateObj = &app::get('logisticsaccounts')->model('estimate');
        //以仓库+物流公司+id作标识
        $actual_task = &app::get('logisticsaccounts')->model('actual_task')->dump($task_id,'logi_id,branch_id');
        $db = kernel::database();
        if($aid){
            $aids = implode(',',$aid);
            $sqlstr.=' AND aid in ('.$aids.')';
        }
        if ($accounted_type) {
            $sqlstr.=' AND `confirm`=\'0\'';
        }
        $sql = 'SELECT sum(delivery_cost_actual) as total_delivery_cost_actual,count(aid) as count FROM sdb_logisticsaccounts_actual WHERE task_id='.$task_id.$sqlstr.' AND `status` in (\'1\',\'2\',\'3\')';

        $actual_sum = $db->selectrow($sql);
        if($adjust_money){
            if ($actual_sum['count']>0) {
                $total_package = $actual_sum['count'];
                $total_delivery_cost_actual = $actual_sum['total_delivery_cost_actual'];
                $difference = $total_delivery_cost_actual-$adjust_money;

                $avg_money = floor($difference/$total_package*100)/100;

                $has_money = $avg_money*$total_package;//已经均摊的金额
                $sub_money = $difference-$has_money;//剩余金额
            }else{
                $avg_money = 0;
                return false;
            }
        }else{
            $avg_money = 0;
        }
        $actualsql = 'UPDATE sdb_logisticsaccounts_actual SET actual_amount=delivery_cost_actual-'.$avg_money.',confirm=\'1\',actual_name=\''.kernel::single('desktop_user')->get_name().'\',actual_time='.time().' WHERE task_id='.$task_id.$sqlstr.' AND status in (\'1\',\'2\',\'3\')';

        $db->exec($actualsql);
        if($avg_money){//随便找一个将余数加过去
             $sub_actual = $db->select('SELECT aid FROM sdb_logisticsaccounts_actual WHERE task_id='.$task_id.$sqlstr.' AND status in (\'1\',\'2\',\'3\') LIMIT 1');
            $sub_aid = $sub_actual[0]['aid'];
            if($sub_money){

                if($sub_aid){
                    $db = kernel::database();
                    $db->exec('UPDATE sdb_logisticsaccounts_actual SET actual_amount=actual_amount-'.$sub_money.' WHERE aid='.$sub_aid);
                }

           }
           //
           app::get('logisticsaccounts')->setConf('logisticsaccounts.accounted.money.'.$actual_task[0]['branch_id'].'_'.$actual_task[0]['logi_id'].'_'.$task_id,$adjust_money);
       }
        $actual_list = $db->select('SELECT aid,confirm,actual_amount,task_id FROM sdb_logisticsaccounts_actual WHERE task_id='.$task_id.$sqlstr.' AND status in (\'1\',\'2\',\'3\')');
        foreach($actual_list as $k=>$v){

            $estimateObj->update_estimate_status($v);
        }
       $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
       $actual_task_data = array();
       $actual_task_data['task_id'] = $task_id;
       $actual_task_data['update_money']=1;
       $actual_taskObj->update_actual_task($actual_task_data);
       $acutalObj->check_confirm(1,$task_id);
       return true;
    }
}

?>