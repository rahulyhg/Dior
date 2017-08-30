<?php
/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_sf_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        $params = parent::_format_delivery_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['delivery_items']){
            sort($sdf['delivery_items']);
            foreach ((array) $sdf['delivery_items'] as $k=>$v){
                // 获取外部商品sku
                 $items['item'][] = array(
                    'item_code'       => $v['bn'],
                    'item_name'       => $v['product_name'],
                    'item_quantity'   => $v['number'],
                    'item_price'      => $v['price'],
                    'item_line_num'   => ($k + 1),// 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => $sdf['order_bn'],//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'         => $v['bn'],// 外部系统商品sku
                    'is_gift'         => $v['is_gift'] == 'ture' ? '1' : '0',// 是否赠品
                    'item_remark'     => $v['memo'],// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => $v['sale_price'],//成交额
                    'uom'             =>'个',
                );
            }
        }
        $params['items']            = json_encode($items);
        $params['shipping_type']    = $params['logistics_code'];
        $params['out_order_code']   = 'OS'.$sdf['outer_delivery_bn'];
        $params['receiver_zip']     = '232222';
        $params['receiver_country'] = '中国';
        $params['receiver_state']   = $this->_formate_receiver_citye($sdf['consignee']['province']);
        $params['monthly_account']  = app::get('wmsmgr')->getConf('monthaccount_'.$this->__channelObj->wms['channel_id']);

        $order_type_arr = array('normal'=>'发货订单','360buy'=>'京东订单');
        $shop_code = isset($sdf['shop_code']) ? trim($sdf['shop_code']) : '';
        if (empty($shop_code)){
            $order_type = $order_type_arr[$sdf['shop_type']] ? $order_type_arr[$sdf['shop_type']] : $order_type_arr['normal'];
        }else{
            $order_type = $shop_code;
        }
        $params['order_type'] = $order_type;

        return $params;
    }

    protected function _format_delivery_search_params($sdf)
    {
        $params = array(
            'out_order_code'=>$sdf['delivery_bn'],    
        );
        return $params;
    }

    protected function _format_delivery_cancel_params($sdf)
    {
        $params = array(
            'warehouse_code' => $sdf['branch_bn'],
            'out_order_code' => 'OS'.$sdf['outer_delivery_bn'],
            'uniqid'         => self::uniqid(),
        );
        return $params;
    }
}