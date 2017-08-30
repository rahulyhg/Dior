<?php
/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_arvato_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        $params = parent::_format_delivery_create_params($sdf);

        $params['logical_code'] = $this->get_warehouse_code($sdf['branch_bn']);

        $params['receiver_time'] = date('Y-m-d H:i:s');
        return $params;
    }

    protected function _format_delivery_cancel_params($sdf)
    {
        $params = parent::_format_delivery_cancel_params($sdf);

        $params['logical_code'] = $this->get_warehouse_code($sdf['branch_bn']);

        $params['order_type'] = '10';

        unset($params['warehouse_code']);
        return $params;
    }
}