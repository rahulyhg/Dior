<?php
/**
* 接收基类
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_response_common extends middleware_wms_abstract{

    /**
    * 接收
    *
    * @access public
    * @param String $method 业务方法
    * @param String $params 业务参数
    * @return Array 标准输出格式
    */
    public function response($method,&$params){
        
        $wms_class = 'middleware_iostock';
        $wms_method = $method;
        $wmsObj = kernel::single($wms_class);
        if(!method_exists($wmsObj,$wms_method)){
            return $this->msgOutput($rsp='fail',$msg='wms_method '.$wms_method.' NOT FOUND');
        }
        
        #调用业务方法
        $rs = $wmsObj->$wms_method($params);
        $rsp = isset($rs['rsp']) ? $rs['rsp'] : 'fail';
        $msg = isset($rs['msg']) ? $rs['msg'] : '';
        $msg_code = isset($rs['msg_code']) ? $rs['msg_code'] : '';
        $data = isset($rs['data']) ? $rs['data'] : array();

        return $this->msgOutput($rsp,$msg,$msg_code,$data);
    }

}