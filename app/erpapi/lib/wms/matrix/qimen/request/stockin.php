<?php
/**
 * 入库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_qimen_request_stockin extends erpapi_wms_request_stockin
{
    public function stockin_cancel($sdf){
        $stockin_bn = $sdf['io_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '入库单取消';

        $params = array(
            'order_type'     => $this->transfer_stockin_type($sdf['io_type']),
            'out_order_code' => $stockin_bn,
            'warehouse_code' => $this->get_warehouse_code($sdf['branch_bn']),
            'order_id'      => $sdf['out_iso_bn'],
        );

        return $this->__caller->call(WMS_ORDER_CANCEL, $params, null, $title, 10, $stockin_bn); 
    } 

    protected function _format_stockin_create_params($sdf)
    {
        $params = parent::_format_stockin_create_params($sdf);
        $params['warehouse_code'] = $this->get_warehouse_code($sdf['branch_bn']);

        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'price');
                $foreignsku = app::get('console')->model('foreign_sku')->dump(array('wms_id'=>$this->__channelObj->wms['channel_id'],'inner_sku'=>$v['bn']));

                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'       => $v['bn'],
                    'item_name'       => $v['name'],
                    'item_quantity'   => $v['num'],
                    'item_price'      => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'   => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'         => $foreignsku['outer_sku'] ? $foreignsku['outer_sku'] : $v['bn'],// 商品ID
                    'is_gift'         => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'     => '',// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => $product['price']['price']['price'] ? $product['price']['price']['price'] : 0,
                );
            }
        }

        $params['items'] = json_encode($items);


        // 调拨出库收货人：仓库
        if ($sdf['io_type'] == 'ALLCOATE') {
            if ($sdf['branch_id']) {
                $branch = app::get('ome')->model('branch')->dump($sdf['branch_id']);
                $area = $branch['area'];

                if ($area) {
                    kernel::single('eccommon_regions')->split_area($area);
                    $params['receiver_state']    = $area[0];
                    $params['receiver_city']     = $area[1];
                    $params['receiver_district'] = $area[2];
                }

                $params['receiver_zip']     = $branch['zip'];
                $params['receiver_name']    = $branch['uname'];
                $params['receiver_address'] = $branch['address'];
                $params['receiver_phone']   = $branch['phone'];
                $params['receiver_mobile']  = $branch['mobile'];
            }

            if ($sdf['extrabranch_id']) {
                $branch = app::get('ome')->model('branch')->dump($sdf['extrabranch_id']);
                $area = $branch['area'];

                if ($area) {
                    kernel::single('eccommon_regions')->split_area($area);
                    $params['shipper_state']    = $area[0];
                    $params['shipper_city']     = $area[1];
                    $params['shipper_district'] = $area[2];
                }

                $params['shipper_zip']     = $branch['zip'];
                $params['shipper_name']    = $branch['uname'];
                $params['shipper_address'] = $branch['address'];
                $params['shipper_phone']   = $branch['phone'];
                $params['shipper_mobile']  = $branch['mobile'];
            }

        }

        return $params;
    }
}