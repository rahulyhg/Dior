<?php
/**
 * 入库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_sf_request_stockin extends erpapi_wms_request_stockin
{

    public function _format_stockin_create_params($sdf)
    {
        $params = parent::_format_stockin_create_params($sdf);
 
        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ($sdf['items'] as $k => $v){
               
                $items['item'][] = array(
                    'item_code'      => $v['bn'],
                    
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
        $params['uniqid']              = substr(self::uniqid(),0,25);
        $params['shipper_name']        = $sdf['shipper_name'] ? $sdf['shipper_name'] : '';
        $params['shipper_zip']         = $sdf['shipper_zip'] ? $sdf['shipper_zip'] : '200000';
        $params['shipper_state']       = $sdf['shipper_state'] ? $sdf['shipper_state'] : '';
        $params['shipper_city']        = $sdf['shipper_city'] ? $sdf['shipper_city'] : '';
        $params['shipper_district']    = $sdf['shipper_district'] ? $sdf['shipper_district'] : '';
        $params['shipper_address']     = $sdf['shipper_address'] ? $sdf['shipper_address'] : '';
        $params['shipper_phone']       = $sdf['shipper_phone'] ? $sdf['shipper_phone'] : '';
        $params['shipper_mobile']      = $sdf['shipper_mobile'] ? $sdf['shipper_mobile'] : '';
        $params['shipper_email']       = $sdf['shipper_email'] ? $sdf['shipper_email'] : '';
        $params['expect_end_time']     = date('Y-m-d H:i:s');
        $params['platform_order_code'] = '';
        $params['source_id']           = $sdf['supplier_bn'] ? $sdf['supplier_bn'] : 'SHP_V1';

       return $params;
    }

    protected function _format_stockin_cancel_params($sdf)
    {
        $params = array(
            'out_order_code'=>$sdf['io_bn'],
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