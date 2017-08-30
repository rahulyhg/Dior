<?php
class ome_event_trigger_delivery{

    /**
     *
     * 发货通知创建发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 发货通知数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function create($wms_id,&$data, $sync = false){

        // $result = kernel::single('middleware_wms_request', $wms_id)->delivery_create($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->delivery_create($data);

        $ext_data['delivery_bn'] = $data['outer_delivery_bn'];
        if ( $result['rsp'] == 'success' && $result['data']['wms_order_code']) {
            $oDelivery_extension = app::get('console')->model('delivery_extension');
            $ext_data['original_delivery_bn'] = $result['data']['wms_order_code'];
            $oDelivery_extension->save($ext_data);
        }
        return $result;
    }

    /**
     *
     * 发货通知创建发起的响应接收方法
     * @param array $data
     */
    public function create_callback($res){

    }

    /**
     *
     * 发货通知取消发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 发货通知状态数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function cancel($wms_id, $data, $sync = false){
        //新增对应仓库bn
        $delivery_bn = $data['outer_delivery_bn'];
        $dlyObj = &app::get('ome')->model("delivery");
        $delivery = $dlyObj->dump(array('delivery_bn'=>$delivery_bn),'branch_id');
        $branch_id =$delivery['branch_id'];
        $branch =$dlyObj->db->selectrow("SELECT branch_bn FROM sdb_ome_branch WHERE branch_id=".$branch_id);
        $data['branch_bn'] =$branch['branch_bn']; 

        // return kernel::single('middleware_wms_request', $wms_id)->delivery_cancel($data, $sync);
        return  kernel::single('erpapi_router_request')->set('wms',$wms_id)->delivery_cancel($data);
    }

    /**
     *
     * 发货通知取消发起方法
     * @param array $data
     */
    public function cancel_callback($res){

    }

    /**
     *
     * 发货通知暂停发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 发货通知状态数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function pause($wms_id, $data, $sync = false){
        // return kernel::single('middleware_wms_request', $wms_id)->delivery_pause($data, $sync);
        return kernel::single('erpapi_router_request')->set('wms', $wms_id)->delivery_pause($data);
    }

    /**
     *
     * 发货通知暂停发起方法
     * @param array $data
     */
    public function pause_callback($res){

    }

    /**
     *
     * 发货通知恢复发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 发货通知状态数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function renew($wms_id, $data, $sync = false){
        // return kernel::single('middleware_wms_request', $wms_id)->delivery_renew($data, $sync);
        return kernel::single('erpapi_router_request')->set('wms',$wms_id)->delivery_renew($data);
    }

    /**
     *
     * 发货通知恢复发起方法
     * @param array $data
     */
    public function renew_callback($res){

    }

    
    /**
     * 发货单查询
     * @param   
     * @return 
     * @access  public
     * @author cyyr24@sina.cn
     */
    function search($wms_id,&$sdf, $sync = false)
    {
        
        $oDelivery_ext = app::get('console')->model('delivery_extension');
        $delivery_ext = $oDelivery_ext->dump(array('delivery_bn'=>$sdf['delivery_bn']),'original_delivery_bn');
        $data = array(
            'delivery_bn'=>$sdf['delivery_bn'],
            'out_order_code'=>$delivery_ext['original_delivery_bn'],    
        );
        // $result = kernel::single('middleware_wms_request', $wms_id)->delivery_search($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->delivery_search($data);
        
        return $result;
    }

    
    /**
     * 查询回调
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function search_callback($res)
    {
        
    }
}