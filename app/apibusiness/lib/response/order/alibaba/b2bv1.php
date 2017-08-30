<?php
/**
* alibaba(阿里巴巴平台)分销订单处理
*
* @category apibusiness
* @package apibusiness/response/order/alibaba
* @author chenping<chenping@shopex.cn>
* @version $Id: b2bv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_alibaba_b2bv1 extends apibusiness_response_order_alibaba_abstract
{

    /**
     * 解决订单备注没更新(淘宝平台问题，备注修改订单最后时间不变)
     *
     * @return void
     * @author
     **/
    protected function operationSel()
    {
        parent::operationSel();
        $lastmodify = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);
        if (empty($this->_operationsel) && $lastmodify == $this->_tgOrder['outer_lastmodify'] && $this->_tgOrder['pay_status']=='0' && $this->_ordersdf['pay_status'] == '1' && time() <= strtotime('2013-12-11')) {
            $this->_operationsel = 'update';

            $this->addMasterComponent = true;
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
        $components = parent::get_update_components();

        if ($this->addMasterComponent == true) {
            $components[] = 'master';
        }

        return $components;
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
        $plugins[] = 'payment';
        $plugins[] = 'refundapply';
        $plugins[] = 'sellingagent';

        return $plugins;
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
    }

    /**
     * 更新订单
     *
     * @return void
     * @author 
     **/
    // public function updateOrder()
    // {
    //     parent::updateOrder();
        
    //     if ($this->_newOrder) {
    //         // 叫回发货单
    //         kernel::single('apibusiness_notice')->notice_process_order($this->_tgOrder,$this->_newOrder);
    //     }
    // }

    /**
     * 允许更新
     *
     * @return void
     * @author 
     **/
    protected function canUpdate()
    {
        if( ($this->_tgOrder['ship_status'] == 0) && ($this->_tgOrder['shipping']['is_cod'] == 'true') && ($this->_ordersdf['status'] != 'active') && ($this->_ordersdf['shipping']['is_cod'] == 'true') ){
            // 取消订单
            $memo = '前端店铺:'.$this->_shop['name'].'订单作废';

            $orderModel = app::get(self::_APP_NAME)->model('orders');
            $orderModel->cancel($this->_tgOrder['order_id'],$memo,false,'async');
            
            $this->_apiLog['info'][] = '返回值：订单取消成功！';
            $this->shutdown('add');

        }elseif( ($this->_ordersdf['shipping']['is_cod'] == 'true') && ($this->_ordersdf['status'] != 'active') ){
            $this->_apiLog['info']['msg'] = '取消的订单不接收';
            return false;
        } 

        return parent::canUpdate();
    }
}