<?php
class wmsvirtual_delivery 
{
    
    
    /**
     * 更新发货单
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function result($result,$node_id)
    {
        
        $method = 'wms.delivery.status_update';
        $data = $this->format_data($result);
        kernel::single('wmsvirtual_response')->dispatch('wms',$method,$data,$node_id);
    }

    
    /**
     *格式化数据
     * @param   
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function format_data($result)
    {
        $delivery_bn = $data['delivery_bn'];
        $oDelivery_ext = app::get('ome')->model('delivery_extension');
        $delivery_ext = $oDelivery_ext->dump(array('original_delivery_bn'=>$delivery_bn),'delivery_bn');
        $data = array(
            'delivery_bn'=>$result['delivery_bn'],
            'logistics'=>$result['logistics'],
            'logi_no'=>$result['logi_no'],
            'status'=>$result['status'],
            'volume'=>'333',
            'weight'=>'444',
            'remark'=>'44',
            'operate_time'=>$result['operate_time'],
            
        );
        
        return $data;

    }
} 

?>