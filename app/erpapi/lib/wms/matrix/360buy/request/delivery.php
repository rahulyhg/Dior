<?php
/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_360buy_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        // 明细
        $delivery_items = $sdf['delivery_items'];
        $items = array('item'=>array());
        if ($delivery_items) {
            sort($delivery_items);
            $oForeign_sku = app::get('console')->model('foreign_sku');

            foreach ($delivery_items as $k=>$v){
                $item_code = $oForeign_sku->get_product_outer_sku($this->__channelObj->wms['channel_id'], $v['bn']);
                $items['item'][] = array(
                    'item_code'       => $item_code,
                    'item_name'       => $v['product_name'],
                    'item_quantity'   => $v['number'],
                    'item_price'      => $v['price'],
                    'item_line_num'   => ($k + 1),// 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => $sdf['order_bn'],//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'         => $v['bn'],// 外部系统商品sku
                    'is_gift'         => $v['is_gift'] == 'ture' ? '1' : '0',// 是否赠品
                    'item_remark'     => $v['memo'],// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => $v['sale_price']//成交额
                );
            }
        }

        $params = parent::_format_delivery_create_params($sdf);

        $params['items']             = json_encode($items);
        $params['wms_order_code']    = $sdf['order_bn'];
        $params['receiver_phone']    = $sdf['consignee']['telephone'] ? $sdf['consignee']['telephone'] : '13222222222';
        $params['receiver_mobile']   = $sdf['consignee']['mobile'] ? $sdf['consignee']['mobile'] : '333333';

        $params['memo']              = '不就备注嘛。我填呀';
        $params['expect_start_time'] = date('Y-m-d H:i:s'); //过期时间
        $params['warehouse_code']    = $this->get_warehouse_code($sdf['branch_bn']);

        return $params;
    }

    protected function _format_delivery_cancel_params($sdf)
    {
        $oDelivery_ext = app::get('console')->model('delivery_extension');

        $delivery_ext = $oDelivery_ext->dump(array('delivery_bn'=>$sdf['outer_delivery_bn']),'original_delivery_bn');
        
        $params = array(
            'warehouse_code' => $sdf['branch_bn'],
            'out_order_code' => $delivery_ext['original_delivery_bn'],
        );
        
        return $params;

    }
}