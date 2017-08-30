<?php
/**
* 促销插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: promotion.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_promotion extends apibusiness_response_order_plugin_abstract
{
    const _APP_NAME = 'ome';
    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        if ($this->_platform->_ordersdf['pmt_detail']) {
            $pmt_addon = $this->_platform->_newOrder['createtime'];
            kernel::single('ome_order_func')->update_pmt($this->_platform->_newOrder['order_id'],$this->_platform->_newOrder['shop_id'],$this->_platform->_ordersdf['pmt_detail'],$pmt_addon,$old_pmt);

            $this->_platform->_apiLog['info'][] = '优惠信息标准$sdf结构：'.var_export($this->_platform->_ordersdf['pmt_detail'],true);
        }
    }

    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function preUpdate()
    {
        $pmtDescript = kernel::single('ome_order_func')->update_pmt(
            $this->_platform->_tgOrder['order_id'],
            $this->_platform->_shop['shop_id'],
            $this->_platform->_ordersdf['pmt_detail'],
            $this->_platform->_tgOrder['createtime'],
            $old_pmt);

        if ($pmtDescript) {
            $this->_platform->_apiLog['info'][] = '订单优惠发生变化，$sdf结构：'.var_export($this->_platform->_ordersdf['pmt_detail'],true);

            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],"修改订单优惠方案");

            // 更新前的优惠方案
            $this->_platform->_tgOrder['pmt'] = $old_pmt;
        }
    }
}