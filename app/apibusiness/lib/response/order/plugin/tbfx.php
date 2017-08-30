<?php
/**
* 淘宝分销订单
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: tbfx.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_tbfx extends apibusiness_response_order_plugin_abstract
{

    /**
     * 订单保存前，会员信息操作
     *
     * @return void
     * @author
     **/
    public function preCreate()
    {
        $ordersdf = $this->_platform->_ordersdf;
        // 如果有OME捆绑插件设定的捆绑商品，则自动拆分
        /*
        if($oPkg = kernel::service('omepkg_order_split')){
            if(method_exists($oPkg,'order_split')){
                $ordersdf = $oPkg->order_split($ordersdf);
            }
        }*/

        // 接收的参数
        $ordersdf_object = array();
        foreach ((array)$ordersdf['order_objects'] as $object) {
            if(empty($object['order_items'])) continue;

            $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type'])));

            $object_pmt = 0; $order_items = array();
            foreach ((array)$object['order_items'] as $item) {
                $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type'])));

                $order_items[$itemkey] = $item;
            }

            $object['order_items'] = $order_items;

            $ordersdf_object[$objkey] = $object;
        }

        if ($this->_platform->_newOrder['order_objects']) {
            foreach ($this->_platform->_newOrder['order_objects'] as $objkey => $object) {
                $fobjkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type'])));
                foreach ($object['order_items'] as $itemkey => $item) {
                    $fitemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type'])));
                    $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey]['fx_oid'] = $ordersdf_object[$fobjkey]['order_items'][$fitemkey]['fx_oid'];
                    $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey]['buyer_payment'] = $ordersdf_object[$fobjkey]['order_items'][$fitemkey]['buyer_payment'];
                    $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey]['cost_tax'] = $ordersdf_object[$fobjkey]['order_items'][$fitemkey]['cost_tax'];
                }

                $this->_platform->_newOrder['order_objects'][$objkey]['fx_oid'] = $ordersdf_object[$fobjkey]['fx_oid'];
                $this->_platform->_newOrder['order_objects'][$objkey]['tc_order_id'] = $ordersdf_object[$fobjkey]['tc_order_id'];
                $this->_platform->_newOrder['order_objects'][$objkey]['buyer_payment'] = $ordersdf_object[$fobjkey]['buyer_payment'];
                $this->_platform->_newOrder['order_objects'][$objkey]['cost_tax'] = $ordersdf_object[$fobjkey]['cost_tax'];
                $this->_platform->_newOrder['order_objects'][$objkey]['tc_order_id'] = $ordersdf_object[$fobjkey]['tc_order_id'];
            }

            $this->_platform->_newOrder['fx_order_id'] = $this->_platform->_ordersdf['fx_order_id'];
            $this->_platform->_newOrder['tc_order_id'] = $this->_platform->_ordersdf['tc_order_id'];
        }
    }

        /**
     * 订单完成后处理
     *
     * @return void
     * @author
     **/
    public function postCreate()
    {
        kernel::single('ome_service_c2c_taobao_order')->save_tbfx_order($this->_platform->_newOrder);

        $this->_platform->_apiLog['info'][] = '保存淘分销关联信息';
    }

    /**
     * 更新前处理
     *
     * @return void
     * @author
     **/
    public function preUpdate()
    {
        $ordersdf = $this->_platform->_ordersdf;
        // 如果有OME捆绑插件设定的捆绑商品，则自动拆分
        if($oPkg = kernel::service('omepkg_order_split')){
            if(method_exists($oPkg,'order_split')){
                $ordersdf = $oPkg->order_split($ordersdf);
            }
        }

        // 接收的参数
        $ordersdf_object = array();
        foreach ((array)$ordersdf['order_objects'] as $object) {
            if(empty($object['order_items'])) continue;

            if ($this->_platform->_use_itemtfxv === true) {
                $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type']) . '-' . trim($object['oid'])));
            } else {
                $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type'])));          
            }


            $object_pmt = 0; $order_items = array();
            foreach ((array)$object['order_items'] as $item) {
                if ($this->_platform->_use_itemtfxv === true) {
                    $quantity = $item['quantity'] ? $item['quantity'] : 1;
                    $subtotal = $item['amount'] ? $item['amount'] : bcmul((float)$item['price'], $quantity,3);
                    $item_sale_price = $item['sale_price'] ? (float)$item['sale_price'] : bcsub($subtotal, (float)$item['pmt_price'],3);

                    $salepricekey = bcdiv((float) $item_sale_price, $item['quantity'],3);
                    $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type']) . '-' . $salepricekey));
                } else {
                    $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type'])));                    
                }

                $order_items[$itemkey] = $item;
            }

            $object['order_items'] = $order_items;

            $ordersdf_object[$objkey] = $object;
        }

        if ($this->_platform->_newOrder['order_objects']) {
            foreach ($this->_platform->_newOrder['order_objects'] as $objkey => $object) {
                foreach ($object['order_items'] as $itemkey => $item) {
                    $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey]['fx_oid'] = $ordersdf_object[$objkey]['order_items'][$itemkey]['fx_oid'];
                    $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey]['buyer_payment'] = $ordersdf_object[$objkey]['order_items'][$itemkey]['buyer_payment'];
                    $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey]['cost_tax'] = $ordersdf_object[$objkey]['order_items'][$itemkey]['cost_tax'];
                }

                $this->_platform->_newOrder['order_objects'][$objkey]['fx_oid'] = $ordersdf_object[$objkey]['fx_oid'];
                $this->_platform->_newOrder['order_objects'][$objkey]['tc_order_id'] = $ordersdf_object[$objkey]['tc_order_id'];
                $this->_platform->_newOrder['order_objects'][$objkey]['buyer_payment'] = $ordersdf_object[$objkey]['buyer_payment'];
                $this->_platform->_newOrder['order_objects'][$objkey]['cost_tax'] = $ordersdf_object[$objkey]['cost_tax'];
            }
        }

    }

    /**
     * 更新完成后处理
     *
     * @return void
     * @author
     **/
    public function postUpdate()
    {
        // 原单处理
        $tgOrder_object = array();
        foreach ((array)$this->_platform->_tgOrder['order_objects'] as $object) {
            if ($this->_platform->_use_itemtfxv === true) {
                $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type']) . '-' . trim($object['oid'])));
            } else {
                $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type'])));
            }
            
            $tgOrder_object[$objkey] = $object;

            $order_items = array();
            foreach ((array)$object['order_items'] as $item) {
                if ($this->_platform->_use_itemtfxv === true) {
                    $salepricekey = bcdiv((float) $item['sale_price'], $item['quantity'],3);
                    $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type']) . '-' . $salepricekey));
                } else {
                    $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type'])));
                }
                
                $order_items[$itemkey] = $item;
            }
            $tgOrder_object[$objkey]['order_items'] = $order_items;
        }

        $must_add_tbfxsdf = array();
        $must_update_tbfxsdf = array();

        if ($this->_platform->_newOrder['order_objects']) {
            foreach ($this->_platform->_newOrder['order_objects'] as $objkey => $object) {
                foreach ($object['order_items'] as $itemkey => $item) {
                    if ($tgOrder_object[$objkey]['order_items'][$itemkey]) {
                        if (!is_null($item['buyer_payment'])) {
                            $must_update_tbfxsdf['order_items'][] = array(
                                'order_id' => $this->_platform->_newOrder['order_id'],
                                'obj_id' => $object['obj_id'],
                                'item_id' => $item['item_id'],
                                'buyer_payment' => $item['buyer_payment'],
                                'cost_tax' => $item['cost_tax'],
                            );
                        }
                    } else {
                        $must_add_tbfxsdf['order_items'][] = array(
                            'order_id' => $this->_platform->_newOrder['order_id'],
                            'obj_id' => $object['obj_id'],
                            'item_id' => $item['item_id'],
                            'buyer_payment' => $item['buyer_payment'],
                            'cost_tax' => $item['cost_tax'],
                        );
                    }
                }

                if ($tgOrder_object[$objkey]) {
                    if (!is_null($object['buyer_payment'])) {
                        $must_update_tbfxsdf['order_objects'][] = array(
                            'order_id' => $this->_platform->_newOrder['order_id'],
                            'obj_id' => $object['obj_id'],
                            'fx_oid' => $object['fx_oid'],
                            'tc_order_id' => $object['tc_order_id'],
                            'buyer_payment' => $object['buyer_payment'],
                            'cost_tax' => $object['cost_tax'],
                        );
                    }
                } else {
                    $must_add_tbfxsdf['order_objects'][] = array(
                        'order_id' => $this->_platform->_newOrder['order_id'],
                        'obj_id' => $object['obj_id'],
                        'fx_oid' => $object['fx_oid'],
                        'tc_order_id' => $object['tc_order_id'],
                        'buyer_payment' => $object['buyer_payment'],
                        'cost_tax' => $object['cost_tax'],
                    );
                }
            }

            if ($must_add_tbfxsdf || $must_update_tbfxsdf) {

                $sdf = $this->_platform->_ordersdf;
                $sdf['shop_type'] = $this->_platform->_shop['shop_type'];
                //处理淘宝分销订单
                kernel::single('ome_service_c2c_taobao_order')->update_tbfx_order($sdf,$must_add_tbfxsdf,$must_update_tbfxsdf);

                $this->_platform->_apiLog['info'][] = '更新淘分销关联信息：$must_add_tbfxsdf:'.var_export($must_add_tbfxsdf,true).'  $must_update_tbfxsdf:'.var_export($must_update_tbfxsdf,true);
            }
        }
    }
}