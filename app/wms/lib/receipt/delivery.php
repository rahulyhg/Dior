<?php
class wms_receipt_delivery{

    //前端操作发货单的动作状态定义
    //取消
    const __CANCEL = 1;

    //暂停
    const __PAUSE = 2;

    //发货
    const __DELIVERY = 3;

    //恢复
    const __RENEW = 4;

    /**
     *
     * 发货通知单创建方法
     * @param array $data 发货通知单数据信息
     */
    public function create(&$sdf,&$msg = ''){
        //校验传入参数
        if(!$this->checkParams($sdf,$error_msg)){
            $msg = '发货通知单参数检验失败,具体原因:'.$error_msg;
            return false;
        }

        //检查发货通知单是否已经存在
        if($this->checkOuterExist($sdf['outer_delivery_bn'])){
            $msg = '发货通知单外部编号'.$sdf['outer_delivery_bn']."已存在";
            return false;
        }

        //数据组织与格式化
        //$data = $sdf;

        //主表信息
        $data['delivery_bn'] = $this->gen_id();

        //外部发货通知单号，必要且唯一
        $data['outer_delivery_bn'] = $sdf['outer_delivery_bn'];

        $data['idx_split']  = $sdf['idx_split'];
        $data['skuNum']     = $sdf['skuNum'];
        $data['itemNum']    = $sdf['itemNum'];
        $data['bnsContent'] = $sdf['bnsContent'];

        $data['delivery_group'] = $sdf['delivery_group'];
        $data['sms_group'] = $sdf['sms_group'];

        $data['member_id'] = $sdf['member_id'];

        $data['is_protect'] = $sdf['is_protect'] ? $sdf['is_protect'] : 'false';
        $data['cost_protect'] = $sdf['cost_protect'] ? $sdf['cost_protect'] : '0';
        $data['is_cod'] = $sdf['is_cod'];
        //$data['delivery'] = $sdf['delivery'];

        $data['logi_id'] = $sdf['logi_id'];
        $data['logi_name'] = $sdf['logi_name'];
        //$data['logi_no'] = '';
        //$data['logi_number'] = 1;
        //$data['delivery_logi_number'] = 0;

        //收货人信息
        $data['consignee'] = $sdf['consignee'];

        $data['create_time'] = time();
        //$data['status'] = 'ready';
        $data['memo'] = $sdf['memo'];
        $data['branch_id'] = $sdf['branch_id'];

        $data['net_weight'] = $sdf['net_weight'] ? $sdf['net_weight'] : 0.000;
        //$data['status'] = '';

        $data['delivery_cost_expect'] = $sdf['delivery_cost_expect'];
        //$data['delivery_cost_actual']

        $data['bind_key'] = $sdf['bind_key'];

        //是普通发货单还是原样寄回发货单
        $data['type'] = $sdf['type'];

        $data['shop_id'] = $sdf['shop_id'];
        $data['order_createtime'] = $sdf['order_createtime'];
        $data['op_id'] = $sdf['op_id'];
        $data['op_name'] = $sdf['op_name'];

        //明细表信息
        $data['delivery_items'] = $sdf['delivery_items'];

        $deliveryObj = app::get('wms')->model('delivery');
        if($deliveryObj->save($data)){
            $sdf['delivery_id'] = $data['delivery_id'];

            $bill_info = array(
                'delivery_id' => $sdf['delivery_id'],
                'net_weight' => $data['net_weight'],
            );

            $deliveryBillObj = app::get('wms')->model('delivery_bill');
            $deliveryBillObj->save($bill_info);

            return true;
        }else{
            return false;
        }
    }

    /**
     * 发货通知单参数校验
     * @param array $params 发货通知参数信息
     * @param string $msg 发货通知单错误消失
     */
    private function checkParams($params,&$msg){
        return true;
    }


    /**
     * 生成发货通知单的唯一标识
     */
    private function gen_id(){
        $cManage = &app::get('ome')->model("concurrent");
        $prefix = date("ymd").'11';
        $sqlString = "SELECT MAX(delivery_bn) AS maxno FROM sdb_wms_delivery WHERE delivery_bn LIKE '".$prefix."%'";
        $aRet = &app::get('wms')->model("delivery")->db->selectrow($sqlString);
        if(is_null($aRet['maxno'])){
            $aRet['maxno'] = 0;
            $maxno = 0;
        }else
            $maxno = substr($aRet['maxno'], -5);

        do{
            $maxno += 1;
            if ($maxno==100000){
                break;
            }
            $maxno = str_pad($maxno,5,'0',STR_PAD_LEFT);

            $sign = $prefix.$maxno;

            if($cManage->is_pass($sign,'wms_delivery')){
                break;
            }
        }while(true);

        return $sign;
    }

    /**
     * 检查外部发货通知单号是否存在
     * @param string $outer_delivery_bn 外部发货通知单号
     */
    public function checkOuterExist($outer_delivery_bn){
        $deliveryObj = app::get('wms')->model("delivery");
        $aRet = $deliveryObj->dump(array('outer_delivery_bn'=>trim($outer_delivery_bn)),'delivery_bn');
        if(isset($aRet['delivery_bn']) && !empty($aRet['delivery_bn'])){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 根据外部通知单号获取发货单信息
     * @param string $outer_delivery_bn
     */
    public function getOneByOuterDlyBn($outer_delivery_bn){
        $deliveryObj = app::get('wms')->model("delivery");
        $deliveryInfo = $deliveryObj->dump(array('outer_delivery_bn'=>trim($outer_delivery_bn)),'delivery_bn');
        return $deliveryInfo ? $deliveryInfo : null;
    }

    /**
     *
     * 根据当前状态判断前端更新状态是否可操作
     * @param string $outer_delivery_bn
     * @param int $remote_status
     * @param string $msg
     */
    function checkDlyStatusByOuterDlyBn($outer_delivery_bn, $remote_status, &$msg){
        $deliveryObj = app::get('wms')->model("delivery");
        $deliveryInfo = $deliveryObj->dump(array('outer_delivery_bn'=>trim($outer_delivery_bn)),'status,disabled');

        if($deliveryInfo['disabled'] == 'true'){
            $msg = '发货单已删除';
            return false;
        }

        switch ($remote_status){
            case 1:
                //取消动作时
                if($deliveryInfo['status'] == 1){
                    $msg = '发货单已取消';
                    return false;
                }elseif ($deliveryInfo['status'] == 3){
                    $msg = '发货单已完成发货';
                    return false;
                }
                break;
            case 2:
                //暂停动作时
                if($deliveryInfo['status'] == 1){
                    $msg = '发货单已取消';
                    return false;
                }elseif($deliveryInfo['status'] == 2){
                    $msg = '发货单已暂停';
                    return false;
                }elseif ($deliveryInfo['status'] == 3){
                    $msg = '发货单已完成发货';
                    return false;
                }
                break;
            case 3:
                //发货动作时
                if($deliveryInfo['status'] == 1){
                    $msg = '发货单已取消';
                    return false;
                }elseif($deliveryInfo['status'] == 2){
                    $msg = '发货单已暂停';
                    return false;
                }elseif ($deliveryInfo['status'] == 3){
                    $msg = '发货单已完成发货';
                    return false;
                }
                break;
            case 4:
                //恢复动作时
                if($deliveryInfo['status'] == 1){
                    $msg = '发货单已取消';
                    return false;
                }elseif($deliveryInfo['status'] == 0){
                    $msg = '发货单未暂停';
                    return false;
                }elseif ($deliveryInfo['status'] == 3){
                    $msg = '发货单已完成发货';
                    return false;
                }
                break;
        }
        return true;
    }

    /**
     *
     * 取消发货单
     * @param string $outer_delivery_bn
     */
    function cancelDlyByOuterDlyBn($outer_delivery_bn){

        $deliveryObj = app::get('wms')->model("delivery");
        $deliveryBillObj = app::get('wms')->model("delivery_bill");

        $deliveryInfo = $deliveryObj->dump(array('outer_delivery_bn'=>$outer_delivery_bn),'delivery_id');

        $filter = array('outer_delivery_bn' => $outer_delivery_bn);
        $data = array('status' => 1);
        $deliveryObj->update($data, $filter);

        $filter = array('delivery_id' => $deliveryInfo['delivery_id']);
        $data = array('status' => 2,'logi_no'=>'');
        $deliveryBillObj->update($data, $filter);
    }

    /**
     *
     * 暂停发货单
     * @param string $outer_delivery_bn
     */
    function pauseDlyByOuterDlyBn($outer_delivery_bn){

        $deliveryObj = app::get('wms')->model("delivery");

        $filter = array('outer_delivery_bn' => $outer_delivery_bn);
        $data = array('status' => 2);

        $deliveryObj->update($data, $filter);
    }

    /**
     *
     * 恢复发货单
     * @param string $outer_delivery_bn
     */
    function renewDlyByOuterDlyBn($outer_delivery_bn){

        $deliveryObj = app::get('wms')->model("delivery");

        $filter = array('outer_delivery_bn' => $outer_delivery_bn);
        $data = array('status' => 0);

        $deliveryObj->update($data, $filter);
    }

}