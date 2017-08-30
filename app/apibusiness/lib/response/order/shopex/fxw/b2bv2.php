<?php
/**
* fxw(分销王系统)分销订单处理 版本二
*
* @category apibusiness
* @package apibusiness/response/order/shopex/fxw
* @author chenping<chenping@shopex.cn>
* @version $Id: b2bv2.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_shopex_fxw_b2bv2 extends apibusiness_response_order_shopex_fxw_abstract
{
    private $_use_itemtfxv = false;
    /**
     * 淘分销订单，相同货号，类型，价格的订单明细合并
     *
     * @return void
     * @author 
     **/
    private function mergeItemsForB2b()
    {
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {

            $order_items = array(); $replace = false;
            foreach ($object['order_items'] as $item) {
                // 销售单价
                $sale_price = bcdiv((float)$item['sale_price'], $item['quantity'],3);

                $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type']) . '-' . $sale_price));

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
     * 数据重组
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        parent::reTransSdf();

        $this->mergeItemsForB2b();
    }

    /**
     * 获取格式转换组件
     *
     * @return void
     * @author 
     **/
    protected function get_convert_components()
    {
        //$components = array('master','items','shipping','consignee','consigner','custommemo','markmemo','marktype');
        $components = parent::get_convert_components();
        $key = array_search('shopexitems',$components);
        if ($key !== false && $this->_use_itemtfxv === true) {
            $components[$key] = 'itemstfxv';
        }

        return $components;
    }

    protected function accept_dead_order()
    {
       $result = parent::accept_dead_order();
        if ($result === false) {
            if ($this->_ordersdf['status'] == 'dead' ) {
                unset($this->_apiLog['info']['msg']);
                return true;
            }
        }
       return $result; 
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

    protected function get_update_components()
    {
        $components = parent::get_update_components();
        
        $components[] = 'consigner';

        $key = array_search('shopexitems',$components);
        if ($key !== false && $this->_use_itemtfxv === true) {
            $components[$key] = 'itemstfxv';
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

        $plugins[] = 'sellingagent';
        
        //$key = array_search('refundapply', $plugins);
        //if($key !== false) unset($plugins[$key]);
        
        return $plugins;
    }
}