<?php
/**
* 订单主表信息
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: master.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_master extends apibusiness_response_order_component_abstract
{
    const _APP_NAME = 'ome';
    /**
     * 订单格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {
        $funcObj = kernel::single('ome_func');

        # 店铺
        $this->_platform->_newOrder['shop_id']   = $this->_platform->_shop['shop_id'];
        $this->_platform->_newOrder['shop_type'] = $this->_platform->_shop['shop_type'];

        # 订单主信息
        $this->_platform->_newOrder['order_bn']         = $this->_platform->_ordersdf['order_bn'];
        $this->_platform->_newOrder['cost_item']        = (float)$this->_platform->_ordersdf['cost_item'];
        $this->_platform->_newOrder['discount']         = (float)$this->_platform->_ordersdf['discount'];
        $this->_platform->_newOrder['total_amount']     = (float)$this->_platform->_ordersdf['total_amount'];
        $this->_platform->_newOrder['pmt_goods']        = (float)$this->_platform->_ordersdf['pmt_goods'];
        $this->_platform->_newOrder['pmt_order']        = (float)$this->_platform->_ordersdf['pmt_order'];
        $this->_platform->_newOrder['cur_amount']       = (float)$this->_platform->_ordersdf['cur_amount'];
        $this->_platform->_newOrder['score_u']          = (float)$this->_platform->_ordersdf['score_u'];
        $this->_platform->_newOrder['score_g']          = (float)$this->_platform->_ordersdf['score_g'];
        $this->_platform->_newOrder['currency']         = $this->_platform->_ordersdf['currency'] ? $this->_platform->_ordersdf['currency'] : 'CNY';
        $this->_platform->_newOrder['source']           = 'matrix';
        $this->_platform->_newOrder['status']           = $this->_platform->_ordersdf['status'];
        $this->_platform->_newOrder['weight']           = (float)$this->_platform->_ordersdf['weight'];
        $this->_platform->_newOrder['order_source']     = $this->_platform->_ordersdf['order_source'];
        $this->_platform->_newOrder['cur_rate']         = $this->_platform->_ordersdf['cur_rate'] ? $this->_platform->_ordersdf['cur_rate'] : 1;
        $this->_platform->_newOrder['title']            = $this->_platform->_ordersdf['title'];
        $this->_platform->_newOrder['coupons_name']     = $this->_platform->_ordersdf['coupons_name'];
        $this->_platform->_newOrder['createway']        = 'matrix';

        # 时间
        $this->_platform->_newOrder['download_time']    = time();
        $this->_platform->_newOrder['createtime']       = $funcObj->date2time($this->_platform->_ordersdf['createtime']);
        $outer_lastmodify = $this->_platform->_ordersdf['lastmodify'] ? $this->_platform->_ordersdf['lastmodify'] : time();
        $this->_platform->_newOrder['outer_lastmodify'] = $funcObj->date2time($outer_lastmodify);

        if ($this->_platform->_ordersdf['order_limit_time']) {
            $this->_platform->_newOrder['order_limit_time'] = $funcObj->date2time($this->_platform->_ordersdf['order_limit_time']);
        } else {
            $this->_platform->_newOrder['order_limit_time'] = time() + 60 * (app::get('ome')->getConf('ome.order.failtime'));
        }

        # 支付方式
        $payment_cfg             = kernel::single('apibusiness_adapter_payment_cfg')->get_payment($this->_platform->_ordersdf['pay_bn'],$this->_platform->_shop['node_type']);
        $this->_platform->_newOrder['pay_bn']      = $payment_cfg['pay_bn'];
        $this->_platform->_newOrder['pay_status'] = $this->_platform->_ordersdf['pay_status'];
        $this->_platform->_newOrder['payed']      = $this->_platform->_ordersdf['payed'];

        # 支付金额
        $this->_platform->_newOrder['payinfo']['pay_name']     = $this->_platform->_ordersdf['payinfo']['pay_name'];
        $this->_platform->_newOrder['payinfo']['cost_payment'] = $this->_platform->_ordersdf['payinfo']['cost_payment'];

        # 支付单结构
        $payment_list = isset($this->_platform->_ordersdf['payments']) ? $this->_platform->_ordersdf['payments'] : array($this->_platform->_ordersdf['payment_detail']);
        if ($payment_list[0]) {
            $this->_platform->_newOrder['pay_status'] = '0';
            $this->_platform->_newOrder['payed']      = '0.000';
            foreach ($payment_list as $key => $value) {
                $this->_platform->_newOrder['payed'] += $value['money'];

                if($value['pay_time'])
                    $this->_platform->_newOrder['paytime'] = kernel::single('ome_func')->date2time($value['pay_time']);
            }

            if ($this->_platform->_newOrder['total_amount'] <= $this->_platform->_newOrder['payed']) {
                if(!$this->_platform->_newOrder['paytime'])
                    $this->_platform->_newOrder['paytime']    = time();
                $this->_platform->_newOrder['pay_status'] = '1';
            } elseif ($this->_platform->_newOrder['payed'] <= 0) {
                $this->_platform->_newOrder['pay_status'] = '0';
            } else {
                if(!$this->_platform->_newOrder['paytime'])
                    $this->_platform->_newOrder['paytime']    = time();

                $comp = bccomp($this->_platform->_newOrder['payed'], $this->_platform->_newOrder['total_amount'],3);
                if ($comp<0) {
                    $this->_platform->_newOrder['pay_status'] = '3';
                } else {
                    $this->_platform->_newOrder['pay_status'] = '1';
                }
            }
        }

        # 发票
        $this->_platform->_newOrder['is_tax']           = $this->_platform->_ordersdf['is_tax'] ? $this->_platform->_ordersdf['is_tax'] : 'false';
        $this->_platform->_newOrder['cost_tax']         = (float)$this->_platform->_ordersdf['cost_tax'];
        $this->_platform->_newOrder['tax_no']           = $this->_platform->_ordersdf['tax_no'];
        $this->_platform->_newOrder['tax_title']        = $this->_platform->_ordersdf['tax_title'];
    }

    public function update()
    {
        if (in_array($this->_platform->_tgOrder['pay_status'], array('6','7')) && in_array($this->_platform->_ordersdf['pay_status'], array('1','3','4','5'))) {
            $this->_platform->_newOrder['pause'] = 'false';
        }

        $master = array();

        if ($this->_platform->_ordersdf['order_limit_time']) {
            $order_limit_time = kernel::single('ome_func')->date2time($this->_platform->_ordersdf['order_limit_time']);
            if ($order_limit_time != $this->_platform->_tgOrder['order_limit_time'] && $this->_platform->_tgOrder['pay_status'] == '0') {
                $master['order_limit_time'] = $order_limit_time;

                $this->_platform->_apiLog['info'][] = '订单失效时间发生变化：'.date('Y-m-d H:i:s',$order_limit_time) . '('.$order_limit_time.')';

                $logModel = app::get(self::_APP_NAME)->model('operation_log');
                $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改订单失效时间');
            }
        }

        $master['pay_status']                = $this->_platform->_ordersdf['pay_status'];
        $master['discount']                  = $this->_platform->_ordersdf['discount'];
        $master['pmt_goods']                 = $this->_platform->_ordersdf['pmt_goods'];
        $master['pmt_order']                 = $this->_platform->_ordersdf['pmt_order'];
        $master['total_amount']              = $this->_platform->_ordersdf['total_amount'];
        $master['cur_amount']                = $this->_platform->_ordersdf['cur_amount'];
        $master['payed']                     = $this->_platform->_ordersdf['payed'];
        $master['cost_item']                 = $this->_platform->_ordersdf['cost_item'];
        $master['coupons_name']              = $this->_platform->_ordersdf['coupons_name'];
        $master['is_tax']                    = $this->_platform->_ordersdf['is_tax'] ? $this->_platform->_ordersdf['is_tax'] : 'false';
        $master['tax_no']                    = $this->_platform->_ordersdf['tax_no'];
        $master['cost_tax']                  = $this->_platform->_ordersdf['cost_tax'];
        $master['tax_title']                 = $this->_platform->_ordersdf['tax_title'];
        $master['weight']                    = $this->_platform->_ordersdf['weight'];
        $master['title']                     = $this->_platform->_ordersdf['title'];
        $master['score_u']                   = $this->_platform->_ordersdf['score_u'];
        $master['score_g']                   = $this->_platform->_ordersdf['score_g'];
        $master['pay_bn']                    = $this->_platform->_ordersdf['pay_bn'];
        
        # 支付单结构
        $payment_list = isset($this->_platform->_ordersdf['payments']) ? $this->_platform->_ordersdf['payments'] : array($this->_platform->_ordersdf['payment_detail']);
        if ($payment_list 
            && is_array($payment_list) 
            && $this->_platform->_ordersdf['payed'] >= $this->_platform->_tgOrder['payed']
            && $this->_platform->_tgOrder['pay_status'] != '1') {
            $last_payment = array_pop($payment_list);
            $master['paytime'] = $last_payment['pay_time'] ? kernel::single('ome_func')->date2time($last_payment['pay_time']) : time();
        }

        $master = array_filter($master,array($this,'filter_null'));

        $diff_master = array_udiff_assoc($master, $this->_platform->_tgOrder,array($this,'comp_array_value'));

        if ($diff_master) {
            $this->_platform->_newOrder = array_merge($this->_platform->_newOrder,$diff_master);

            if (in_array($this->_platform->_newOrder['pay_status'], array('6','7'))) {
               $this->_platform->_newOrder['pause'] = 'true';
            }      
        }

        $payinfo = array();
        $payinfo['pay_name']     = $this->_platform->_ordersdf['payinfo']['pay_name'];
        $payinfo['cost_payment'] = $this->_platform->_ordersdf['payinfo']['cost_payment'];
        $payinfo = array_filter($payinfo,array($this,'filter_null'));
        $diff_payinfo = array_udiff_assoc($payinfo, $this->_platform->_tgOrder['payinfo'],array($this,'comp_array_value'));
        if ($diff_payinfo) {
            $this->_platform->_newOrder['payinfo'] = array_merge((array)$this->_platform->_newOrder['payinfo'],$diff_payinfo);
        }

        if ($diff_payinfo['pay_bn']) {
            $payment_cfg = kernel::single('apibusiness_adapter_payment_cfg')->get_payment($this->_platform->_ordersdf['pay_bn'],$this->_platform->_shop['node_type']);
            $this->_platform->_newOrder['pay_bn']  = $payment_cfg['pay_bn'];
            $this->_platform->_newOrder['payinfo']['pay_name'] = $payment_cfg['custom_name'];
        }
    }
}