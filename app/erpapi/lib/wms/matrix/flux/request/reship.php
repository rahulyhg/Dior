<?php
/**
 * 退货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_flux_request_reship extends erpapi_wms_request_reship
{
    protected function _format_reship_create_params($sdf)
    {
        $params = parent::_format_reship_create_params($sdf);

        $params['warehouse_code'] = $this->get_warehouse_code($sdf['branch_bn']);

        return $params;
    }
}