<?php
/**
* 盘点单回传参数校验
* @author dqiujing@gmail.com 独孤羽
* @copyright shopex.cn 2013.4.27
*/
class middleware_wms_params_inventory{

    /**
    * 盘点单结果回传参数校验
    * @access public
    * @param Array $params 接口参数
    * @param String $msg 错误消息
    * @param String $msg_code 错误消息
    * @return bool
    */
    public function result($params,&$msg='',&$msg_code=''){

        if(empty($params)){
            $msg = '参数不能为空';
            return false;
        }
        return true;
    }

}