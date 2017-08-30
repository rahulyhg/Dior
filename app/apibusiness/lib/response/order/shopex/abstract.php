<?php
/**
* SHOPEX订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/shopex/fxw
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_shopex_abstract extends apibusiness_response_order_abstractbase
{
    /**
     * 获取插件
     *
     * @return void
     * @author 
     **/
    public function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        # 如果是0元订单，注册支付单插件
        if (bccomp('0.000', $this->_ordersdf['total_amount'],3) == 0) {
            $key = array_search('payment', $plugins);
            if ($key !== false) {
                unset($plugins[$key]);
            }
        }

        return $plugins;
    }

    /**
     * 允许更新
     *
     * @return void
     * @author 
     **/
    public function canUpdate()
    {   
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $payStatus = array('0','1','2','3','4','5','6','7','8');
        // 淘管中的订单被取消
        if ($this->_tgOrder['process_status'] == 'cancel' || $this->_tgOrder['status'] == 'dead') {
            if ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status'] && in_array($this->_ordersdf['pay_status'], $payStatus)) {
                $order['pay_status'] = $this->_ordersdf['pay_status'];

                $this->_apiLog['info'][] = '订单结构变化：更新订单支付状态为：'.$order['pay_status'];
            }

            if ($this->_ordersdf['payed'] != $this->_tgOrder['payed']) {
                $order['payed'] = $this->_ordersdf['payed'];

                $this->_apiLog['info'][] = '订单结构变化：更新订单付款金额为：'.$order['payed'];
            }

            if ($order) {
                $orderModel->update($order,array('order_id'=>$this->_tgOrder['order_id']));

                $logModel = app::get(self::_APP_NAME)->model('operation_log');
                $logModel->write_log('order_edit@ome',$this->_tgOrder['order_id'],"前端店铺订单更新");
            }

            return false;
        } elseif ($this->_ordersdf['status'] == 'dead') {
            // 前端取消

            $rs['rsp'] = 'fail';
            if ($this->_tgOrder['pay_status'] == '0' && $this->_tgOrder['ship_status'] == '0') {
                $memo = '前端店铺:'.$this->_shop['name'].'订单作废';

                $rs = $orderModel->cancel($this->_tgOrder['order_id'],$memo,false,'async');
            }

            if ($rs['rsp'] == 'fail') {
                $this->_apiLog['info'][] = '返回值：订单已发货无法取消或者取消失败';
                $this->exception('add');
            } else {
                $this->_apiLog['info'][] = '返回值：订单取消成功！';
            }
            
            return false;
        }

        return true;
    }

    /**
     * 获取插件
     *
     * @return void
     * @author 
     **/
    public function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        $plugins[] = 'member';
        $plugins[] = 'promotion';
        $plugins[] = 'payment';
        $plugins[] = 'refundapply';
        $plugins[] = 'cod';
        
        return $plugins;
    }

    /**
     * 更新订单
     *
     * @return void
     * @author 
     **/
    public function updateOrder()
    {
        parent::updateOrder();

        $oOrder_sync = app::get(self::_APP_NAME)->model('order_sync_status');
        $oOrder_sync->update(array('sync_status'=>'2'),array('order_id'=>$this->_tgOrder['order_id']));

        if ($this->_newOrder) {
            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $log_id = $logModel->write_log('order_edit@ome',$this->_tgOrder['order_id'],'前端店铺订单更新');

            // 订单快照
            $orderModel = app::get(self::_APP_NAME)->model('orders');
            $orderModel->write_log_detail($log_id,$this->_tgOrder);

            // 发送通知
            kernel::single('apibusiness_notice')->notice_process_order($this->_tgOrder,$this->_newOrder);
        }
    }

    /**
     * 需更新的组件
     *
     * @return void
     * @author 
     **/
    protected function get_update_components()
    {
        $component = parent::get_update_components();
        $key = array_search('items', (array)$component);
        if ($key !== false) {
            $component[$key] = 'shopexitems';
        }
        return $component;
    }

    /**
     * 获取格式转换组件
     *
     * @return void
     * @author 
     **/
    protected function get_convert_components()
    {
        $component = parent::get_convert_components();
        $key = array_search('items', (array)$component);
        if ($key !== false) {
            $component[$key] = 'shopexitems';
        }
        
        return $component;
    }

}