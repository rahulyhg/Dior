<?php
class apibusiness_response_remark{
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
        if(empty($sdf['tid'])){
            $this->_respservice->send_user_error("tid is not exist",$sdf);
        }
        
        $shopModel = app::get(self::_APP_NAME)->model('shop');
        $shop = $shopModel->dump(array('node_id'=>base_rpc_service::$node_id));
        if (!$shop) {
            $this->_respservice->send_user_error('shop is not exist',$data);
        }
        $shop['node_version'] = $sdf['node_version'] ? $sdf['node_version'] : $shop['api_version'];
        $sdf['shop'] = $shop;
        
        $class_name = $this->getClassName($sdf,$shop,$tgver);
        $obj = kernel::single($class_name,$sdf);
        
        if (!$obj instanceof apibusiness_response_remark_abstract) {
            $this->_respservice->send_user_error("Class `{$class_name}` is not instance of apibusiness_response_remark_abstract");
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
                             $sdf['tid']);      
        return $data;
    }
    public function setRespservice($respservice){
        $this->_respservice = $respservice;
        return  $this;
    }
    public function getClassName($sdf,$shop,&$tgver){
        $data = array('tid'=>$sdf['tid']);
    
        $dirname = $shop['shop_type'];
        # 获取版本
        $tgver = kernel::single('apibusiness_router_mapping')->getVersion($shop['node_type'],$shop['node_version']);
        do {
            # 如果版本号小于0，直接报错
            if ($tgver<=0) {
                $this->_respservice->send_user_error('no version matched',$data); exit;
            }

            $class_name = sprintf('apibusiness_response_remark_%s_v%s',$dirname,$tgver);

            try{
            # 版本文件存在跳出循环
                if (class_exists($class_name)) break;
            } catch (Exception $e) {
                
            }
            # 当前版本文件不存在，取低版本的
            $tgver--;
        } while (true);
        return $class_name;
    }   
}