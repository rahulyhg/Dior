<?php
/**
* 360buy(京东平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/360buy
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_360buy_abstract extends apibusiness_response_order_abstractbase
{
    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    protected function canAccept()
    {
        $result = parent::canAccept();

        if ($result === false) {
            return false;
        }

        // 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }

        // 商户类型不是SOP
        if ($this->_shop['addon']['type'] != 'SOP') {
            $this->_apiLog['info']['msg'] = '商户类型不是SOP订单不接收';
            return false;
        }

        return true;
    }

    /**
     * 订单转换淘管格式
     *
     * @return void
     * @author 
     **/
    public function component_convert()
    {

        parent::component_convert();

        $this->_newOrder['pmt_goods'] = abs($this->_newOrder['pmt_goods']);
        $this->_newOrder['pmt_order'] = abs($this->_newOrder['pmt_order']);
    }

    /**
     * 操作判断
     *
     * @return void
     * @author 
     **/
    protected function operationSel()
    {
        parent::operationSel();
        if ($this->_tgOrder) {
            $this->_operationsel = 'update';
            
            /*
            if($this->_tgOrder['is_fail'] == 'true' && $this->_tgOrder['download_time'] >= strtotime('2013-11-10') ){

                foreach ($this->_tgOrder['order_objects'] as $objkey=>$object){
                    $tmpobjbn = uniqid();
                    if(!$object['bn']){
                        $this->_tgOrder['order_objects'][$objkey]['bn'] = $tmpobjbn;
                    }
                    foreach ($object['order_items'] as $itemkey=>$item){
                        $tmpitembn = uniqid();
                        if(!$item['bn']){
                            $this->_tgOrder['order_objects'][$objkey]['order_items'][$itemkey]['bn'] = $tmpitembn;
                        }
                    }
                }
            }*/
        }
    }

    /**
     * 需要更新的组件
     *
     * @return void
     * @author 
     **/
    protected function get_update_components()
    {
        $components = array('master','markmemo');

        // 失败订单进行ITEM修复
        /*
        if($this->_tgOrder['is_fail'] == 'true' && $this->_tgOrder['download_time'] >= strtotime('2013-11-10') ){
            $components[] = 'items360buy';
        }*/

        return $components;
    }

    protected function reTransSdf()
    {
        parent::reTransSdf();

        if(!$this->_ordersdf['lastmodify']){
            $this->_ordersdf['lastmodify'] = date('Y-m-d H:i:s',time());
        }

        $trade_refunding = false;

        //获取货号
        foreach ($this->_ordersdf['order_objects'] as $objkey => &$object) {
            foreach ($object['order_items'] as $k => &$v) {
                //货号不存在
                if (empty($v['bn'])) {
                    $item   = array();
                    $sku_id = $v['shop_product_id'];
                    $item   = $this->item_get($sku_id);
                    if ($item && $item['outer_id']) {
                        //货号

                        $v['bn']      = $item['outer_id'];
                        $object['bn'] = $item['outer_id'];
                    }
                }

                if ($v['status'] == 'refund') {
                    $trade_refunding = true;
                    $v['status'] = 'active';
                }
            }
        }

        if ($trade_refunding == true) {
            $this->_ordersdf['pay_status'] = '7';
        }
    }
    /**
     * 获取货品信息
     * @param String $shop_product_id sku_id
     */
    protected function item_get($sku_id)
    {
        if (empty($sku_id)) {
            return array();
        }
        $sku = array('sku_id' => $sku_id);
        $rs = kernel::single('apibusiness_router_request')->setShopId($this->_shop['shop_id'])->item_sku_get($sku,$this->_shop['shop_id']);
        if ($rs->rsp == 'fail' || !$rs->data) {
            $this->_apiLog['info'][] = '获取SKU('.$rs->msg_id.')失败：' . $sku_id;
            return array();
        }
        $data = json_decode($rs->data, true);
        if ($rs->rsp == 'succ' && $data) {
            return json_decode($data['sku'], true);
        }
        else {
            return array();
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
        } elseif ($this->_tgOrder['ship_status'] == '1' && $this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['pay_status'] == '7') {
            $this->_apiLog['info']['msg'] = '后端已发货订单不接收';

            $this->exception('add');

            return false;
        }

        return $rs;
    }

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
     * 能够创建订单
     *
     * @return void
     * @author 
     **/
    public function canCreate()
    {
        if ($this->_ordersdf['status'] != 'active') {
            $this->_apiLog['info']['msg'] = ($this->_ordersdf['status'] == 'dead') ? '取消的订单不接收' : '完成的订单不接收';
            return false;
        }     

        return parent::canCreate();
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
        
        //判断京东订单原来是已支付的变成退款中，已拆分未发货的话叫回
        if((($this->_tgOrder['shipping']['is_cod'] == 'true' && $this->_tgOrder['pay_status'] == '0') || $this->_tgOrder['pay_status'] == '1') && $this->_newOrder['pay_status'] == '7' && in_array($this->_tgOrder['process_status'], array('splitting','splited')) && $this->_tgOrder['ship_status'] == '0'){
            app::get(self::_APP_NAME)->model('orders')->pauseOrder($this->_tgOrder['order_id'],'true');
        }

        if ($this->_newOrder) {
            // 叫回发货单
            kernel::single('apibusiness_notice')->notice_process_order($this->_tgOrder,$this->_newOrder);
        }
    }
}