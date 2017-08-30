<?php
/**
 * 退货
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_reship
{
    /**
     * @param Array $params=array(
     *                  'status'=>@状态@ PARTIN|FINISH|DENY|CLOSE|FAILED|ACCEPT 
     *                  'reship_bn'=>@退货单号@
     *                  'items'=>array(
     *                      'bn'=>@货号@
     *                      'normal_num"=>@良品@
     *                      'defective_num'=>@不良品@
     *                  )
     *              ) 
     *
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        return kernel::single('console_event_receive_iostock')->reship_result($params);
    }
}
