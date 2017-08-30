<?php
/**
* 版本一 订单统一处理
*
* @category apibusiness
* @package apibusiness/response/order/
* @author chenping<chenping@shopex.cn>
* @version $Id: v1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_v1 implements apibusiness_response_order_vinterface
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
        if ($this->_platform->_ordersdf['status'] == '') {
            $this->_platform->_apiLog['info']['msg'] = 'Order status is not exists';
            $this->_platform->exception(__METHOD__);
        }

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('pay_status,order_id,op_id',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            if ($this->_platform->_ordersdf['status'] == 'dead') {
                if ( in_array($tgOrder['pay_status'], array('1','2','3','4')) ) {
                    $this->_platform->_apiLog['info']['msg'] = 'Order ' . $this->_platform->_ordersdf['order_bn'] . ' has been paid';
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
        if ($this->_platform->_ordersdf['pay_status'] == '') {
            $this->_platform->_apiLog['info']['msg'] = 'Order pay_status is not exists';
            $this->_platform->exception(__METHOD__);
        }

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('order_id',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            $updateOrder = array();
            $updateOrder['pay_status'] = $this->_platform->_ordersdf['pay_status'];

            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '更新订单支付状态：'.$this->_platform->_ordersdf['pay_status'];

        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__);
        }
    }

    public function ship_status_update()
    {
        if ($this->_platform->_ordersdf['ship_status'] == '') {
            $this->_platform->_apiLog['info']['msg'] = 'Order ship_status is not exists';
            $this->_platform->exception(__METHOD__);
        }

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('order_id',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            $updateOrder = array();
            $updateOrder['ship_status'] = $this->_platform->_ordersdf['ship_status'];

            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '更新订单发货状态：'.$this->_platform->_ordersdf['ship_status'];
        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__);
        }
    }

    public function custom_mark_add()
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('order_id,custom_mark',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            $custom_mark = array();

            if ($tgOrder['custom_mark']) {
                $tgOrder['custom_mark'] = unserialize($tgOrder['custom_mark']);
                foreach ($tgOrder['custom_mark'] as $key => $value) {
                    $custom_mark[] = $value;
                }
            }

            $newMemo = array(
                'op_name' => $this->_platform->_ordersdf['sender'],
                'op_time' => kernel::single('ome_func')->date2time($this->_platform->_ordersdf['add_time']),
                'op_content' => htmlspecialchars($this->_platform->_ordersdf['message']),
            );
            $custom_mark[] = $newMemo;

            $updateOrder['custom_mark'] = serialize($custom_mark);
            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '返回值：买家留言添加成功';

        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__);
        }
    }

    public function custom_mark_update()
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('order_id,custom_mark',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            $custom_mark = array();

            if ($tgOrder['custom_mark']) {
                $tgOrder['custom_mark'] = unserialize($tgOrder['custom_mark']);
                foreach ($tgOrder['custom_mark'] as $key => $value) {
                    $custom_mark[] = $value;
                }
            }
            $newMemo = array(
                'op_name' => $this->_platform->_ordersdf['sender'],
                'op_time' => kernel::single('ome_func')->date2time($this->_platform->_ordersdf['add_time']),
                'op_content' => htmlspecialchars($this->_platform->_ordersdf['message']),
            );
            $custom_mark[] = $newMemo;

            $updateOrder['custom_mark'] = serialize($custom_mark);
            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '返回值：买家留言更新成功';

        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__);
        }
    }

    public function memo_add()
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('order_id,mark_text',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            $mark_text = array();

            if ($tgOrder['mark_text']) {
                $tgOrder['mark_text'] = unserialize($tgOrder['mark_text']);
                foreach ($tgOrder['mark_text'] as $key => $value) {
                    $mark_text[] = $value;
                }
            }
            $newMemo = array(
                'op_name' => $this->_platform->_ordersdf['sender'],
                'op_time' => kernel::single('ome_func')->date2time($this->_platform->_ordersdf['add_time']),
                'op_content' => htmlspecialchars($this->_platform->_ordersdf['memo']),
            );
            $mark_text[] = $newMemo;

            $updateOrder['mark_text'] = serialize($mark_text);
            $updateOrder['mark_type'] = $this->_platform->_ordersdf['flag'];
            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '返回值：订单备注添加成功';

        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__);
        }
    }

    public function memo_update()
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('order_id,mark_text',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            $mark_text = array();

            if ($tgOrder['mark_text']) {
                $tgOrder['mark_text'] = unserialize($tgOrder['mark_text']);
                foreach ($tgOrder['mark_text'] as $key => $value) {
                    $mark_text[] = $value;
                }
            }
            $newMemo = array(
                'op_name' => $this->_platform->_ordersdf['sender'],
                'op_time' => kernel::single('ome_func')->date2time($this->_platform->_ordersdf['add_time']),
                'op_content' => htmlspecialchars($this->_platform->_ordersdf['memo']),
            );
            $mark_text[] = $newMemo;

            $updateOrder['mark_text'] = serialize($mark_text);
            $updateOrder['mark_type'] = $this->_platform->_ordersdf['flag'];
            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '返回值：订单备注更新成功';
        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__);
        }

    }

    public function payment_update()
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');

        $filter = array('order_bn'=>$this->_platform->_ordersdf['order_bn'],'shop_id'=>$this->_platform->_shop['shop_id']);
        $tgOrder = $orderModel->getList('order_id,mark_text,cost_payment,total_amount,final_amount',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if ($tgOrder) {
            $total_amount = bcsub(bcadd($tgOrder['total_amount'], $this->_platform->_ordersdf['cost_payment'],3), $tgOrder['cost_payment'],3);
            $updateOrder = array(
                'pay_bn' => $this->_platform->_ordersdf['pay_bn'],
                'payinfo' => array(
                    'pay_name' => $this->_platform->_ordersdf['payment'],
                    'cost_payment' => $this->_platform->_ordersdf['cost_payment'],
                ),
                'cur_amount' => $total_amount,
                'total_amount' => $total_amount,
            );

            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));

            $this->_platform->_apiLog['info'][] = '返回值：更新支付单状态成功';

        } else {
            $this->_platform->_apiLog['info']['msg'] = 'Order Order_bn ' . $this->_platform->_ordersdf['order_bn'] . ' is not exists';
            $this->_platform->exception(__METHOD__); 
        }
    }
}