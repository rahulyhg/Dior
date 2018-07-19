<?php 
/**
 * 请求奇门接口失败的单据
 * 间隔30分钟后重新发送接
 * 口请求
 */
class qmwms_request_resend{

    /**
     * @param $idsArr
     * 页面手动重新发送接口请求
     */
    public function resent($idsArr){
        $requestLog = app::get('qmwms')->model('qmrequest_log');
        $requestData = $requestLog->getList('*',array('log_id'=>$idsArr));

        foreach($requestData as $value){
            $log_id = $value['log_id'];
            $method = $value['task_name'];
            switch($method){
                case 'deliveryorder.create':
                    $order_id = $value['original_id'];
                    kernel::single('qmwms_request_omsqm')->deliveryOrderCreate($order_id);
                    break;
                case 'returnorder.create':
                    $reship_id   = $value['original_id'];
                    $objReship = app::get('ome')->model('reship')->getList('order_id',array('reship_id'=>$reship_id));
                    $deliverOrder = app::get('ome')->model('delivery_order')->getList('delivery_id',array('order_id'=>$objReship[0]['order_id']));
                    $delivery_id = $deliverOrder[0]['delivery_id'];
                    $return_type = $value['param1'];
                    $change = unserialize($value['param2']);

                    kernel::single('qmwms_request_omsqm')->returnOrderCreate($delivery_id,$reship_id,$return_type,$change);
                    break;
                case 'order.cancel':
                    $delivery_id = $value['original_id'];
                    kernel::single('qmwms_request_omsqm')->orderCancel($delivery_id);
                    break;
                default:
                    break;
            }
            $requestLog->delete(array('log_id'=>$log_id));
        }

    }

    /**
     * @param $response
     * @return bool
     * 检查string是否是xml
     */
    public function check_xml($response){
        $is_xml = true;
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$response,true)){
            xml_parser_free($xml_parser);
            $is_xml = false;
        }
        return $is_xml;
    }

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
                    $_response = kernel::single('qmwms_request_omsqm')->res_params($response,$item['param1'],'returnOrderCreate',$item['original_id']);
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