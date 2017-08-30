<?php
/**
 * 退款申请service
 * 有关退款申请方面的扩展功能都可以使用此服务
 * @author sunjing
 * @package ome_service_refund_apply
 * @copyright www.shopex.cn 2013.08.14
 *
 */
class ome_service_refund_apply{
    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    
    
    /**
     * 更新退款申请单状态
     * @param   array refund
     * @param   int   status
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function update_status($refund,$status,$mod = 'async')
    {
        $apply_id = $refund['apply_id'];
        $refundapplyModel = $this->app->model('refund_apply');
        $refundinfo = $refundapplyModel->dump($apply_id,'shop_id,refund_apply_bn,order_id');
        $refundinfo = array_merge($refund,$refundinfo);
        $rsp = $this->router->setShopId($refundinfo['shop_id'])->update_refund_apply_status($refundinfo,$status,$mod);
        
        return $rsp;
    }

    /**
    * 回写留言和凭证
    */
    function add_refundmemo($data){
        $refundModel = $this->app->model('refund_apply');
        $apply_id = $data['apply_id'];
        $refund = $refundModel->dump($apply_id);
        $shop_id = $refund['shop_id'];
        $data['refund_apply_bn'] = $refund['refund_apply_bn'];
        $data['order_id'] = $refund['order_id'];
        $data['content'] = $data['newmemo']['op_content'];
        $data['image'] = $data['newmemo']['image'];

        $this->router->setShopId($refund['shop_id'])->add_refundmemo($data);
    }

    
}
?>