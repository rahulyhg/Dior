<?php
/**
 * 退款service
 * 有关退款方面的扩展功能都可以使用此服务
 * @author dongqiujing
 * @package ome_service_refund
 * @copyright www.shopex.cn 2010.10.14
 *
 */
class ome_service_refund{

    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 添加退款单
     * @access public
     * @param int $refund_id 退款单ID
     */
    public function refund($refund_id){
        $refundModel = $this->app->model('refunds');
        $refund = $refundModel->dump($refund_id);

        $this->router->setShopId($refund['shop_id'])->add_refund($refund);
        //kernel::single("ome_rpc_request_refund")->add($refund_id);
    }
    
    /**
     * 退款单请求
     * @access public
     * @param int $sdf 请求数据
     */
    public function refund_request($sdf){
        $this->router->setShopId($sdf['shop_id'])->add_refund($sdf);
        //kernel::single("ome_rpc_request_refund")->refund_request($sdf);
    }
    
    /**
     * 退款单状态更新
     * @access public
     * @param int $refund_id 退款单ID
     */
    public function update_status($refund_id,$status=''){
        $refundModel = $this->app->model('refunds');
        $refund = $refundModel->dump($refund_id);
        if ($status) {
            unset($refund['status']);
            $refund['status'] = $status;
        }
        $this->router->setShopId($refund['shop_id'])->update_refund_status($refund);
        //kernel::single("ome_rpc_request_refund")->status_update($refund_id);
    }

    public function refuse_refund($refundinfo){
        set_time_limit(0);
        $rs = array('rsp'=>'succ','msg'=>'成功','data'=>'');
        $apply_id = $refundinfo['apply_id'];
        $refundapplyModel = $this->app->model('refund_apply');
        $refund = $refundapplyModel->dump($apply_id,'shop_id,refund_apply_bn,order_id');
        $refundinfo = array_merge($refund,$refundinfo);
        $rsp = $this->router->setShopId($refund['shop_id'])->refuse_refund($refundinfo);
        
        if($rsp){
            if($rsp->rsp == 'succ'){
  
            }else{
                $rs['rsp'] = 'fail';
                $rs['msg'] = $rsp->err_msg;
 
            }
        }else{
            $rs['rsp'] = 'fail';
            $rs['msg'] = '失败';
        }
        return $rs;
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

    
    /**
     * 接受申请
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function accept_refundstatus($status,$apply_id)
    {
        $refundapplyModel = $this->app->model('refund_apply');
        $refund = $refundapplyModel->dump($apply_id,'shop_id,refund_apply_bn,order_id');
        $rs = $this->router->setShopId($refund['shop_id'])->accept_refundstatus($status,$refund);;
        
        $rsp = array('rsp'=>'fail','msg'=>'失败');
        if($rs){
            if($rs->rsp == 'succ'){
                $tmp = json_decode($rs->data,true);
                
                $rsp = array('rsp'=>'succ','msg'=>'成功','data'=>$tmp);
            }else{
                $rsp['msg'] = $rs->err_msg;
            }
        }
        return $rsp;
    }
}