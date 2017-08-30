<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wms_event_receive_delivery extends wms_event_response{

    /**
     * 发货单创建事件
     * @param array $data
     */
    public function create($data){
        //检查发货单数据信息

        //创建发货单
        $res = kernel::single('wms_receipt_delivery')->create($data, $msg);
        if($res){
            return $this->send_succ();
        }else{
            return $this->send_error($msg, $msg_code);
        }
    }

    /**
     * 发货单取消事件处理
     * @param array $data
     */
    public function cancel($data){

        $deliveryLib = kernel::single('wms_receipt_delivery');

        if(!isset($data['outer_delivery_bn']) || empty($data['outer_delivery_bn'])){
            return $this->send_error('必要参数丢失', $msg_code, $data);
        }

        //检查发货单是否存在
        if(!$deliveryLib->checkOuterExist($data['outer_delivery_bn'])){
            return $this->send_error('发货单不存在', $msg_code, $data);
        }

        //检查发货单当前状态是否有效，可操作
        if(!$deliveryLib->checkDlyStatusByOuterDlyBn($data['outer_delivery_bn'],wms_receipt_delivery::__CANCEL,$msg)){
            return $this->send_error($msg, $msg_code, $data);
        }

        //执行发货单取消
        $deliveryLib->cancelDlyByOuterDlyBn($data['outer_delivery_bn']);
        return $this->send_succ();

    }

    /**
     * 发货单暂停事件处理
     * @param array $data
     */
    public function pause($data){

        $deliveryLib = kernel::single('wms_receipt_delivery');

        if(!isset($data['outer_delivery_bn']) || empty($data['outer_delivery_bn'])){
            return $this->send_error('必要参数丢失', $msg_code, $data);
        }

        //检查发货单是否存在
        if(!$deliveryLib->checkOuterExist($data['outer_delivery_bn'])){
            return $this->send_error('发货单不存在', $msg_code, $data);
        }

        //检查发货单当前状态是否有效，可操作
        if(!$deliveryLib->checkDlyStatusByOuterDlyBn($data['outer_delivery_bn'],wms_receipt_delivery::__PAUSE,$msg)){
            return $this->send_error($msg, $msg_code, $data);
        }

        //执行发货单暂停
        $deliveryLib->pauseDlyByOuterDlyBn($data['outer_delivery_bn']);
        return $this->send_succ();
    }

    /**
     * 发货单恢复事件处理
     * @param array $data
     */
    public function renew($data){

        $deliveryLib = kernel::single('wms_receipt_delivery');

        if(!isset($data['outer_delivery_bn']) || empty($data['outer_delivery_bn'])){
            return $this->send_error('必要参数丢失', $msg_code, $data);
        }

        //检查发货单是否存在
        if(!$deliveryLib->checkOuterExist($data['outer_delivery_bn'])){
            return $this->send_error('发货单不存在', $msg_code, $data);
        }

        //检查发货单当前状态是否有效，可操作
        if(!$deliveryLib->checkDlyStatusByOuterDlyBn($data['outer_delivery_bn'],wms_receipt_delivery::__RENEW,$msg)){
            return $this->send_error($msg, $msg_code, $data);
        }

        //执行发货单暂停
        $deliveryLib->renewDlyByOuterDlyBn($data['outer_delivery_bn']);
        return $this->send_succ();
    }
}

?>
