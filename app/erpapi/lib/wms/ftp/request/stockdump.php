<?php
/**
 * 转储单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_ftp_request_stockdump extends erpapi_wms_request_stockdump
{

    /**
     * 转储单创建
     *
     * @return void
     * @author 
     **/
    public function stockdump_create($sdf){
        return $this->error('接口方法不存在','w402');
    } 

    /**
     * 转储单取消
     *
     * @return void
     * @author 
     **/
    public function stockdump_cancel($sdf){
        return $this->error('接口方法不存在','w402');
    } 
}