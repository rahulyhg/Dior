<?php
/**
* taobao(淘宝平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/taobao
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_taobao_abstract extends apibusiness_response_order_abstractbase
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
        if (empty($this->_operationsel) && $lastmodify == $this->_tgOrder['outer_lastmodify']) {
            $this->_operationsel = 'update';
        }
    }

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

        # 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }

        if($this->_shop['business_type']=='zx' && in_array($this->_ordersdf['order_source'],array('tbdx','tbjx'))) {
            $this->_apiLog['info']['msg'] = '直销店铺不接收分销订单';
            return false;
        }

        if($this->_shop['business_type']=='fx' && !in_array($this->_ordersdf['order_source'],array('tbdx','tbjx'))) {
            $this->_apiLog['info']['msg'] = '分销店铺不接收直销订单';
            return false;
        }

        if(in_array($this->_ordersdf['step_trade_status'],array('FRONT_NOPAID_FINAL_NOPAID','FRONT_PAID_FINAL_NOPAID'))){
            $this->_apiLog['info']['msg'] = '定金未付尾款未付或定金已付尾款未付订单不接收';
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

        $checkems = app::get('ome')->getConf('ome.checkems');
        if ('true' == $checkems && 'ems' == strtolower($this->_newOrder['shipping']['shipping_name'])) {
            $custom_memo = $this->_newOrder['custom_mark'] ? unserialize($this->_newOrder['custom_mark']) : array();
            $custom_memo[] = array(
                'op_name'=>$this->_shop['name'],
                'op_time'=>date("Y-m-d H:i:s",time()),
                'op_content'=>'系统：用户选择了 EMS 的配送方式'
            );

            $this->_newOrder['custom_mark'] = serialize($custom_memo);
        }
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

        $plugins[] = 'tbgift';
        $plugins[] = 'tbjz';
        return $plugins;
    }

    /**
     * 需要更新的组件
     *
     * @return void
     * @author
     **/
    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype');
        $process_status = array('unconfirmed');
        #未审核的淘宝订单，修改收货人信息
        if(in_array($this->_tgOrder['process_status'], $process_status)){
            $obj_orders_extend = app::get('ome')->model('order_extend');
            $rs = $obj_orders_extend->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
            #判断本地收货人信息，是否发生变更
            if($rs[0]['extend_status'] == 'consignee_modified'){
                #ERP已修改
                $local_updated = true;
            }else{
                #ERP未修改
                $local_updated = false;
            }
            #如果ERP收货人信息未发生变动时，则更新淘宝收货人信息
            if($local_updated == false){
                #还要判断是未审核,审核的才修改
                $components[] = 'consignee';
            }
        }
        return $components;
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

        $plugins[] = 'tboversold';

        return $plugins;
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
     * 创建订单
     * @see apibusiness_response_order_abstractbase::createOrder()
     */
    public function createOrder()
    {
        parent::createOrder();
        #淘宝全链路 已转单
        kernel::single('ome_order')->sendMessageProduce(0, '', $this->_ordersdf['order_bn']);
    }
}