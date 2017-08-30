<?php
/**
 * 库存对账
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_stock
{
    
    /**
     * 库存对账
     *
     * @param Array $params=array(
     *                  'batch'=>@@
     *                  'wms_id'=>@仓储ID@
     *                  'items'=>array(
     *                      'product_bn'=>@货号@
     *                      'normal_num'=>@良品@
     *                      'defective_num'=>@不良品@
     *                  )
     *              )
     * @return void
     * @author 
     **/
    public function quantity($params)
    {
        return kernel::single('console_event_receive_iostock')->stock_result($params);
    }

}
