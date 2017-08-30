<?php
/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_cnss_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        $params = parent::_format_delivery_create_params($sdf);

        $items = array('item'=>array());
        $delivery_items = $sdf['delivery_items'];
        if ($delivery_items){
            sort($delivery_items);
            foreach ($delivery_items as $k => $v){
                $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');

                $items['item'][] = array(
                    'item_code'       => $product['barcode'],
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

        
        $params['items'] = json_encode($items);
        $params['logistics_no'] = $this->_get_electron_logi_no($sdf);

        if ($params['logistics_no'] === false) {
            return array();
        }
        
        return $params;
    }
}