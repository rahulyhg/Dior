<?php
class ome_aftersale_request_feiniu  extends ome_aftersale_abstract{
    public function __construct(){
        $this->_render = app::get('ome')->render();
    }
     

    function show_aftersale_html(){
        
        $html = '';
        return $html;
    }
    function pre_save_refund($apply_id,$data){
        $rs = array('rsp'=>'succ','msg'=>'成功','data'=>'');
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
        
        
        #2是接受申请状态
        if ($data['status'] == '2') {
            $result = kernel::single('ome_service_refund_apply')->update_status($refunddata,2,'sync');
            return $result;
        }
    }
    /**
     * 售后保存前的扩展
     * @param
     * @return
     * @access  public
     * @author
     */
    function pre_save_return($data){
        set_time_limit(0);
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        $return_id = $data['return_id'];
        $status = $data['status'];
        if($status == '3') {
            $rsp = kernel::single('ome_service_aftersale')->update_status($return_id,'3','sync');
            if ($rsp  && $rsp['rsp'] == 'fail') {
                $rs['rsp'] = 'fail';
                $rs['msg'] = $rsp['msg'];
            }
        }
        return $rs;
    }

    /**
     * 退款申请详情扩展.
     *
     *
     *
     * @author
     */
    function refund_detail($refundinfo){
        $product_data = $refundinfo['product_data'];
    
        if ($product_data) {
            $product_data = unserialize($product_data);
        }
        $refundinfo['product_data'] = $product_data;
        $this->_render->pagedata['refundinfo'] = $refundinfo;
        unset($refundinfo);
        $html = $this->_render->fetch('admin/refund/plugin/refund_feiniu.html');
        return $html;
    }    
}
?>