<?php
/**
 * 供应商推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_360buy_request_supplier extends erpapi_wms_request_supplier
{
    /**
     * 不支持供应商同步
     *
     * @return void
     * @author 
     **/
    public function supplier_create($sdf){
        return $this->error('接口方法不存在','w402');
    }
}