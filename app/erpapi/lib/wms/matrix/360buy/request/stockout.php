<?php
/**
 * 出库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_360buy_request_stockout extends erpapi_wms_request_stockout
{   
    /**
     * 不支持出库单取消
     *
     * @return void
     * @author 
     **/
    public function stockout_cancel($sdf){
        return $this->error('接口方法不存在','w402');
    }

   /**
     * 出库单创建(同步)
     *
     * @return void
     * @author 
     **/
    public function stockout_create($sdf){
        $stockout_bn = $sdf['io_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($stockout_bn);
        if ($iscancel) {
            return $this->succ('出库单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '出库单添加';

        // 分页请求
        $params = $this->_format_stockout_create_params($sdf);

        $params['is_finished']      = 'true';
        $params['current_page']     = '1';
        $params['total_page']       = '1';
        $params['item_total_num']   = count($sdf['items']);
        $params['line_total_count'] = count($sdf['items']);

        $result = $this->__caller->call(WMS_OUTORDER_CREATE, $params, null, $title, 10, $stockout_bn);

        if (!is_array($result['data']) && $result['data']) $result['data'] = @json_decode($result['data'],true);

        return $result;
    } 

    /**
     * 不支持分页
     *
     * @return void
     * @author 
     **/
    protected function _format_stockout_create_params($sdf)
    {
        $params = parent::_format_stockout_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['items']){
            $oForeign_sku = app::get('console')->model('foreign_sku');

            sort($sdf['items']);
            foreach ((array) $sdf['items'] as $k => $v){
                $item_code = $oForeign_sku->get_product_outer_sku($this->__channelObj->wms['channel_id'], $v['bn']);
                $items['item'][] = array(
                    'item_code'      => $item_code,
                    'item_name'      => $v['name'],
                    'item_quantity'  => $v['num'],
                    'item_price'     => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => $v['bn'],// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => $this->transfer_inventory_type($sdf['type_id']),// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }

        $params['items']             = json_encode($items);
        $params['logistics_code']    = $this->get_wmslogi_code($this->__channelObj->wms['channel_id'],$sdf['logi_code']);
        $params['remark']            = $sdf['memo'] ? $sdf['memo'] : '没有也得传啊';
        $params['memo']              = $sdf['memo'] ? $sdf['memo'] : '没有也得传啊';
        $params['receiver_zip']      = $sdf['receiver_zip'] ? $sdf['receiver_zip'] : '232122';
        $params['warehouse_code']    = $this->get_warehouse_code($sdf['branch_bn']);
        $params['expect_start_time'] = date('Y-m-d H:i:s');
        $params['is_cod']            = $sdf['is_cod'] ? $sdf['is_cod'] : 'false';
        $params['total_trade_fee']   = $sdf['total_goods_fee'];

        return $params;
    }

    protected function _format_stockout_cancel_params($sdf)
    {
        $params = array(
            'out_order_code' => $sdf['out_iso_bn'],
            'order_type'     => $this->transfer_stockout_type($sdf['io_type']),
        );

        return $params;
    }

    protected function _format_stockout_search_params($sdf)
    {
        $params = array(
            'out_order_code' =>$sdf['out_order_code'],
        );
        return $params;
    }
}