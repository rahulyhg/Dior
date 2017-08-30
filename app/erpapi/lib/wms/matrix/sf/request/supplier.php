<?php
/**
 * 供应商推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_sf_request_supplier extends erpapi_wms_request_supplier
{
    protected function _format_supplier_create_params($sdf)
    {
        $params = parent::_format_supplier_create_params($sdf);

        $params['interface_action_code'] = 'NEW';

        return $params;
    }
}