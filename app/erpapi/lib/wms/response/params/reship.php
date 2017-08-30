<?php
/**
 * WMS 退货参数验证
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_params_reship extends erpapi_wms_response_params_abstract
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
            'reship_bn' => array('required'=>'true','type'=>'string','errmsg'=>'退货单号必填'),
            'status'=>array('type'=>'enum','value'=>array('FINISH','PARTIN','CLOSE','FAILED','DENY','ACCEPT')),
        );

        return $params;
    }
}