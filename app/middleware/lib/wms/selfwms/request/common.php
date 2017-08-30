<?php
/**
* 发起基类
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_request_common extends middleware_wms_abstract{

    /**
    * 设置业务调用方的异步回调
    *
    */
    public function setUserCallback($callback_class,$callback_method,$callback_params=null){
        $this->callback_class = $callback_class;
        $this->callback_method = $callback_method;
        $this->callback_params = $callback_params;
    }

    /**
    * 调用
    *
    * @access public
    * @param String $wms_class 接口类
    * @param String $wms_method 接口方法
    * @param String $wms_params 接口参数
    * @return Array 标准输出格式
    */
    public function request($wms_class,$wms_method,$wms_params){
        
        if(class_exists($wms_class)){
            $wmsObj = kernel::single($wms_class);
            if(!method_exists($wmsObj,$wms_method)){
                return $this->msgOutput($rsp='fail',$msg='wms_method '.$wms_method.' NOT FOUND');
            }
        }else{
            return $this->msgOutput($rsp='fail',$msg='wms_class '.$wms_class.' NOT FOUND');
        }

        $rs = $wmsObj->$wms_method($wms_params);
        $rsp = isset($rs['rsp']) ? $rs['rsp'] : 'fail';
        $msg = isset($rs['msg']) ? $rs['msg'] : '';
        $msg_code = isset($rs['msg_code']) ? kernel::single('middleware_wms_selfwms_errcode')->getWmsErrCode($rs['msg_code']) : '';
        $data = isset($rs['data']) ? $rs['data'] : array();

        return $this->msgOutput($rsp,$msg,$msg_code,$data);
    }

}