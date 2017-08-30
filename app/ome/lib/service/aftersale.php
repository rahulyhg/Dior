<?php
/**
 * 售后服务
 * 有关售后方面的扩展功能都可以使用此服务
 * @author dongqiujing
 * @package ome_service_aftersale
 * @copyright www.shopex.cn 2010.10.14
 *
 */
class ome_service_aftersale{
    
    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 售后申请
     * @access public
     * @param int $return_id 售后申请ID
     */
    public function add_aftersale($return_id){
        $returnModel = $this->app->model('return_product');
        $returninfo = $returnModel->dump($return_id);
        
        $this->router->setShopId($returninfo['shop_id'])->add_aftersale($returninfo);

        //kernel::single("ome_rpc_request_aftersale")->add($return_id);
    }
    
    /**
     * 售后申请状态修改
     * @access public
     * @param int $return_id 售后申请ID
     */
    public function update_status($return_id,$status='',$mod='async',$memo=array()){
        $returnModel = $this->app->model('return_product');
        $returninfo = $returnModel->dump($return_id);
        if ($memo) {
            $returninfo['refuse_message'] = $memo['refuse_message'];
            $returninfo['refuse_proof']   = $memo['refuse_proof'];
            $returninfo['imgext']         = $memo['imgext'];
        }
        
        if($returninfo['source'] == 'matrix' && $returninfo['shop_type'] == 'tmall'){
            #拒绝退货的
            if($status == '5'){
                #没有凭证的，不再往前端打请求，避免二次请求
                if(empty($memo)){
                    return true;
                }
            }
        }
        $rs = $this->router->setShopId($returninfo['shop_id'])->update_aftersale_status($returninfo,$status,$mod);
        if ($mod == 'sync') {
            return $rs;
        }
        
        //kernel::single("ome_rpc_request_aftersale")->status_update($return_id);
    }

    
    /**
     * 退款留言
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function refund_message($apply_id,$type)
    {
        $data = array();
        if ($type == 'return') {
            $oReturn = $this->app->model('return_product');
            $oReturn_tmall = $this->app->model('return_product_tmall');
            $return = $oReturn->dump($apply_id);
            $shop_id = $return['shop_id'];
            $data['refund_bn'] = $return['return_bn'];
            $return_tmall = $oReturn_tmall->dump(array('return_bn'=>$return['return_bn'],'shop_id'=>$shop_id));
            if ($return_tmall) {
                $data['refund_phase'] = $return_tmall['refund_phase'];
                $data['refund_version'] = $return_tmall['refund_version'];
            }
        }else{
            $oRefund = $this->app->model('refund_apply');
            $refund = $oRefund->dump($apply_id);
            $shop_id = $refund['shop_id'];
            $refund_bn = $refund['refund_apply_bn'];
            $oRefund_tmall = $this->app->model('refund_apply_tmall');
            $refund_tmall = $oRefund_tmall->dump(array('apply_id'=>$apply_id,'shop_id'=>$shop_id));
            
            if ($refund_tmall) {
                $data['refund_phase'] = $refund_tmall['refund_phase'];
                $data['refund_version'] = $refund_tmall['refund_version'];
            }
            $data['refund_bn'] = $refund_bn;
        }
       
        $rs = $this->router->setShopId($shop_id)->get_refund_message($data);
        
        if($rs){
            if($rs->rsp == 'succ'){
                $tmp = json_decode($rs->data,true);
                $tmp = $tmp['refund_messages']['refund_message'];
                
                 foreach ($tmp as $tk=>$tv) {
                    if (isset($tv['pic_urls'])) {
                        $tmp[$tk]['voucher_urls'] = $tv['pic_urls']['pic_url'][0]['url'];
                    }
                }
                return $tmp;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    
    /**
     * 拒绝退货
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function refuse_return($returninfo)
    {
        $returnModel = $this->app->model('return_product');
        $return_id = $returninfo['return_id'];
        $return = $returnModel->dump($return_id);
        $returninfo['return_bn'] = $return['return_bn'];
        $returninfo['order_id'] = $return['order_id'];
        $result = $this->router->setShopId($return['shop_id'])->refuse_return($returninfo);
        
        $rs = array('rsp'=>'fail','msg'=>'失败');
        if($result){
            if($result->rsp == 'succ'){
                
                $rs['rsp'] = 'succ';
                $rs['msg'] = '成功';
            }else{
                
                $rs['msg'] = $result->err_msg;
            }
        }

        return $rs;
       
    }

    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function searchAddress($shop_id,$rdef='')
    {
        $rs = $this->router->setShopId($shop_id)->searchAddress($rdef);
        //return $rs;
    }
    
    
    /**
     * 退货回填物流单号.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function update_return_logistics($reship_id)
    {
        $oReship = $this->app->model('reship');
        $reshipinfo = $oReship->dump($reship_id);

        $rs = $this->router->setShopId($reshipinfo['shop_id'])->update_return_logistics($reshipinfo);
    }
    
}