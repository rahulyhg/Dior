<?php
/**
 * WMS 盘点参数验证
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_params_inventory extends erpapi_wms_response_params_abstract
{
    public function add()
    {
        $params = array(
            'inventory_bn'=>array('required'=>'true','type'=>'string','errmsg'=>'盘点单号必填'),
        );

        return $params;
    }
}