<?php
/**
 * 入库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_ilc_request_stockin extends erpapi_wms_request_stockin
{
    protected $_stockin_pagination = false;
    
    protected function transfer_stockin_type($io_type)
    {
        $stockin_type = array(
            'PURCHASE'  => '采购入库',// 采购入库
            'ALLCOATE'  => '调拨入库',// 调拨入库
            'DEFECTIVE' => '残损入库',// 残损入库
        );

        return isset($stockin_type[$io_type]) ? $stockin_type[$io_type] : '一般入库';
    }

    public function _format_stockin_create_params($sdf)
    {
        $params = parent::_format_stockin_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k=>$v){
                // 获取外部商品sku
                $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');#TODO:伊腾忠用条形码作唯一标识
                $items['item'][] = array(
                    'item_code'      => $v['bn'],
                    'item_bn'        => $product['barcode'],
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

        $params['items'] = json_encode($items);
        $params['uniqid'] = substr(self::uniqid(),0,25);
        $params['shipper_zip'] = $sdf['shipper_zip'] ? $sdf['shipper_zip'] : '200000';
        $params['platform_order_code'] = '';

        if ($sdf['branch_type'] == 'damaged') {
            $params['order_type'] = '残损入库';
        }

        return $params;   
    }

    protected function _format_stockin_cancel_params($sdf)
    {
        $params = array(
            'out_order_code' => $sdf['io_bn'],
            'uniqid' => self::uniqid(),
        );
        return $params;
    }

    protected function _format_stockin_search_params($sdf)
    {
        $params = array(
            'out_order_code'=>$sdf['stockin_bn'], 
        );

        return $params;
    }
}