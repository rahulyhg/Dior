<?php
class logisticsaccounts_actual_import{

    function run(&$cursor_id,$params){

        $actualObj = &app::get('logisticsaccounts')->model('actual');
        $estimateObj = &app::get('logisticsaccounts')->model('estimate');
        $actual_taskObj = &app::get('logisticsaccounts')->model('actual_task');
        $Oestimate = logisticsaccounts_estimate::delivery();
        $actual_data=array();
        $task_id = $params['sdfdata']['task_id'];
        $task_bn = $params['sdfdata']['task_bn'];
        
        foreach($params['sdfdata']['actual'] as $k=>$v){
            $delivery_cost_actual = $v['delivery_cost_actual']/1;
            $data = array();
            $data['logi_no']         = $v['logi_no'];
            $data['logi_weight']          = $v['logi_weight']*1000;
            $data['ship_city']   = $v['ship_city'];
            $data['delivery_cost_actual']    = $delivery_cost_actual;
            $data['task_id']       = $task_id;
            $data['task_bn']       = $task_bn;
            $actual = $actualObj->getlist('aid,status,confirm,actual_amount,task_id',array('logi_no'=>$v['logi_no']),0,1);
            $estimate = $estimateObj->dump(array('logi_no'=>$v['logi_no']),'eid,ship_name,status,delivery_cost_expect,delivery_time,delivery_bn,order_bn,weight');
            if($estimate){
                $data['delivery_time'] = $estimate['delivery_time'];
                $data['weight'] = $estimate['weight'];
                $data['delivery_bn'] = $estimate['delivery_bn'];

                $data['ship_name'] = $estimate['ship_name'];
       
                $data['delivery_cost_expect'] = $estimate['delivery_cost_expect'];
                if($estimate['delivery_cost_expect']==$delivery_cost_actual){
                    $data['status'] = '1';
                }else if($delivery_cost_actual<$estimate['delivery_cost_expect']){
                    $data['status'] = '2';
                }else if($delivery_cost_actual>$estimate['delivery_cost_expect']){
                    $data['status'] = '3';
                }
                //差额
                $data['delivery_cost_diff'] = kernel::single('eccommon_math')->number_minus(array($v['delivery_cost_actual'],$estimate['delivery_cost_expect']));
                if($actual){

                    if($task_id==$actual[0]['task_id']){
                        #同一任务，已存在未记账更新
                        if(($actual[0]['confirm']=='0')){
                            $data['aid'] = $actual[0]['aid'];
                        }else if(($actual[0]['confirm']=='1')){
                            #同一任务，对账状态已记账,记账状态已记账更新
                            if ( $actual[0]['status']=='4' ) {
                                $data['aid'] = $actual[0]['aid'];
                            }
                            $data['confirm'] = '1';
                            $data['status'] = '4';
                            $data['actual_amount'] = $actual[0]['actual_amount'];
                        }
                    }else{
                        #不同任务，已存在已记账可以保存
                        if($actual[0]['confirm']!='0'){

                            $data['status']         = '4';
                            $data['confirm']        = '1';
                            $data['actual_amount'] = $actual[0]['actual_amount'];
                        }
                    }
                }else{
                    if($estimate){#匹配到更新预估单状态当物流账单首次查询存在
                        $estimate_data = array();
                        $estimate_data['eid'] = $estimate['eid'];
                        $estimate_data['status'] = '1';

                        $estimate_data['task_id'] = $task_id;
                        $estimate_data['delivery_cost_actual'] = $delivery_cost_actual;
                        $estimate_data['logi_weight'] = $data['logi_weight'];
                    }
                }
            }

            $result = $actualObj->save($data);
            if($estimate){
                if($result){
                    if($estimate_data){
                        $estimate_data['aid'] = $data['aid'];
                        $estimateObj->save($estimate_data);
                    }
                }
            }

    }

   $actual_task_data = array();
   $actual_task_data['task_id'] = $task_id;
   $actual_task_data['update_money']=1;
   $actual_taskObj->update_actual_task($actual_task_data);

    return false;

 }



}


?>