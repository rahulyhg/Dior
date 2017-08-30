<?php
/**
* 错误代码类
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_errcode{

    private $_errcode = array(
        's200' => array('comment'=>'接口不支持同步','wms_errcode'=>'w403'),
    );

    /**
    * 转换wms的通用错误编码
    *
    * @access public
    * @param String $err_code 错误码
    * @return mixed
    */
    public function getWmsErrCode($err_code=NULL){
        if ($wmsErrcode = $this->_errcode[$err_code]['wms_errcode']){
            return $wmsErrcode;
        }else{
            return $err_code;
        }
    }

}