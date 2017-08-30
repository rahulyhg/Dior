<?php
/**
 * 入库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_flux_request_stockin extends erpapi_wms_request_stockin
{
    public function _format_stockin_create_params($sdf)
    {
        $params = parent::_format_stockin_create_params($sdf);

        $params['warehouse_code'] = $this->get_warehouse_code($sdf['branch_bn']);

        return $params;   
    }

    protected function _format_stockin_cancel_params($sdf)
    {
        $params = parent::_format_stockin_cancel_params($sdf);

        $params['warehouse_code'] = $this->get_warehouse_code($sdf['branch_bn']);
        
        return $params;
    }
}