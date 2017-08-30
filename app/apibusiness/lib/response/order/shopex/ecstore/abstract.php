<?php
/**
* ecstore(ecstore系统)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/shopex/ecstore
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_shopex_ecstore_abstract extends apibusiness_response_order_shopex_abstract
{
    /**
     * 解析订单解构
     *
     * @return void
     * @author 
     **/
    protected function analysis()
    {
        parent::analysis();

        if ($this->_ordersdf['promotion_details'] && is_string($this->_ordersdf['promotion_details'])) {
            $this->_ordersdf['pmt_detail'] = array();
            $pmt_detail = json_decode($this->_ordersdf['promotion_details']);
            foreach ($pmt_detail as $key => $value) {
                $this->_ordersdf['pmt_detail'][$key]['pmt_describe'] = trim($value['promotion_name']);
                $this->_ordersdf['pmt_detail'][$key]['pmt_amount'] = trim($value['promotion_fee']);
            }
        }
    }

    /**
     * ecstore 0元订单打下来。状态变化更新时间不变化
     *
     * @return void
     * @author
     **/
    protected function operationSel()
    {
        parent::operationSel();
        $lastmodify = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);
        if (empty($this->_operationsel) && $lastmodify == $this->_tgOrder['outer_lastmodify']) {
            if ($this->_tgOrder['pay_status'] == '0' && $this->_ordersdf['pay_status'] == '1' && 0 == bccomp($this->_ordersdf['total_amount'], 0,3)) {
                $this->_operationsel = 'update';
            }
            
        }
    }
}