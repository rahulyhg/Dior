<?php
/**
 * 发货的队列调用
 *
 * @author chris.zhang
 * @package ome_delivery_template
 * @copyright www.shopex.cn 2011.01.12
 *
 */
class ome_delivery_consign{

    function __construct($app)
    {
        $this->app = $app;
    }

    function run(&$cursor_id,$params){
        $dly_id = $params['sdfdata'];
        $model = &app::get($params['app'])->model($params['mdl']);
        $dly = $model->dump($dly_id,'process');
        if ($dly && $dly['process']=='false'){
            $model->consignDelivery($dly_id);
        }
        return false;
    }

    /**
     * @description 是否允许发货
     * @access public
     * @param void
     * @return void
     * @author chening<chenping@shopex.cn>
     */
    public function deliAllow($logi_no,$branches,&$msg,&$patch) 
    {
        $deliBillModel = $this->app->model('delivery_bill');
        $deliModel = $this->app->model('delivery');
        $delivery = $deliModel->db->select("SELECT delivery_id,branch_id,is_bind,status,verify,process,ship_area,logi_id,logi_number,delivery_logi_number,delivery_bn,type FROM sdb_ome_delivery WHERE logi_no='".$logi_no."'");
        $patch = false;
        if (!$delivery) {
            $delivery = $this->is_patch_logi_no($logi_no,$deliBill);
            
            $patch = true;
        }
        if (!$delivery) { 
            $msg = '快递单号【'.$logi_no.'】不存在！';
            return false;
        }
        $delivery = current($delivery);
        $logi_number = $delivery['logi_number'];
        $delivery_logi_number = $delivery['delivery_logi_number'];
        
        //-- 验证快递单是否已经发货
        if ($patch === true && $deliBill['status'] == 1) {
            $msg = '快递单号【'.$logi_no.'】已经发货！';
            return false;
        }
        
        if ($patch === false) {
            //$deliBillCount1 = $deliBillModel->count(array('delivery_id'=>$delivery['delivery_id'],'status'=>'1'));
            //$deliBillCount0 = $deliBillModel->count(array('delivery_id'=>$delivery['delivery_id'],'status'=>'0'));
            if ($delivery['status'] == 'succ') {
                $msg = '快递单号【'.$logi_no.'】已经发货！';
                return false;
            }
            #多包情况
            elseif($logi_number > 1){
                $deliBillCount = $deliBillModel->count(array('delivery_id'=>$delivery['delivery_id'],'status'=>'1'));
                if($delivery_logi_number > $deliBillCount){
                    $msg = '主单【'.$logi_no.'】不能再重复发货！';
                    return false;
                }  
            }
        }
       


        if (!in_array($delivery['branch_id'],$branches) && $branches[0] != '_ALL_') {
            $msg = '你无权对快递单【'.$logi_no.'】进行发货！';
            return false;
        }

        if (!$deliModel->existOrderStatus($delivery['delivery_id'], $delivery['is_bind'])){
            $msg = '快递单号【'.$logi_no.'】对应发货单不处于可发货状态！';
            return false;
        }

        if (!$deliModel->existOrderPause($delivery['delivery_id'], $delivery['is_bind'])){
            $msg = '快递单号【'.$logi_no.'】对应发货单订单存在异常！';
            return false;
        }

        if ($delivery['status'] == 'back'){
            $msg = '快递单号【'.$logi_no.'】对应发货单已打回！';
            return false;
        }
        if ($delivery['verify'] == 'false'){
            $msg = '快递单号【'.$logi_no.'】对应发货单未校验！';
            return false;
        }

        if ($delivery['process'] == 'true'){
            $msg = '快递单号【'.$logi_no.'】对应发货单已发货！';
            return false;
        }
        
        $deliItemModel = $this->app->model('delivery_items');
        $delivery_items = $deliItemModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
        foreach ($delivery_items as $item) {
            if ($item['verify'] == 'false'){
                $msg = '快递单号【'.$logi_no.'】对应发货单未校验！';
                return false;
            }
            
            if ($delivery['type'] == 'normal') {
                $re = $deliModel->existStockIsPlus($item['product_id'],$item['number'],$item['item_id'],$delivery['branch_id'],$err,$item['bn']);
                if (!$re){
                   $msg = $err;
                   return false;
                }
            }

             if(app::get('taoguaninventory')->is_installed()){
                 $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($item['product_id'],$delivery['branch_id']);

                if(!$check_inventory){
                   $msg = '正在盘点,请将该货物放回指定区域';
                    return false;
                }
            }
        }


        $orderInfo = $deliModel->getOrderByDeliveryId($delivery['delivery_id']);
        if($orderInfo['pay_status'] == '5'){
            $msg = '对应订单 '.$orderInfo['order_bn'].' 已退款';
            return false;
        }
        
        return $delivery;
    }

    /**
     * @description 判断是否是补打发货单
     * @access public
     * @param void
     * @return void
     */
    public function is_patch_logi_no($logi_no,&$deliBill='') 
    {
        $deliBill = $this->app->model('delivery_bill')->select()->columns('*')->where('logi_no=?',$logi_no)->instance()->fetch_row();
        if (!$deliBill) {
            return false;
        }

        $delivery = $this->app->model('delivery')->getList('delivery_id,branch_id,is_bind,status,verify,process,ship_area,logi_id,logi_number,delivery_logi_number,delivery_bn,type',array('delivery_id'=>$deliBill['delivery_id']),0,1);
        return $delivery;
    }
}