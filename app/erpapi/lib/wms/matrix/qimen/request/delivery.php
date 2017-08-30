<?php
/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_qimen_request_delivery extends erpapi_wms_request_delivery
{
    private $_shop_type_mapping = array(
        'taobao'    => 'TB',
        'paipai'    => 'PP',
        '360buy'    => 'JD',
        'yihaodian' => 'YHD',
        'qq_buy'    => 'QQ',
        'dangdang'  => 'DD',
        'amazon'    => 'AMAZON',
        'yintai'    => 'YT',
        'vjia'      => 'FK',
        'alibaba'   => '1688',
        'suning'    => 'SN',
        'gome'      => 'GM',
        'mogujie'   => 'MGJ',
    );

    public function delivery_cancel($sdf){
        $delivery_bn = $sdf['outer_delivery_bn'];
        $oDelivery_extension = app::get('console')->model('delivery_extension');
        $dextend = $oDelivery_extension->dump(array('delivery_bn'=>$delivery_bn)); 


        $title = $this->__channelObj->wms['channel_name'].'发货单取消';

        $params = array(
            'order_type'     => 'OUT_SALEORDER',
            'out_order_code' => $delivery_bn,
            'warehouse_code' => $this->get_warehouse_code($sdf['branch_bn']),
            'order_id'       => $dextend['original_delivery_bn'],
        );

        return $this->__caller->call(WMS_ORDER_CANCEL, $params, null, $title,10,$delivery_bn);
    }


    protected function _format_delivery_create_params($sdf)
    {
        $params = parent::_format_delivery_create_params($sdf);
        $params['warehouse_code'] = $this->get_warehouse_code($sdf['branch_bn']);

        // 发货人信息
        $shop = app::get('ome')->model('shop')->dump($sdf['shop_id']);
        $area = explode(':',$shop['area']);
        list($shipper_state,$shipper_city,$shipper_area) = explode('/',$area[1]);

        $params['shipper_name']    = $shop['default_sender'];
        $params['shipper_mobile']  = $shop['mobile'];
        $params['shipper_state']   = $shipper_state;
        $params['shipper_city']    = $shipper_city;
        $params['shipper_district'] = $shipper_area;
        $params['shipper_address'] = $shop['addr'];
        $params['shipper_zip'] = $shop['zip'];
        $params['order_flag']      = $params['is_cod'] == 'true' ? 'COD' : '';
        $params['order_source']    = $this->_shop_type_mapping[$sdf['shop_type']]  ?  $this->_shop_type_mapping[$sdf['shop_type']] : 'OTHER';

        // 判断是不是天猫
        if ($sdf['shop_type']=='taobao' && $sdf['shop_id']) {
            $shop = app::get('ome')->model('shop')->dump(array('shop_id'=>$sdf['shop_id'],'tbbusiness_type'=>'B'));
            if ($shop) {
                $params['order_source'] = 'TM';
            }
        }

        $params['operate_time']    = date('Y-m-d H:i:s');
        
        $items = array('item'=>array()); $delivery_items = $sdf['delivery_items'];
        if ($delivery_items){
            sort($delivery_items);
            foreach ($delivery_items as $k => $v){
                $foreignsku = app::get('console')->model('foreign_sku')->dump(array('wms_id'=>$this->__channelObj->wms['channel_id'],'inner_sku'=>$v['bn']));

                $items['item'][] = array(
                    'item_code'       => $v['bn'],
                    'item_name'       => $v['product_name'],
                    'item_quantity'   => $v['number'],
                    'item_price'      => $v['price'],
                    'item_line_num'   => ($k + 1),// 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => $sdf['order_bn'],//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'         => $foreignsku['outer_sku'] ? $foreignsku['outer_sku'] : $v['bn'],// 外部系统商品sku
                    'is_gift'         => $v['is_gift'] == 'ture' ? '1' : '0',// 是否赠品
                    'item_remark'     => $v['memo'],// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => $v['sale_price']//成交额
                );
            }
        }

        $params['items'] = json_encode($items);


        if ($sdf['relate_order_bn']) {
            $params['order_type'] = 'HHCK';

            // 获取原始发货单号
            $order = app::get('ome')->model('orders')->dump(array('order_bn'=>$sdf['relate_order_bn']),'order_id');
            $delivery_orders = app::get('ome')->model('delivery_order')->getList('delivery_id',array('order_id'=>$order['order_id']));

            foreach ($delivery_orders as $delivery_order) {
                $delivery_ids[] = $delivery_order['delivery_id'];
            }

            $delivery = app::get('ome')->model('delivery')->dump(array('delivery_id'=>$delivery_ids,'parent_id'=>0,'process'=>'true','status'=>'succ'),'delivery_bn');

            $delivery_extend = app::get('console')->model('delivery_extension')->dump(array('delivery_bn'=>$delivery['delivery_bn']));
            $params['wms_order_code'] = $delivery_extend['original_delivery_bn'];

            $params['orig_order_code'] = $delivery['delivery_bn'];
        }

        return $params;
    }
}