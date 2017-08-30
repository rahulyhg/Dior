<?php
class console_event_trigger_reship{

    
    /**
     *
     * 退货单通知创建发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 商品同步数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function create($wms_id, &$data, $sync = false){
        
        // $result = kernel::single('middleware_wms_request', $wms_id)->reship_create($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->reship_create($data);

        if ($result['rsp'] == 'success' && $result['data']['wms_order_code']) {
            $this->update_out_bn($data['reship_bn'],$result['data']['wms_order_code']);
            
        }
    }

    /**
     *
     * 采购通知创建发起的响应接收方法
     * @param array $data
     */
    public function create_callback($res){

    }

    /**
     *
     * 退货单通知更新发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 商品同步数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function cancel($wms_id, &$data, $sync = false){
        
        // $result = kernel::single('middleware_wms_request', $wms_id)->reship_cancel($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->reship_cancel($data);
        return $result;
    }

    /**
     *
     * 采购通知更新发起的响应接收方法
     * @param array $data
     */
    public function cancel_callback($res){

    }

    /**
     * 退货单查询
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function search($wms_id,&$data, $sync = false)
    {

        //$result =  kernel::single('middleware_wms_request', $wms_id)->reship_search($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->reship_search($data);
    }

     
    /**
     * 更新退货单外部编号
     * @param
     * @return  
     * @access  protected
     * @author sunjing@shopex.cn
     */
    protected function update_out_bn($reship_bn,$out_iso_bn)
    {
        $oReship = app::get('ome')->model('reship');
        $data = array(
            'out_iso_bn'=>$out_iso_bn,    
        );
        $oReship->update($data,array('reship_bn'=>$reship_bn));
    }
}