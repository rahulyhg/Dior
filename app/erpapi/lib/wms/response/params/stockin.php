<?php
/**
 * WMS 入库参数验证
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_params_stockin extends erpapi_wms_response_params_abstract
{
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        $params = array(
            'io_bn' => array('required'=>'true', 'type'=>'string','errmsg'=>'入库单号必填'),
        );

        return $params;
    }
}