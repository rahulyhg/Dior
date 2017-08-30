<?php
/**
* fxw(分销王系统)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/shopex/fxw
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_shopex_fxw_abstract extends apibusiness_response_order_shopex_abstract
{
    /**
     * 订单操作：创建 OR 更新
     *
     * @return void
     * @author 
     **/
    protected function operationSel()
    {
        parent::operationSel();

        // 为下代码为兼容，客户支付后打过来的更新时间小于创建时间
        if (empty($this->_operationsel) 
            && $this->_tgOrder
            && $this->_tgOrder['pay_status'] == '0'
            && $this->_ordersdf['pay_status'] == '1'
            && $this->_ordersdf['payments']) {
                $this->_operationsel = 'update';
        }
    }

    protected function reTransSdf()
    {
        parent::reTransSdf();

        // 重新计算优惠，兼容分销王将商品优惠，打在订单优惠上
        // 验证订单金额是否正确
        $total_amount = (float) $this->_ordersdf['cost_item'] 
                                + (float) $this->_ordersdf['shipping']['cost_shipping'] 
                                + (float) $this->_ordersdf['shipping']['cost_protect'] 
                                + (float) $this->_ordersdf['discount'] 
                                + (float) $this->_ordersdf['cost_tax'] 
                                + (float) $this->_ordersdf['payinfo']['cost_payment'] 
                                - (float) $this->_ordersdf['pmt_goods'] 
                                - (float) $this->_ordersdf['pmt_order'];
        if(0 != bccomp($total_amount, $this->_ordersdf['total_amount'],3)){
            $pmt_goods = $cost_item = 0;
            foreach ($this->_ordersdf['order_objects'] as $objkey => $object){
                foreach ($object['order_items'] as $itemkey => $item){
                    $amount = (float) $item['price'] * $item['quantity'];
                    $pmt_price = $amount - (float) $item['sale_price'];
                    $pmt_goods += $pmt_price;
                    $cost_item += (float) $item['sale_price'];

                    // 判断优惠金额是否被记录
                    if(0 != bccomp($item['pmt_price'], $pmt_price,3)){
                        $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['pmt_price'] = (string) $pmt_price;
                        //echo 1;exit;
                    }
                }
            }
            if(0 == bccomp($pmt_goods, 0,3)){
                // 去除订单优惠的总额
                $total_amount = (float) $this->_ordersdf['cost_item'] 
                                        + (float) $this->_ordersdf['shipping']['cost_shipping'] 
                                        + (float) $this->_ordersdf['shipping']['cost_protect'] 
                                        + (float) $this->_ordersdf['discount'] 
                                        + (float) $this->_ordersdf['cost_tax'] 
                                        + (float) $this->_ordersdf['payinfo']['cost_payment'] 
                                        - (float) $this->_ordersdf['pmt_goods'];
                if(0 == bccomp($total_amount, $this->_ordersdf['total_amount'],3) && 0 != bccomp($this->_ordersdf['pmt_order'], 0,3)){
                    // 订单优惠置0
                    $this->_ordersdf['pmt_order'] = '0';//echo 2;exit;
                    return true;
                }
                return true;
            }

            // 如果从订单优惠中除去商品优惠后，总金额匹配，进行数据修复
            $pmt_order = (float) $this->_ordersdf['pmt_order'] - $pmt_goods;
            $pmt_order = $pmt_order > 0 ? $pmt_order : 0;
            $total_amount = (float) $this->_ordersdf['cost_item'] 
                                    + (float) $this->_ordersdf['shipping']['cost_shipping'] 
                                    + (float) $this->_ordersdf['shipping']['cost_protect'] 
                                    + (float) $this->_ordersdf['discount'] 
                                    + (float) $this->_ordersdf['cost_tax'] 
                                    + (float) $this->_ordersdf['payinfo']['cost_payment'] 
                                    - (float) $this->_ordersdf['pmt_goods']
                                    - $pmt_order;
            if( 0 == bccomp($total_amount, $this->_ordersdf['total_amount'],3)){
                $this->_ordersdf['pmt_order'] = (string) $pmt_order;
                
                // 判断商品总金额是否是扣除商品优惠后的金额
                if(0 == bccomp($cost_item, $this->_ordersdf['cost_item'],3)){
                    $this->_ordersdf['cost_item'] += $pmt_goods;
                    $this->_ordersdf['pmt_goods'] += $pmt_goods;//echo 3;exit;

                    $this->_ordersdf['cost_item'] = (string) $this->_ordersdf['cost_item'];
                    $this->_ordersdf['pmt_goods'] = (string) $this->_ordersdf['pmt_goods'];
                }
            }
        } else {
            // 重新计算优惠金额
            $pmt_goods = $cost_item = 0;
            foreach ($this->_ordersdf['order_objects'] as $objkey => $object){
                foreach ($object['order_items'] as $itemkey => $item){
                    $cost_item += (float) $item['sale_price'];

                    $amount = (float) $item['price'] * $item['quantity'];
                    $pmt_price = $amount - (float) $item['sale_price'];
                    if(1 != bccomp($pmt_price, 0,3)){
                        continue;
                    }
                    $pmt_goods += $pmt_price;

                    // 判断优惠金额是否被记录
                    if(0 == bccomp($item['pmt_price'], 0,3)){
                        $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['pmt_price'] = (string) $pmt_price;

                    }
                }
            }

            // 重新计算商品总额
            if(0 == bccomp($this->_ordersdf['pmt_goods'], 0,3) 
                && 0 == bccomp($this->_ordersdf['cost_item'], $cost_item,3)
                && 1 == bccomp($pmt_goods, 0,3)){

                $this->_ordersdf['cost_item'] += $pmt_goods;
                $this->_ordersdf['pmt_goods'] += $pmt_goods;//echo 3;exit;

                $this->_ordersdf['cost_item'] = (string) $this->_ordersdf['cost_item'];
                $this->_ordersdf['pmt_goods'] = (string) $this->_ordersdf['pmt_goods'];
            }
        }
        //echo 22;exit;
    }
}