<?php
/**
* 订单明细--淘宝订单修改明细特殊处理
*
* @category apibusiness
* @package apibusiness/response/order/component/itemstfxv
* @author chenping<chenping@shopex.cn>
* @version $Id: items.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_itemstb extends apibusiness_response_order_component_abstract
{
    const _APP_NAME = 'ome';

    private $_obj_alias = array(
        'goods'       => '商品',
        'pkg'         => '捆绑商品',
        'gift'        => '赠品',
        'giftpackage' => '礼包',
    );

    /**
     * 数据格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {
        foreach ($this->_platform->_ordersdf['order_objects'] as $object) {
            if(empty($object['order_items'])) continue;
            
            $goods = array();

            if ($object['bn']) {
                //$goods = kernel::single('apibusiness_adapter_goods')->getGoodBybn($object['bn']);
                $goods = app::get(self::_APP_NAME)->model('goods')->dump(array('bn'=>$object['bn']));
            }

            $object_pmt = 0; $order_items = array();
            foreach ($object['order_items'] as $item) {
                
                $product = array();

                // 验证货品是否存在
                if ($item['bn']) {
                    // $product = kernel::single('apibusiness_adapter_products')->getProductBybn($item['bn']);
                    $product = app::get(self::_APP_NAME)->model('products')->dump(array('bn'=>$item['bn']));
                }

                if ( !$product ) {
                    foreach(kernel::servicelist('ome.product') as $instance){
                        if(method_exists($instance, 'getProductByBn')){
                            $product = $instance->getProductByBn($item['bn']);

                            if ($product) break;
                        }
                    }
                }

                if (!$product) {
                    $this->_platform->_newOrder['is_fail']     = 'true';
                    $this->_platform->_newOrder['edit_status'] = 'true';
                    $this->_platform->_newOrder['archive']     = '1';
                }

                $addon = '';
                if ($item['product_attr']) {
                    $addon = serialize(array('product_attr'=>$item['product_attr']));
                }
                $quantity = $item['quantity'] ? $item['quantity'] : 1;
                $subtotal = $item['amount'] ? (float)$item['amount'] : bcmul((float)$item['price'], $quantity,3);
                $sendnum = $this->_platform->_ordersdf['ship_status'] == '0' ? 0 : $item['sendnum'];
                $order_items[] = array(
                    'shop_goods_id'   => $item['shop_goods_id'] ? $item['shop_goods_id'] : 0,
                    'product_id'      => $product['product_id'] ? $product['product_id'] : 0,
                    'shop_product_id' => $item['shop_product_id'] ? $item['shop_product_id'] : 0,
                    'bn'              => $item['bn'],
                    'name'            => $item['name'],
                    'cost'            => (float)$item['cost'],
                    'price'           => (float)$item['price'],
                    'pmt_price'       => (float)$item['pmt_price'],
                    'sale_price'      => $item['sale_price'] ? $item['sale_price'] : bcsub($subtotal, (float)$item['pmt_price'],3),
                    'amount'          => $subtotal,
                    'weight'          => (float)$item['weight'],
                    'quantity'        => $quantity,
                    'addon'           => $addon,
                    'item_type'       => $item['item_type'] ? $item['item_type'] : 'product',
                    'score'           => (float)$item['score'],
                    'delete'          => ($item['status'] == 'close') ? 'true' : 'false',
                    'sendnum'         => $sendnum ? $sendnum : 0,
                    'original_str'    => $item['original_str'],
                    'product_attr'    => $item['product_attr'],
                );
                
                if($item['status'] != 'close')
                    $object_pmt += (float)$item['pmt_price'];
            }

            $obj_type = $object['obj_type'] ? $object['obj_type'] : 'goods';
            $obj_amount = $object['amount'] ? $object['amount'] : bcmul($object['quantity'], $object['price'],3);
            $obj_sale_price = $object['sale_price'] ? $object['sale_price'] :  bcsub($obj_amount,bcadd($object['pmt_price'], $object_pmt,3),3);
            $this->_platform->_newOrder['order_objects'][] = array(
                'obj_type'      => $obj_type,
                'obj_alias'     => $object['obj_alias'] ? $object['obj_alias'] : $this->_obj_alias[$obj_type],
                'shop_goods_id' => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                'goods_id'      => $goods['goods_id'] ? $goods['goods_id'] : 0,
                'bn'            => $object['bn'] ? $object['bn'] : null,
                'name'          => $object['name'],
                'price'         => $object['price'] ? (float)$object['price'] : bcdiv($obj_amount,$object['quantity'],3),
                'amount'        => $obj_amount,
                'quantity'      => $object['quantity'],
                'weight'        => (float)$object['weight'],
                'score'         => (float)$object['score'],
                'pmt_price'     => (float)$object['pmt_price'],
                'sale_price'    => $obj_sale_price,
                'order_items'   => $order_items,
                'is_oversold'   => ($object['is_oversold'] == true) ? 1 : 0,
                'oid'           => $object['oid'],
            );
        }
    }

    /**
     * 更新订单明细
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        // 后期修改
        if ($this->_platform->_tgOrder['ship_status'] == '0') {
            $productModel = app::get(self::_APP_NAME)->model('products');

            #比对是否有变化的字段列表
            $compre_obj = array('oid','obj_type','obj_alias','name','price','amount','quantity','pmt_price','sale_price','weight','score');
            $compre_items = array('name','cost','price','amount','pmt_price','sale_price','weight','quantity','item_type','score','delete','addon');

            // 原单处理
            $tgOrder_object = array();
            foreach ((array)$this->_platform->_tgOrder['order_objects'] as $object) {
                $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type']) . '-' . trim($object['oid'])));
                $tgOrder_object[$objkey] = $object;

                $order_items = array();
                foreach ((array)$object['order_items'] as $item) {
                    $salepricekey = bcdiv((float) $item['sale_price'], $item['quantity'],3);
                    $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type']) . '-' . $salepricekey));
                    $order_items[$itemkey] = $item;
                }
                $tgOrder_object[$objkey]['order_items'] = $order_items;
            }

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

                $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type'] . '-' . trim($object['oid']))));
                
                $goods = array();

                if ($object['bn']) {
                    if($object['obj_type'] == 'pkg' && $object['goods_id']){
                        $goods['goods_id'] = $object['goods_id'];
                    } else {
                        $goods = app::get(self::_APP_NAME)->model('goods')->dump(array('bn'=>$object['bn']));
                    }
                }

                $object_pmt = 0; $order_items = array();
                foreach ((array)$object['order_items'] as $item) {

                    $product = array();
                    
                    // 验证货品是否存在
                    if ($item['bn']) {
                        //$product = kernel::single('apibusiness_adapter_products')->getProductBybn($item['bn']);
                        $product = $productModel->dump(array('bn'=>$item['bn']));
                    }

                    if ( !$product ) {
                        foreach(kernel::servicelist('ome.product') as $instance){
                            if(method_exists($instance, 'getProductByBn')){
                                $product = $instance->getProductByBn($item['bn']);

                                if ($product) break;
                            }
                        }
                    }

                    //$addon = serialize(array('product_attr'=>$item['product_attr']));
                    //if ($product['product_id']) {
                    $addon = app::get(self::_APP_NAME)->model('orders')->_format_productattr($item['product_attr'],$product['product_id'],$item['original_str']);
                    //}
                    
                    $quantity = $item['quantity'] ? $item['quantity'] : 1;
                    $subtotal = $item['amount'] ? $item['amount'] : bcmul((float)$item['price'], $quantity,3);
                    $sendnum = $ordersdf['ship_status'] == '0' ? 0 : $item['sendnum'];
                    $item_sale_price = $item['sale_price'] ? (float)$item['sale_price'] : bcsub($subtotal, (float)$item['pmt_price'],3);

                    $salepricekey = bcdiv((float) $item_sale_price, $item['quantity'],3);
                    $itemkey = sprintf('%u',crc32(trim($item['bn']) . '-' . trim($item['item_type']) . '-' . $salepricekey));
                    $order_items[$itemkey] = array(
                        'shop_goods_id'   => $item['shop_goods_id'] ? $item['shop_goods_id'] : 0,
                        'product_id'      => $product['product_id'] ? $product['product_id'] : 0,
                        'shop_product_id' => $item['shop_product_id'] ? $item['shop_product_id'] : 0,
                        'bn'              => $item['bn'],
                        'name'            => $item['name'],
                        'cost'            => (float)$item['cost'],
                        'price'           => (float)$item['price'],
                        'pmt_price'       => (float)$item['pmt_price'],
                        'sale_price'      => $item_sale_price,
                        'amount'          => $subtotal,
                        'weight'          => $item['weight'],
                        'quantity'        => $quantity,
                        'addon'           => $addon,
                        'item_type'       => $item['item_type'] ? $item['item_type'] : 'product',
                        'score'           => (float)$item['score'],
                        'delete'          => ($item['status'] == 'close') ? 'true' : 'false',
                        //'sendnum'         => $sendnum ? $sendnum : 0,
                        'order_id'       => $this->_platform->_tgOrder['order_id'],
                    );
                    
                    if ($item['status'] != 'close') {
                        $object_pmt += (float)$item['pmt_price'];
                    }
                }

                $obj_type = $object['obj_type'] ? $object['obj_type'] : 'goods';
                $obj_amount = $object['amount'] ? (float)$object['amount'] : bcmul($object['quantity'], $object['price'],3);
                $obj_sale_price = $object['sale_price'] ? (float)$object['sale_price'] :  bcsub($obj_amount,bcadd((float)$object['pmt_price'], $object_pmt,3),3);
                $ordersdf_object[$objkey] = array(
                    'obj_type'      => $obj_type,
                    'obj_alias'     => $object['obj_alias'] ? $object['obj_alias'] : $this->_obj_alias[$obj_type],
                    'shop_goods_id' => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                    'goods_id'      => $goods['goods_id'] ? $goods['goods_id'] : 0,
                    'bn'            => $object['bn'] ? $object['bn'] : null,
                    'name'          => $object['name'],
                    'price'         => $object['price'] ? (float)$object['price'] : bcdiv($obj_amount,$object['quantity'],3),
                    'amount'        => $obj_amount,
                    'quantity'      => $object['quantity'],
                    'weight'        => (float)$object['weight'],
                    'score'         => (float)$object['score'],
                    'pmt_price'     => (float)$object['pmt_price'],
                    'sale_price'    => (float)$obj_sale_price,
                    'order_items'   => $order_items,
                    'is_oversold'   => ($object['is_oversold'] == true) ? 1 : 0,
                    'oid'           => $object['oid'],
                    'order_id'       => $this->_platform->_tgOrder['order_id'],
                );
            }
            $tmp_ordersdf = array(
                'shop_type' => $this->_platform->_shop['shop_type'],
                'order_objects' => $ordersdf_object,
            );
            if($order_sdf_service = kernel::service('ome.service.order.sdfdata')){
                if(method_exists($order_sdf_service,'modify_sdfdata')){
                    $tmp_ordersdf = $order_sdf_service->modify_sdfdata($tmp_ordersdf);
                    $ordersdf_object = $tmp_ordersdf['order_objects'];
                }
            }

            // 订单object 如果在接收到的数据里不存在,则置为删除状态
            /*
            $no_object = array_diff_key($tgOrder_object, $ordersdf_object);
            if ($no_object) {
                foreach ($no_object as $objkey=>$object) {
                    $obj_id = $tgOrder_object[$objkey]['obj_id'];
                    foreach ($object['order_items'] as $itemkey=>$item) {
                        $this->_platform->_newOrder['order_objects'][$objkey]['obj_id'] = $obj_id;

                        $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey] = array('item_id'=>$item['item_id'],'delete'=>'true');

                        // 扣库存
                        if ($item['product_id']) {
                            $productModel->chg_product_store_freeze($item['product_id'],$item['quantity'],'-');
                        }
                    }
                }
            }*/

            // 判断ITEM有没有
            foreach ($tgOrder_object as $objkey => $object) {
                foreach ($object['order_items'] as $itemkey=>$item) {
                    // 如果已经被删除，则跳过
                    if($item['delete'] == 'true') continue;

                    // ITEM被删除
                    if (!$ordersdf_object[$objkey]['order_items'][$itemkey]) {
                        $this->_platform->_newOrder['order_objects'][$objkey]['obj_id'] = $object['obj_id'];

                        $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey] = array('item_id'=>$item['item_id'],'delete'=>'true');

                        // 扣库存
                        if ($item['product_id']) {
                            $productModel->chg_product_store_freeze($item['product_id'],$item['quantity'],'-');
                        }
                    }
                }
            }

            // 字段比较
            foreach ($ordersdf_object as $objkey => $object) {
                $obj_id = $tgOrder_object[$objkey]['obj_id'];
                $order_items = $object['order_items']; unset($object['order_items']);

                $object = array_filter($object,array($this,'filter_null'));
                // OBJECT比较
                $diff_obj = array_udiff_assoc((array)$object, (array)$tgOrder_object[$objkey],array($this,'comp_array_value'));
                if ($diff_obj) {
                    $diff_obj['obj_id'] = $obj_id;

                    $this->_platform->_newOrder['order_objects'][$objkey] = array_merge((array)$this->_platform->_newOrder['order_objects'][$objkey],(array)$diff_obj);
                }

                foreach ($order_items as $itemkey => $item) {
                    $item = array_filter($item,array($this,'filter_null'));
                    // ITEM比较
                    $item_id = $tgOrder_object[$objkey]['order_items'][$itemkey]['item_id'];
                    $diff_item = array_udiff_assoc((array)$item, (array)$tgOrder_object[$objkey]['order_items'][$itemkey],array($this,'comp_array_value'));

                    if ($diff_item) {
                        $diff_item['item_id'] = $item_id;

                        $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey] = array_merge((array)$this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey],(array)$diff_item);

                        // 如果货品不存在，置为失败
                        if (!$item['product_id']) {
                            $this->_platform->_newOrder['is_fail']     = 'true';
                            $this->_platform->_newOrder['edit_status'] = 'true';
                            $this->_platform->_newOrder['archive']     = '1';  
                        }

                        if ($diff_item['delete'] == 'false' && $item['product_id']) {
                            $productModel->chg_product_store_freeze($item['product_id'],$item['quantity'],'+');
                        } elseif ($diff_item['delete'] == 'true' && $item['product_id']) {
                            $productModel->chg_product_store_freeze($item['product_id'],$tgOrder_object[$objkey]['order_items'][$itemkey]['quantity'],'-');
                        } elseif (isset($diff_item['quantity']) && $item['product_id']) {
                            // 如果库存发生变化，
                            $diff_quantity = bcsub($diff_item['quantity'], $tgOrder_object[$objkey]['order_items'][$itemkey]['quantity']);
                            $operator = $diff_quantity > 0 ? '+' : '-';

                            // 是多了，还是少了
                            $productModel->chg_product_store_freeze($item['product_id'],abs($diff_quantity),$operator);
                        }
                    
                        $this->_platform->_newOrder['order_objects'][$objkey]['obj_id'] = $obj_id;
                    }

                    // 如果货品不存在，置为失败
                    if (!$item['product_id']) {
                        $this->_platform->_newOrder['is_fail']     = 'true';
                        $this->_platform->_newOrder['edit_status'] = 'true';
                        $this->_platform->_newOrder['archive']     = '1';  
                    }

                }
            }

            if ($this->_platform->_newOrder['is_fail'] != 'true' && $this->_platform->_tgOrder['is_fail'] == 'true') {
                $this->_platform->_newOrder['is_fail']     = 'false';
                $this->_platform->_newOrder['edit_status'] = 'false';
                $this->_platform->_newOrder['archive']     = '0';        
            }

            // 日志
            if ($this->_platform->_newOrder['order_objects']) {
                //后期修改 分销订单的处理
                $logModel = app::get(self::_APP_NAME)->model('operation_log');
                $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'订单商品信息被修改');
            }
        }
    }
}