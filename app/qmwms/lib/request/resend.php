<?php 
/**
 * 请求奇门接口失败的单据
 * 间隔30分钟后重新发送接
 * 口请求
 */
class qmwms_request_resend{

    //重发机制
    public function resend(){
        $log_mdl = app::get('qmwms')->model('qmrequest_log');
        $timeStamp = time()-30*60;//重新发送半小时之前的请求接口失败的单据
        $sql = sprintf("select * from sdb_qmwms_qmrequest_log  where try_num < 5 and last_modified <= %s and status <> 'success' and original_id is not null ",$timeStamp);
        $response = kernel::database()->select($sql);
        foreach($response as $key => $item){
            $response = kernel::single('qmwms_request_abstract')->request($item['original_params'],$item['task_name']);
            switch($item['task_name']){
                case 'deliveryorder.create':
                    $_response = kernel::single('qmwms_request_omsqm')->res_params($response,$item['original_id'],'deliveryOrderCreate');
                    $data = array(
                        'try_num'=>$item['try_num']+1,
                        'response'=>$response,
                        'status'=>$_response['status'],
                        'res_msg'=>$_response['res_msg']
                    );
                    $log_mdl->update($data,array('log_id'=>$item['log_id']));
                    break;
                case 'returnorder.create':
                    $_response = kernel::single('qmwms_request_omsqm')->res_params($response,$item['param_sx'],'returnOrderCreate',$item['original_id']);
                    $data = array(
                        'try_num'=>$item['try_num']+1,
                        'response'=>$response,
                        'status'=>$_response['status'],
                        'res_msg'=>$_response['res_msg'],
                    );
                    $log_mdl->update($data,array('log_id'=>$item['log_id']));
                    break;
                case 'order.cancel':
                    $_response = kernel::single('qmwms_request_omsqm')->res_params($response,null,null);
                    $data =array(
                        'try_num'=>$item['try_num']+1,
                        'response'=>$response,
                        'status'=>$_response['status'],
                        'res_msg'=>$_response['res_msg'],
                    );
                    $log_mdl->update($data,array('log_id'=>$item['log_id']));
                    break;
                default:
                    break;
            }
        }
    }






}