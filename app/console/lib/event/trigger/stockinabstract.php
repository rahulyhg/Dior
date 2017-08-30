<?php
/**
* 入库接口请求抽象类
*
* @category console
* @package console/lib/event/trigger
* @author sunjing<sunjing@shopex.cn>
* @version $Id: abstract.php 2013-6-1 14:44Z
*/
abstract class console_event_trigger_stockinabstract {

    public function create($data, $sync = false){

        $data = $this->getStockInParam($data);
        $io_bn = $data['io_bn'];
        $wms_id = kernel::single('ome_branch')->getWmsIdById($data['branch_id']);
        if ($data['supplier_id'] && !isset($data['supplier_bn'])) {
            $supplier = $this->getSupplier($data['supplier_id']);
            $data = array_merge($data,$supplier);
        }

        if ($wms_id) {
            
            // $result = kernel::single('middleware_wms_request', $wms_id)->stockin_create($data, $sync);
            $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockin_create($data);

            if ($result['rsp'] == 'success' || $result['data']['wms_order_code']) {
                $this->update_out_bn($io_bn,$result['data']['wms_order_code']);
            }
        }
        
        
        return $result;
    }

    /**
     *
     * 入库通知创建发起的响应接收方法
     * @param array $data
     */
    public function create_callback($res){
       
    }

    /**
     *
     * 入库通知取消变更发起方法
     * @param string $wms_id 仓库类型ID
     * @param string $po_bn 采购通知单编号
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function cancel($data, $sync = false){
        
        $wms_id = kernel::single('ome_branch')->getWmsIdById($data['branch_id']);
        $branch =$this->getBranchByid($data['branch_id']);
        $data['branch_bn'] =$branch['branch_bn']; 

        // $result =  kernel::single('middleware_wms_request', $wms_id)->stockin_cancel($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockin_cancel($data);
        return $result;
    }

    
    function getStockInParam($param){}

    

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
    * 返回供应商相关信息
    *
    */
    function getSupplier($supplier_id){
        $oSupplier = app::get('purchase')->model('supplier');
        $supplier = $oSupplier->dump($supplier_id,'area,name,zip,addr,telphone,bn');
        $area = explode(':',$supplier['area']);
        $area = explode('/',$area[1]);
        $data = array(
            'shipper_name'=>$supplier['name'],
            'shipper_zip'=>$supplier['zip'],
            'shipper_state'=>$area[0],
            'shipper_city'=>$area[1],
            'shipper_district'=>$area[2],
            'shipper_address'=>$supplier['addr'],
            'shipper_phone'=>$supplier['telphone'],
            'supplier_bn'=>$supplier['bn'],
            );
        return $data;
    }

    /**
    *更新
    */
    
    protected function update_out_bn($iso_id,$out_iso_bn)
    {
        
    }

    
    /**
     * 入库查询
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function search($wms_id,&$data, $sync = false)
    {

        // $result =  kernel::single('middleware_wms_request', $wms_id)->stockin_search($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockin_search($data);
        return $result;
        
    }

    
    
}
?>