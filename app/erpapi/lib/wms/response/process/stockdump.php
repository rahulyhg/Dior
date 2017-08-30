<?php
/**
 * 转储单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_stockdump
{
    /**
     * 转储单
     *
     * @param Array $params=array(
     *                  'status'=>@状态@ FINISH|FAILED|CANCEL|CLOSE
     *                  'io_source'=>selfwms
     *                  'stockdump_bn'=>@转储单号@
     *                  'memo'=>@备注@
     *                  'items'=>array(
     *                      'bn'=>@货号@
     *                      'num'=>@数量@
     *                  )
     *  
     *              )
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        return kernel::single('console_event_receive_stockdump')->ioStorage($params);
    }
}