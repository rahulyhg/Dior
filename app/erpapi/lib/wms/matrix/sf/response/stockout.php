<?php
/**
 * 出库单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_sf_response_stockout extends erpapi_wms_response_stockout
{

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        // 顺风销售出库
        $stockout_bn = $params['stockout_bn'];
        if ('OS' == substr($stockout_bn, 0, 2)) {
            return $this->convert_delivery_params($params);
        }
        return parent::status_update($params);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function convert_delivery_params($params)
    {
        $delivery = array(
            'delivery_bn'     => substr($params['stockout_bn'], 2),
            'logi_no'         => $params['logi_no'],
            'logistics'       => $params['logistics']=='顺丰速运' ? 'SF' : $params['logistics'],
            'warehouse'       => $params['warehouse'],
            'operate_time'    => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
            'out_delivery_bn' => $params['out_delivery_bn'],
            'status'          => $params['status'] == 'FINISH' ? 'DELIVERY' : $params['status'],
            'item'            => $params['item'],
            'remark'          => '',
            'volume'          => '',
            'weight'          => '',
            // 'io_bn'           => $params['stockout_bn'],
        );
        
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '发货单' . $delivery['delivery_bn'];
        $this->__apilog['original_bn'] = $delivery['delivery_bn'];

        $data = kernel::single('erpapi_wms_matrix_sf_response_delivery')->init($this->__channelObj)->status_update($delivery);

        // 为校验用
        $data['io_bn'] = $params['stockout_bn'];

        return $data;
    }
}
