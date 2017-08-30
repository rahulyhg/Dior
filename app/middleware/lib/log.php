<?php
/**
* 日志类
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_log {

    function __construct(){
        $this->logMdl = app::get('ome')->model('api_log');
    }

    /**
    * 获取日志ID
    *
    * @access public
    * @param bool 
    * @return 
    */
    public function getLogId(){
        return $this->logMdl->gen_id();
    }

    /**
    * 添加日志
    *
    * @access public
    * @param bool 
    * @return 
    */
    public function writeLog($log_id,$log_title,$retry_class,$retry_method,$retry_params,$memo='',$api_type='request',$status='fail',$msg='请求中',$addon='',$log_type='other',$original_bn=''){
        return $this->logMdl->write_log($log_id,$log_title,$retry_class,$retry_method,$retry_params,$memo,$api_type,$status,$msg,$addon,$log_type,$original_bn);
    }

    /**
    * 更新日志
    *
    * @access public
    * @return
    */
    public function updateLog($log_id,$msg=NULL,$status=NULL,$params=NULL,$addon=NULL){
        return $this->logMdl->update_log($log_id,$msg,$status,$params,$addon);
    }

    /**
    * 查询日志信息
    *
    * @access public
    * @return
    */
    public function dump($filter,$field = '*'){
        $detail = $this->logMdl->dump($filter,$field);
        return isset($detail) ? $detail: NULL;
    }

    /**
    * 判断是否重复
    *
    * @access public
    * @return
    */
    public function is_repeat($key){
        return $this->logMdl->is_repeat($key);
    }
    /**
    * 查询日志信息
    *
    * @access public
    * @return
    */
    public function set_repeat($key,$value = ''){
        return $this->logMdl->set_repeat($key,$value);
    }

}