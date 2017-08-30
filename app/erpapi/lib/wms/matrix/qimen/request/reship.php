<?php
/**
 * 退货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_qimen_request_reship extends erpapi_wms_request_reship
{
    public function reship_cancel($sdf){
        $title = $this->__channelObj->wms['channel_name'].'退货单取消';

        $order_type = 'IN_OTHER';

        if ($sdf['return_type'] == 'return') {
            $order_type = 'IN_SALE_RETURN';
        } elseif ($sdf['return_type'] == 'change') {
            $order_type = 'IN_EXCHANGE';
        }

        $params = array(
            'order_type'     => $order_type,
            'out_order_code' => $sdf['reship_bn'],
            'warehouse_code' => $this->get_warehouse_code($sdf['branch_bn']),
        );

        return $this->__caller->call(WMS_ORDER_CANCEL, $params, null, $title, 10, $sdf['reship_bn']);
    }

    protected function _format_reship_create_params($sdf)
    {
        $params = parent::_format_reship_create_params($sdf);
        $params['warehouse_code'] = $this->get_warehouse_code($sdf['branch_bn']); 

        switch ($sdf['return_type']) {
            case 'return':
                $params['order_type'] = 'THRK'; // 退货入库
                break;
            case 'change':
                $params['order_type'] = 'HHRK'; // 换货入库 
                break;
            default:
                $params['order_type'] = '';
                break;
        }
        
        $items = array('item'=>array());
        if ($sdf['items']){
            sort($sdf['items']);
            foreach ((array) $sdf['items'] as $k => $v){
                $foreignsku = app::get('console')->model('foreign_sku')->dump(array('wms_id'=>$this->__channelObj->wms['channel_id'],'inner_sku'=>$v['bn']));
                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'      => $v['bn'],
                    'item_name'      => $v['name'],
                    'item_quantity'  => $v['num'],
                    'item_price'     => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => $foreignsku['outer_sku'] ? $foreignsku['outer_sku'] : $v['bn'],// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }

        $params['items'] = json_encode($items);

        $delivery_extend = app::get('console')->model('delivery_extension')->dump(array('delivery_bn'=>$sdf['original_delivery_bn']));
        $params['wms_order_code'] = $delivery_extend['original_delivery_bn'];
        return $params;
    }
}