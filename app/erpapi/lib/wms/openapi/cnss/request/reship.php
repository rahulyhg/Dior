<?php
/**
 * 退货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_cnss_request_reship extends erpapi_wms_request_reship
{
    protected function _format_reship_create_params($sdf)
    {
        $params = parent::_format_reship_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['items']){
            sort($sdf['items']);
            foreach ((array) $sdf['items'] as $k => $v){
                $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');

                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'      => $product['barcode'],
                    'item_name'      => $v['name'],
                    'item_quantity'  => $v['num'],
                    'item_price'     => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => '',// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => '',
                    'sub_trade_code' => '',
                );
            }
        }
        
        $params['items'] = json_encode($items);
        return $params;
    }
}