<?php
class apibusiness_response_logistics{
    private $_respservice = null;
    const _APP_NAME = 'ome';

    /**
     * 订单方法跳转
     *
     * @return void
     * @author 
     **/
    public function dispatch($method,$sdf){
        if (!base_rpc_service::$node_id) {
            $this->_respservice->send_user_error('no node id',$sdf);
        }
        if(empty($sdf['LogisticCode'])){
            $this->_respservice->send_user_error("LogisticCode is not exist",$sdf);
        }
        $type ='hqepay';#写死为华强宝类型
        $class_name = $this->getClassName($type ,$tgver);
        $obj = kernel::single($class_name,$sdf);

        if (!$obj instanceof apibusiness_response_logistics_abstract) {
            $this->_respservice->send_user_error("Class `{$class_name}` is not instance of apibusiness_response_logistics_abstract");
        }
        if (!method_exists($obj, $method)) {
            $this->_respservice->send_user_error("method `{$method}` is not exist",$data);
        }
        $obj->setRespservice($this->_respservice)->setTgVer($tgver)->$method();
        
        $logModel = app::get('ome')->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,
                             $obj->_apiLog['title'],
                             get_class($this), 
                             $method, 
                             '', 
                             '', 
                             'response', 
                             'success', 
                             implode('<hr/>',$obj->_apiLog['info']),
                             '',
                             '',
                             $sdf['LogisticCode']);      
        return $data;
    }
    public function setRespservice($respservice){
        $this->_respservice = $respservice;
        return  $this;
    }
    public function getClassName($type ='hqepay',&$tgver){
        if(empty($hqepay)){
            $type = 'hqepay';
        }
        #获取版本
        $tgver = kernel::single('apibusiness_router_mapping')->getVersion(null,null);
        do {
            #如果版本号小于0，直接报错
            if ($tgver<=0) {
                $this->_respservice->send_user_error('no version matched',$data); exit;
            }
            $class_name = sprintf('apibusiness_response_logistics_%s_v%s',$type,$tgver);
            try{
                #版本文件存在跳出循环
                 if (class_exists($class_name)) {
                    break;
                }
            } catch (Exception $e) {
                
            } 
            #当前版本文/件不存在，取低版本的
            $tgver--;
        } while (true);
        return $class_name;
    }    
}