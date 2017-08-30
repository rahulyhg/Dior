<?php
/**
 * 入库单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_stockin
{
    /**
     * 入库单
     *
     * @param Array $params=array(
     *                  'io_type'=>@入库类型@ PURCHASE|ALLCOATE
     *                  'io_source'=>selfwms
     *                  'io_bn'=>@入库单号@                                           
     *                  'io_status'=>@入库单状态@ PARTIN|FINISH|FAILED|CANCEL|CLOSE   
     *                  'memo'=>@备注@                                                
     *                  'operation_time'=>@操作时间@                                  
     *                  'items'=>array(                                               
     *                      'bn'=>@货号@                                              
     *                      'num'=>@数量@
     *                      'defective_num'=>@次品数@                                 
     *                      'normal_num'=>@正品数@                                    
     *                  )
     *              )  
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        if ($params['reship_bn']) {
            return kernel::single('console_event_receive_iostock')->reship_result($params);
        } else {
            return kernel::single('console_event_receive_iostock')->stockin_result($params);
        }
    }
}