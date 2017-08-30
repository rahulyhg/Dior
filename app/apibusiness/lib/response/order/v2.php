<?php
/**
* 版本二 订单统一处理
*
* @category apibusiness
* @package apibusiness/response/order/
* @author chenping<chenping@shopex.cn>
* @version $Id: v2.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_v2 implements apibusiness_response_order_vinterface
{
    private $_platform = null;

    const _APP_NAME = 'ome';

    public function setPlatform($platform)
    {
        $this->_platform = $platform;

        return $this;
    }

    public function analysis(&$ordersdf){
        
    }
    
    public function status_update()
    {
        // 只接收作废订单
        if ($this->_platform->_ordersdf['status'] == '') {
            $this->_platform->_apiLog['info']['msg'] = 'Order status is not exists';
            $this->_platform->exception(__METHOD__);
        }

        if ($this->_platform->_ordersdf['status'] != 'dead') {
            $this->_platform->_apiLog['info']['msg'] = '不接收除作废以外的其他状态';
            $this->_platform->exception(__METHOD__);
        }

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('pay_status,order_id,op_id,ship_status,status,process_status',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            if ($this->_platform->_ordersdf['status'] == 'dead') {
                if (in_array($tgOrder['pay_status'], array('1','2','3','4'))) {
                    $this->_platform->_apiLog['info']['msg'] = 'Order ' . $this->_platform->_ordersdf['order_bn'] . ' has been paid';
                    $this->_platform->exception(__METHOD__);
                }

                if ($tgOrder['ship_status'] != 0) {
                    $this->_platform->_apiLog['info']['msg'] = '取消失败：ERP中不是未发货订单';
                    $this->_platform->exception(__METHOD__);
                }

                if ($tgOrder['status'] != 'active' || $tgOrder['process_status'] == 'cancel') {
                    $this->_platform->_apiLog['info']['msg'] = '取消失败：ERP中不是活动订单';
                    $this->_platform->exception(__METHOD__);
                }
            }

            $updateOrder = array();

            if (!$tgOrder['op_id']) {
                $userModel = app::get('desktop')->model('users');
                $userinfo = $userModel->getList('user_id',array('super'=>'1'),0,1,'user_id asc');
                $updateOrder['op_id'] = $userinfo[0]['op_id'];
            }

            $updateOrder['status'] = $this->_platform->_ordersdf['status'];

            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '更新订单状态：' . var_export($this->_platform->_ordersdf['status'], true);

            if ($this->_platform->_ordersdf['status'] == 'dead') {
                $orderModel->cancel($tgOrder['order_id'],'订单被取消',false,'async');
                $this->_platform->_apiLog['info'][] = '取消订单，ID为：' . $tgOrder['order_id'];
            }

        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__);
        }
    }

    public function pay_status_update()
    {
        $this->_platform->_apiLog['info']['msg'] = '版本2不走此接口';
        return array('rsp' => 'success','msg' => '','data' => $this->_ordersdf['order_bn']);
    }

    public function ship_status_update()
    {
        $this->_platform->_apiLog['info']['msg'] = '版本2不走此接口';
        return array('rsp' => 'success','msg' => '','data' => $this->_ordersdf['order_bn']);
    }

    public function custom_mark_add()
    {
        $this->_platform->_apiLog['info']['msg'] = '版本2不走此接口';
        return array('rsp' => 'success','msg' => '','data' => $this->_ordersdf['order_bn']);
    }

    public function custom_mark_update()
    {
        $this->_platform->_apiLog['info']['msg'] = '版本2不走此接口';
        return array('rsp' => 'success','msg' => '','data' => $this->_ordersdf['order_bn']);
    }

    public function memo_add()
    {
        $this->_platform->_apiLog['info']['msg'] = '版本2不走此接口';
        return array('rsp' => 'success','msg' => '','data' => $this->_ordersdf['order_bn']);
    }

    public function memo_update()
    {
        $this->_platform->_apiLog['info']['msg'] = '版本2不走此接口';
        return array('rsp' => 'success','msg' => '','data' => $this->_ordersdf['order_bn']);
    }

    public function payment_update()
    {
        $this->_platform->_apiLog['info']['msg'] = '版本2不走此接口';
        return array('rsp' => 'success','msg' => '','data' => $this->_ordersdf['order_bn']);
    }
}