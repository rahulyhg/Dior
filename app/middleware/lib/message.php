<?php
/**
* 消息类
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_message {

    /**
    * 标准输出格式
    * @access public
    * @param String $rsp 状态:fail(失败)、success(成功)、warning(警告)
    * @param String $msg 消息
    * @param String $msg_code 错误代码
    * @param Array $data 数据
    * @return Array
    */
    public function output($rsp='fail', $msg=null, $msg_code=null, $data=null){
        $rs = array(
            'rsp' => $rsp,
            'msg' => $msg,
            'msg_code' => $msg_code,
            'data' => $data,
        );
        return $rs;
    }

    /**
    * 获取wms的错误编码
    *
    * @access public
    * @return mixed
    */
    public static function getWmsErrCodeList(){
        $errcode = include 'wms/errcode.php';
        return $errcode;
    }

}