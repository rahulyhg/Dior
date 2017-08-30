<?php
/**
 * 校验LIB类
 *
 * @author chenping<chenping@shopex.cn>
 * @version $2012-8-17 15:07Z
 */

class ome_delivery_check 
{

    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @description 是否允许发货
     * @access public
     * @param void
     * @return void
     */
    public function checkAllow($logi_no,$branches,&$msg) 
    {
        $deliModel = $this->app->model('delivery');
        $delivery = $deliModel->getList('logi_no,branch_id,delivery_id,is_bind,verify,status,pause,deli_cfg,stock_status,deliv_status,expre_status,delivery_bn',array('logi_no'=>$logi_no),0,1);
        if (!$delivery) {
            $msg = '快递单号【'.$logi_no.'】不存在！';
            return false;
        }
        $delivery = current($delivery);

        if (!in_array($delivery['branch_id'],$branches) && $branches[0] != '_ALL_') {
            $msg = '你无权对快递单【'.$logi_no.'】进行校验！';
            return false;
        }

        if (!$deliModel->existOrderStatus($delivery['delivery_id'], $delivery['is_bind'])){
            $msg = '快递单号【'.$logi_no.'】对应发货单不处于可校验状态！';
            return false;
        }

        if (!$deliModel->existOrderPause($delivery['delivery_id'], $delivery['is_bind'])){
            $msg = '快递单号【'.$logi_no.'】对应发货单订单存在异常！';
            return false;
        }

        if ($delivery['verify'] == 'true'){
            $msg = '快递单号【'.$logi_no.'】对应发货单已校验完成！';
            return false;
        }
        if ($delivery['status'] != 'progress'){
            $msg = '快递单号【'.$logi_no.'】对应发货单不满足校验需求！';
            return false;
        }
        if ($delivery['pause'] == 'true'){
            $msg = '快递单号【'.$logi_no.'】对应发货单已暂停！';
            return false;
        }

        $printFinish = $deliModel->checkPrintFinish($delivery,$msg);
        if($printFinish == false){
            return false;
        }

        return $delivery;
    }

    /**
     * @京东出库请求入口方法
     * @access public
     * @param void
     * @return void
     */
    public function _outstorage($delivery_id,&$msg='') 
    {
        //京东订单出库操作
        $dlyObj = &$this->app->model('delivery');
        $shop_type     = $dlyObj->getShopType($delivery_id);
        $jingdong_type = ome_shop_type::jingdong_type();
        $is_jingdong = in_array($shop_type, $jingdong_type);
        if ($is_jingdong){
            $result = kernel::single("ome_rpc_request_shipping")->outstorage($delivery_id);
            if(!$result){
                $msg = '此物流运单号通知京东出库失败';
                return false;
            }else{
                return true;
            }
        }
        return true;
    }

}