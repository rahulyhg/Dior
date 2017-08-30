<?php
abstract class wms_event_trigger_stockinabstract{

    /**
     *入库事件发起方法
     * 
     * @param array data 
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function inStorage($data, $sync = false){
        $data = $this->getStockInData($data);
        $wms_id = kernel::single('ome_branch')->getWmsIdById($data['branch_id']);

        //$result = kernel::single('middleware_wms_response', $wms_id)->stockin_result($data,$sync);set_channel_id($wms_id)
        $result = kernel::single('erpapi_router_response')->set_channel_id($wms_id)->set_api_name('wms.stockin.status_update')->dispatch($data);
    }

    /**
     *
     * 入库事件发起的响应接收方法
     * @param string 
     */
    public function inStorage_callback($res){

    }

   
    /**
    * 获取对应数据
    * @param   type    $varname    description
    * @return  type    description
    * @access  public or private
    * @author cyyr24@sina.cn
    */
    function getStockInData($iso_id,$data=array())
    {
       
    } // end func

    public function cancel($data, $sync = false){
        $iostockdataObj = kernel::single('wms_iostockdata');
        $wms_id = kernel::single('ome_branch')->getWmsIdById($data['branch_id']);
        $data['in_status'] = 'CANCEL';
        $branch_detail = $iostockdataObj->getBranchByid($data['branch_id']);
        $data['branch_bn'] = $branch_detail['branch_bn'];
        //$result = kernel::single('middleware_wms_response', $wms_id)->stockin_result($data, $sync);
        $result = kernel::single('erpapi_router_response')->set_channel_id($wms_id)->set_api_name('wms.stockin.status_update')->dispatch($data);
        return $result;
    }
}

?>
