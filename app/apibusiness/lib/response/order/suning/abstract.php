<?php
/**
* suning(苏宁平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/suning
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_suning_abstract extends apibusiness_response_order_abstractbase
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

        if (empty($this->_operationsel) && $this->_tgOrder['createtime'] < strtotime('2014-01-24 10:30:00')) {
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

        return true;
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

        if ($this->_tgOrder['createtime'] < strtotime('2014-06-18 14:30:00')) {
            $components[] = 'consignee';
        }

        return $components;
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
     * 对平台接收的数据纠正(有些是前端打的不对的)
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        parent::reTransSdf();

        // return ;
        /*
        $regionObj = kernel::single('apibusiness_response_order_suning_region');
        $region  = $regionObj->get_region($this->_ordersdf['consignee']['area_state'],$this->_ordersdf['consignee']['area_city'],$this->_ordersdf['consignee']['area_district']);

        $this->_ordersdf['consignee']['area_state']    = $region['provinceName'];
        $this->_ordersdf['consignee']['area_city']     = $region['cityName'];
        $this->_ordersdf['consignee']['area_district'] = $region['districtName'];
        */
        if (!$this->_ordersdf['lastmodify']) $this->_ordersdf['lastmodify'] = date('Y-m-d H:i:s');

        $this->_ordersdf['payinfo']['pay_name'] = '在线支付';

        // 获取货号(实际传的是货品ID)
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object) {           
            $goods = array();

            if (!$object['bn'] || $object['bn'] == $object['shop_goods_id']) {
                $goods = $this->items_custom_get($object['shop_goods_id']);
                
                $this->_ordersdf['order_objects'][$objkey]['bn'] = $goods['bn'];
            }

            if ($object['sale_price']) $this->_ordersdf['order_objects'][$objkey]['sale_price'] = round($object['sale_price'],3);

            foreach ($object['order_items'] as $itemkey => $item) {

                if ($goods) {
                    $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['bn'] = isset($goods['sku']) ? $goods['sku']['bn'] : $goods['bn'];
                    $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['product_attr'] = $goods['sku']['properties'] ? $goods['sku']['properties'] : array();  
                }

                if ($item['sale_price']) $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['sale_price'] = round($item['sale_price'],3);
            }
        }

        if ($this->_ordersdf['total_amount']) $this->_ordersdf['total_amount'] = round($this->_ordersdf['total_amount'],3);
        if ($this->_ordersdf['cur_amount']) $this->_ordersdf['cur_amount'] = round($this->_ordersdf['cur_amount'],3);

        // 判断订单优惠是否多余pmt_order
        $total_amount = (float) $this->_ordersdf['cost_item'] 
                                + (float) $this->_ordersdf['shipping']['cost_shipping'] 
                                + (float) $this->_ordersdf['shipping']['cost_protect'] 
                                + (float) $this->_ordersdf['discount'] 
                                + (float) $this->_ordersdf['cost_tax'] 
                                + (float) $this->_ordersdf['payinfo']['cost_payment'] 
                                - (float) $this->_ordersdf['pmt_goods'];

        if (0 == bccomp($total_amount, $this->_ordersdf['total_amount'],3) && 0 != bccomp($this->_ordersdf['pmt_order'], 0,3)) {
            $this->_ordersdf['pmt_order'] = '0';
        }
    }

    /**
     * 获取商品
     *
     * @return void
     * @author 
     **/
    protected function items_custom_get($num_iid)
    {
        $goods = array();

        $rs = kernel::single('apibusiness_router_request')->setShopId($this->_shop['shop_id'])->items_custom_get($num_iid);
        if ($rs->rsp == 'fail' || !$rs->data ){
            $this->_apiLog['info'][] = '获取商品('.$rs->msg_id.')失败：' . $num_iid;
            return array();
        }

        $data = json_decode($rs->data,true);
        $this->_apiLog['info'][] = '获取商品('.$rs->msg_id.')：' . $num_iid;

        if ($rs->rsp == 'succ' && $data) {
            $item = $data['item'];unset($data);

            $goods['bn']  = trim($item['outer_id']);
            $goods['iid'] = $item['iid'];

            if ($item['skus']['sku']) {
                foreach ($item['skus']['sku'] as $key => $value) {
                    $goods['sku']['bn']     = trim($value['outer_id']);
                    $goods['sku']['sku_id'] = $value['sku_id'];

                    $details_goods = $this->item_get($value['iid']);

                    $goods['sku']['properties'] = $details_goods['skus'][$value['sku_id']]['properties'];
                }
            }
        }

        return $goods;
    }

    protected function item_get($num_iid)
    {
        static $goods;

        if ($goods[$num_iid]) {
            return $goods[$num_iid];
        }

        $rs = kernel::single('apibusiness_router_request')->setShopId($this->_shop['shop_id'])->item_get($num_iid,$this->_shop['shop_id']);
        if ($rs->rsp == 'fail' || !$rs->data ){
            $this->_apiLog['info'][] = '获取商品详细('.$rs->msg_id.')失败：' . $num_iid;
            return array();
        }

        $data = json_decode($rs->data,true);
        $this->_apiLog['info'][] = '获取商品详细('.$rs->msg_id.')：' . $num_iid;

        if ($rs->rsp == 'succ' && $data) {
            $item = $data['item'];unset($data);

            $goods[$num_iid]['outer_id'] = $item['outer_id'];
            $goods[$num_iid]['iid']      = $item['iid'];

            if ($item['skus']['sku']) {
                foreach ($item['skus']['sku'] as $key => $value) {

                    $product_attr = array();

                    $goods[$num_iid]['skus'][$value['sku_id']]['sku_id'] = $value['sku_id'];
                    $goods[$num_iid]['skus'][$value['sku_id']]['outer_id'] = $value['outer_id'];
                    $goods[$num_iid]['skus'][$value['sku_id']]['iid'] = $value['iid'];
                    if ($value['properties']) {
                        $properties = array_filter(explode(';',$value['properties']));
                        foreach ($properties as $property) {
                            list($label_name,$label_value) = explode(':',$property);

                            $product_attr[] = array('label' => $label_name,'value' => $label_value);
                        }

                        $goods[$num_iid]['skus'][$value['sku_id']]['properties'] = $product_attr;
                    } else {
                        $goods[$num_iid]['skus'][$value['sku_id']]['properties'] = '';
                    }
                }
            }
        }

        return $goods[$num_iid];
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
        
        if ($this->_newOrder) {
            // 叫回发货单
            kernel::single('apibusiness_notice')->notice_process_order($this->_tgOrder,$this->_newOrder);
        }
    }
}