<?php
/**
 * 入库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_360buy_request_stockin extends erpapi_wms_request_stockin
{
    protected $_stockin_pagination = false;

    /**
     * 不支持入库单取消
     *
     * @return void
     * @author 
     **/
    public function stockin_cancel($sdf){
        return $this->error('接口方法不存在','w402');
    }

    /**
     * 入库单创建(同步)
     *
     * @return void
     * @author 
     **/
    public function stockin_create($sdf){
        $stockin_bn = $sdf['io_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($stockin_bn);
        if ($iscancel) {
            return $this->succ('入库单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'].'入库单添加'.$stockin_bn;

        $params = $this->_format_stockin_create_params($sdf);
        $params['is_finished']      = 'true';
        $params['current_page']     = 1;
        $params['total_page']       = 1;
        $params['item_total_num']   = count($sdf['items']);
        $params['line_total_count'] = count($sdf['items']);

        $result = $this->__caller->call(WMS_INORDER_CREATE, $params, null, $title, 10, $stockin_bn);

        if (!is_array($result['data']) && $result['data']) $result['data'] = @json_decode($result['data'],true); 

        return $result;
    }

    public function _format_stockin_create_params($sdf)
    {
        $wms_order_code = $stockin_bn = $sdf['io_bn'];

        $items = array('item'=>array());
        if ($sdf['items']){
            $oForeign_sku = app::get('console')->model('foreign_sku');
            foreach ((array) $sdf['items'] as $k=>$v){
                // 外部编号
                $item_code = $oForeign_sku->get_product_outer_sku($this->__channelObj->wms['channel_id'], $v['bn']);

                $items['item'][] = array(
                    'item_code'      => $item_code,
                    'item_name'      => $v['name'],
                    'item_quantity'  => $v['num'],
                    'item_price'     => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'  => ($k+1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => $v['bn'],// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => $this->transfer_inventory_type($sdf['type_id']),// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }
       

        $create_time = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);
        $params = array(
            'uniqid'            => self::uniqid(),
            'out_order_code'    => $stockin_bn,
            'order_type'        => $this->transfer_stockin_type($sdf['io_type']),
            'created'           => $create_time,
            'wms_order_code'    => $wms_order_code,
            'logistics_code'    => '',// TODO: 快递公司（如果是汇购传递快递公司，则该项目不能为空，否则可以为空处理）
            'logistics_no'      => '',// TODO: 运输公司运单号
            'remark'            => $sdf['memo'],
            'line_total_count'  => $sdf['line_total_count'],// TODO: 订单行项目数量
            'item_total_num'    => $sdf['item_total_num'],
            'storage_code'      => $sdf['storage_code'],// 库内存放点编号
            'warehouse_code'    => $this->get_warehouse_code($sdf['branch_bn']),
            'expect_start_time' => date('Y-m -d H:i:s'),
            'items'             => json_encode($items),
        );
        
       return $params;
    }    
}