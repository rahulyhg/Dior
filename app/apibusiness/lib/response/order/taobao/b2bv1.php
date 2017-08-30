<?php
/**
* taobao(淘宝平台)分销订单处理
*
* @category apibusiness
* @package apibusiness/response/order/taobao
* @author chenping<chenping@shopex.cn>
* @version $Id: b2bv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_taobao_b2bv1 extends apibusiness_response_order_taobao_abstract
{
    public $_use_itemtfxv = false;

    /**
     * 是否接收(除活动订单外的其他订单)
     *
     * @return void
     * @author 
     **/
    protected function accept_dead_order()
    {
        $rs = parent::accept_dead_order();

        if ($rs == false && $this->_ordersdf['status'] == 'dead') {
            unset($this->_apiLog['info']['msg']);
            
            return true;
        }

        if ($rs == false && $this->_ordersdf['status'] == 'finish') {
            unset($this->_apiLog['info']['msg']);

            return true;
        }

        return $rs;
    }

    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    protected function canAccept()
    {
        $rs = parent::canAccept();
        if ($rs == false) {
            return false;
        }
        
        $result = kernel::single('ome_service_c2c_taobao_order')->pre_tbfx_order($this->_ordersdf,$this->_shop['addon']);
        if($result['rsp'] == 'fail'){
            $this->_apiLog['info']['msg'] = $result['msg'];
            return false;
        }
        
        // 只接收已支付的
        if ($this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付淘分销订单不接收';
            return false;
        }

        return true;
    }

    /**
     * 插件
     *
     * @return void
     * @author 
     **/
    public function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'tbfx';
        $plugins[] = 'sellingagent';

        return $plugins;
    }

    /**
     * 获取更新信息插件
     *
     * @return void
     * @author 
     **/
    public function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        $plugins[] = 'member';
        $plugins[] = 'promotion';
        // $plugins[] = 'payment';
        $plugins[] = 'refundapply';
        $plugins[] = 'tbfx';
        $plugins[] = 'sellingagent';
        $plugins[] = 'crm';

        return $plugins;
    }

    /**
     * 需更新的组件
     *
     * @return void
     * @author 
     **/
    protected function get_update_components()
    {
        $components = array('master','itemstbfx','shipping','consignee','custommemo','markmemo','marktype');

        if ($this->_use_itemtfxv === true) {
            $components[1] = 'itemstb';
        }
        return $components;
    }

    /**
     * 获取格式转换组件
     *
     * @return void
     * @author 
     **/
    protected function get_convert_components()
    {
        $components = array('master','itemstbfx','shipping','consignee','consigner','custommemo','markmemo','marktype');
        //$components = parent::get_convert_components();
        //$key = array_search('items',$components);
        if ($this->_use_itemtfxv === true) {
            $components[1] = 'itemstb';
        }

        return $components;
    }

    /**
     * 对平台接收的数据纠正(有些是前端打的不对的)
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        parent::reTransSdf();

        foreach($this->_ordersdf['selling_agent'] as $k=>$v){
            if($k == 'agent'){
                $this->_ordersdf['selling_agent']['member_info'] = $this->_ordersdf['selling_agent']['agent'];
                unset($this->_ordersdf['selling_agent']['agent']);
            }
        }
        
        $trade_refunding = false; $trade_refundmoney = 0;        
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {
            if($object['status'] == 'TRADE_REFUNDING'){
                $trade_refunding = true;
            }

            foreach ($object['order_items'] as $itemkey => $item) {
                if($item['quantity'] == '0'){
                    unset($this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]);
                }

                // 判断订单支付状态是否为退款中...
                if ($item['status'] == 'TRADE_REFUNDING') {
                    $trade_refunding = true;
                }
            }

            if(empty($object['order_items'])){
                unset($this->_ordersdf['order_objects'][$objkey]);
            }
        }

        // 退款中...
        if ($trade_refunding == true) {
            $this->_ordersdf['pay_status'] = '7';
        }

        if ($this->_ordersdf['is_tax'] == 'None') {
            $this->_ordersdf['is_tax'] = 'false';
        }

        $this->mergeItemsForB2b();

        if ( !$this->_ordersdf['order_source'] ) $this->_ordersdf['order_source'] = $this->_ordersdf['order_type'];
        
    }

    /**
     * 淘分销订单，相同货号，类型，价格的订单明细合并
     *
     * @return void
     * @author 
     **/
    private function mergeItemsForB2b()
    {
        $objIdent = array();
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {
            $ident = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type'])));
            if (false !== array_search($ident, $objIdent)) {
                $this->_use_itemtfxv = true;
            }
            $objIdent[] = $ident;

            $order_items = array(); $replace = false;
            foreach ($object['order_items'] as $item) {
                // 销售单价
                $sale_price = bcdiv((float)$item['sale_price'], $item['quantity'],3);

                $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type']) . $sale_price));

                if (isset($order_items[$itemkey])) { // 如果存在，说明有合并
                    // 各相关值叠加
                    $order_items[$itemkey]['quantity']   += $item['quantity'];
                    $order_items[$itemkey]['pmt_price']  += $item['pmt_price'];
                    $order_items[$itemkey]['price']      += $item['price'];
                    $order_items[$itemkey]['amount']     += $item['amount'];
                    $order_items[$itemkey]['sale_price'] += $item['sale_price'];

                    $replace = true;

                    $this->_use_itemtfxv = true;
                } else {
                    $order_items[$itemkey] = $item;
                }
            }

            if ($replace === true) {
                $this->_ordersdf['order_objects'][$objkey]['order_items'] = $order_items;                
            }
        }
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
        
        if ($this->_newOrder) {
            // 叫回发货单
            kernel::single('apibusiness_notice')->notice_process_order($this->_tgOrder,$this->_newOrder);
        }
    }

    /**
     * 允许更新
     *
     * @return void
     * @author 
     **/
    protected function canUpdate()
    {
        $rs = parent::canUpdate();

        // 全额退款，订单取消
        if ($this->_ordersdf['status'] == 'dead') {

            if ($this->_tgOrder['status'] == 'active' && $this->_tgOrder['ship_status'] == '0') {
                $orderModel = app::get(self::_APP_NAME)->model('orders');

                if ($this->_tgOrder['pay_status'] == '7') {
                    $ordersdf = array(
                        'pay_status' => '5',
                        'payed' => '0',
                    );
                    $orderModel->update($ordersdf,array('order_id' => $this->_tgOrder['order_id']));
                }

                $memo = '前端订单取消';
                $orderModel->cancel($this->_tgOrder['order_id'],$memo,false,'async');

                $this->_apiLog['info'][] = '返回值：订单取消成功';

                $this->shutdown('add');

                return false;
            } else {
                $this->_apiLog['info'][] = '返回值：取消订单不更新';

                return false;
            }
        } elseif($this->_ordersdf['status'] == 'finish') {
            $orderModel = app::get(self::_APP_NAME)->model('orders');

            if ($this->_ordersdf['pay_status'] == '1' && $this->_tgOrder['pay_status'] == '7') {
                $ordersdf = array(
                    'pay_status' => '1',
                );

                $orderModel->update($ordersdf,array('order_id' => $this->_tgOrder['order_id']));

                $this->_apiLog['info'][] = '前端拒绝退款并手动发货，后端更新支付状态：1';

                $this->shutdown('add');
            } else {
                $this->_apiLog['info']['msg'] = '完成的订单不接收';

                $this->exception('add');
            }

            return false;
        }

        return $rs;
    }

    public function canCreate()
    {
        $allow = parent::canCreate();
        if ($allow == true) {

            $funcLib = kernel::single('ome_func');

            $order_time = $funcLib->date2time($this->_ordersdf['createtime']);

            // $payment_list = isset($this->_ordersdf['payments']) ? $this->_ordersdf['payments'] : array($this->_ordersdf['payment_detail']);
            // if ($payment_list && is_array($payment_list) ) {
            //     foreach ($payment_list as $payment) {
            //         if ($payment['pay_time']) {
            //             $pay_time = $funcLib->date2time($payment['pay_time']);

            //             if ($pay_time > $order_time) {
            //                 $order_time = $pay_time;
            //             }
            //         }
            //     }
            // }

            // 直联分销，如果绑定时间大于支付时间,同时又绑了分销王,订单不收
            /*
            $shopex_b2b = app::get('ome')->model('shop')->getList('node_id',array('node_type' => 'shopex_b2b','filter_sql' => 'node_id is not null'));
            if ($shopex_b2b && $this->_shop['addon']['bindtime'] && $this->_shop['addon']['bindtime'] > $order_time) {
                $this->_apiLog['info']['msg'] = '订单不接收：下单时间早于店铺绑定时间且同时绑有分销王店铺!';

                return false;
            }*/

        }

        return $allow;
    }
}