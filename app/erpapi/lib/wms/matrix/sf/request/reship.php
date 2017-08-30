<?php
/**
 * 退货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_sf_request_reship extends erpapi_wms_request_reship
{

    protected function _format_reship_create_params($sdf)
    {
        $params = parent::_format_reship_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k=>$v){
                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'      => $v['bn'],
                    'item_name'      => $v['name'],
                    'item_quantity'  => $v['num'],
                    'item_price'     => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => '',// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'uom'            =>'个',
                );
            }
        }
        $params['items']           = json_encode($items);
        $params['out_order_code']  = 'MS'.$sdf['reship_bn'];
        
        $delivery_extObj           = app::get('console')->model('delivery_extension');
        $detail                    = $delivery_extObj->dump(array('delivery_bn'=>$sdf['original_delivery_bn']),'original_delivery_bn');
        $params['orig_order_code'] = $detail['original_delivery_bn'] ? $detail['original_delivery_bn'] : '';
        $params['monthly_account'] = '7550144315';
        $params['expect_end_time'] = date('Y-m-d H:i:s',strtotime('+1 days'));
        $params['source_id']       = $sdf['supplier_bn'] ? $sdf['supplier_bn']: 'SHP_V1';

   
        return $params;
    }
}