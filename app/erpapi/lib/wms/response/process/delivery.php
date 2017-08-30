<?php
/**
 * 发货
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_delivery
{
    /**
     * 发货单
     * @param Array $params=array(
     *                  'status'=>@状态@ delivery 
     *                  'delivery_bn'=>@发货单号@
     *                  'out_delivery_bn'=>@外部发货单号@
     *                  'logi_no'=>@运单号@
     *                  'delivery_time'=>@发货时间@
     *                  'weight'=>@重量@
     *                  'delivery_cost_actual'=>@物流费@
     *                  'logi_id'=>@物流公司编码@
     *                  ===================================
     *                  'status'=>print,
     *                  'delivery_bn'=>@发货单号@
     *                  'stock_status'=>@备货单打印状态@
     *                  'deliv_status'=>@发货单打印状态@
     *                  'expre_status'=>@快递单打印状态@
     *                  ===================================
     *                  'status'=>check
     *                  'delivery_bn'=>@发货单号@
     *                  ===================================
     *                  'status'=>cancel
     *                  'delivery_bn'=>@发货单号@
     *                  'memo'=>@备注@
     *                  ===================================
     *                  'status'=>update
     *                  'delivery_bn'=>@发货单号@
     *                  'action'=>updateDetail|addLogiNo
     *                  
     *
     *              )
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        $params['delivery_time'] = $params['operate_time'];

        return kernel::single('ome_event_receive_delivery')->update($params);
    }
}
