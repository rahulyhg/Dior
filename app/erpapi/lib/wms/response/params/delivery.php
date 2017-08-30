<?php
/**
 * WMS 发货单参数验证
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_params_delivery extends erpapi_wms_response_params_abstract
{
    /**
     * 发货单更新校验参数
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        $params = array(
            'delivery_bn' => array('required'=>'true', 'type'=>'string','errmsg'=>'发货单号必填'),
            'status'=>array('type'=>'enum','value'=>array('delivery','print','check','cancel','update','accept','pick','package','partin')),
        );
        return $params;
    }

}