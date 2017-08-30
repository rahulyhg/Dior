<?php
/**
 * 出库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_ilc_request_stockout extends erpapi_wms_request_stockout
{
    protected $_stockout_pagination = false;
    
    protected function _format_stockout_create_params($sdf)
    {
        $params = parent::_format_stockout_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k=>$v){
                $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');

                $items['item'][] = array(
                    'item_code'      => $product['barcode'],
                    'item_name'      => $v['name'],
                    'item_quantity'  => $v['num'],
                    'item_price'     => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => $v['bn'],// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }

        $params['items']               = json_encode($items);
        $params['uniqid']              =  substr(self::uniqid(),0,25);
        $params['shipping_type']       = 'EMS';
        $params['remark']              =  $sdf['memo'] ? $sdf['memo'] : '';
        $params['receiver_zip']        =  $sdf['receiver_zip'] ? $sdf['receiver_zip'] : '200000';
        $params['receiver_country']    =  '中国';
        $params['is_cod']              =  'false';
        $params['platform_order_code'] = '';

        if ($sdf['branch_type'] == 'damaged') {
            $params['order_type'] = '残损出库';
        }

        return $params;
    }

    protected function _format_stockout_cancel_params($sdf)
    {
        $params = array(
             'out_order_code' => $sdf['io_bn'],
             'order_type'     => parent::transfer_stockout_type($sdf['io_type']),
             'uniqid'         =>self::uniqid(),
        );
        return $params;
    }

    protected function transfer_stockout_type($io_type)
    {
        $stockout_type = array(
            'PURCHASE_RETURN' => '采购退货',// 采购退货
            'ALLCOATE'        => '调拨出库',// 调拨出库
            'DEFECTIVE'       => '残损出库',// 残损出库
        );

        return isset($stockout_type[$io_type]) ? $stockout_type[$io_type] : '一般出库';
    }
}