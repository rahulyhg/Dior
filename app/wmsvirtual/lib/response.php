<?php
class wmsvirtual_response{
    public function dispatch($adapter_type,$method,&$params,$node_id){
        try{
            $dispatch_class_name = 'rpc_'.$adapter_type;
            if(class_exists($dispatch_class_name)){
                $funcObj = kernel::single('rpc_func');
                $repeat = false;
                #内部sdf参数转换
                $dispatch_instance = kernel::single($dispatch_class_name);


                list($adapter_method,$adapter_params,$write_log) = $dispatch_instance->convert($method,$params);
                
                #日志记录
                $log_id = $funcObj->write_log($params,$write_log,$repeat);

                
                
                #调用适配器接口方法
                $adapter_instance = $funcObj->getResponseAdapter($adapter_type,$node_id);
                $adapter_params['node_id'] = $node_id;
                $rs = $adapter_instance->$adapter_method($adapter_params);
               
                $rs['rsp'] = isset($rs['rsp']) && $rs['rsp'] == 'succ' ? 'success' : $rs['rsp'];

                // 更新日志success
                $funcObj->update_log($log_id,$rs['rsp'],$rs['msg']);

                return $rs;
            }
        }catch(Exception $e){
            return false;
        }
    }
}
?>