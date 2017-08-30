<?php
/**
 * 盘点
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_inventory
{
    /**
     * 盘点
     *  
     * @param Array $params=array(
     *                  'inventory_bn'=>@盘点单号@
     *                  'operate_time'=>@操作时间@
     *                  'memo'=>@备注@
     *                  'wms_id'=>@仓储id@
     *                  'io_source'=>selfwms
     *                  'branch_bn'=>@库存编号@
     *                  'inventory_type'=>@盘点类型@
     *                  'items'=>array(
     *                      'bn'=>@货号@ 
     *                      'num'=>@库存@
     *                      'normal_num'=>@良品@
     *                      'defective_num'=>@不良品@
     *                  )
     *              )
     *
     * @return void
     * @author 
     **/
    public function add($params)
    {
        return kernel::single('console_event_receive_iostock')->inventory_result($params);
    }
}
