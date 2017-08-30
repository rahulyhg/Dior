<?php
/**
* 出库发起接口请求抽象类
*
* @category console
* @package console/lib/event/trigger
* @author sunjing<sunjing@shopex.cn>
* @version $Id: abstract.php 2013-6-1 14:44Z
*/
abstract class console_event_trigger_stockoutabstract {

    /**
    * 出库通知发起
    *
    */
    public function create($param, $sync = false){
        $data = $this->getStockOutParam($param);
        if ($data['supplier_id'] && !isset($data['supplier_bn'])) {
            $purchaseObj = kernel::single('console_event_trigger_purchase');
            $supplier = $purchaseObj->getSupplier($data['supplier_id']);
            $data = array_merge($data,$supplier);
        }

        $wms_id = kernel::single('ome_branch')->getWmsIdById($data['branch_id']);
        if ($wms_id) {
            // $result = kernel::single('middleware_wms_request', $wms_id)->stockout_create($data, $sync);
            $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockout_create($data);

            if ($result['rsp'] == 'success' || $result['data']['wms_order_code']) {
                $this->update_out_bn($data['io_bn'],$result['data']['wms_order_code']);
            }
        }
        
    }

    /**
     *
     * 出库通知创建发起的响应接收方法
     * @param array $data
     */
    public function create_callback($res){

    }

    
    /**
    * 出库取消
    */
    public function cancel($data, $sync = false){
       
        $wms_id = kernel::single('ome_branch')->getWmsIdById($data['branch_id']);
        $branch =$this->getBranchByid($data['branch_id']);
        $data['branch_bn'] =$branch['branch_bn']; 
        // $result = kernel::single('middleware_wms_request', $wms_id)->stockout_cancel($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockout_cancel($data);

        return $result;
    }

    function getStockOutParam($param){}

    /**
    * 获取仓库详情
    * @access public
    * @param  $branch_id 仓库ID
    * 
    * @return Array 
    */
    function getBranchByid($branch_id){
        $oBranch = app::get('ome')->model('branch');
        $branch_damaged = $oBranch->getlist('type,branch_id,branch_bn,storage_code',array('branch_id' => $branch_id),0,1);
        $branch_damaged = $branch_damaged[0];
       return $branch_damaged;

    }

     

    
    /**
     * 查询出库结果
     * @param  
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function search($wms_id, $data, $sync)
    {
       
        // $result = kernel::single('middleware_wms_request', $wms_id)->stockout_search($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockout_search($data);
        return $result;
    }

    /**
    *更新
    */
    
    protected function update_out_bn($iso_id,$out_iso_bn)
    {
        
    }

    
}
?>