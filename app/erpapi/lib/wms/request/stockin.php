<?php
/**
 * 入库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_stockin extends erpapi_wms_request_abstract
{
    protected $_stockin_pagination = true;
    protected $_stockin_pagesize   = 150;

    protected function transfer_stockin_type($io_type)
    {
        $stockin_type = array(
            'PURCHASE'   => 'IN_PURCHASE',  // 采购入库
            'ALLCOATE'   => 'IN_ALLCOATE',  // 调拨入库
            'DEFECTIVE'  => 'IN_DEFECTIVE', // 残损入库
            'ADJUSTMENT' => 'IN_ADJUSTMENT',// 调帐入库
            'EXCHANGE'   => 'IN_EXCHANGE',  // 换货入库
            'OTHER'      => 'IN_OTHER',     // 其他入库
        );

        return isset($stockin_type[$io_type]) ? $stockin_type[$io_type] : 'IN_OTHER';
    }

    /**
     * 入库单创建
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

        $title = $this->__channelObj->wms['channel_name'].'入库单添加';

        // 分页请求
        $items = $sdf['items']; sort($items);

        $total = count($items); $page_no = 1; $page_size =  $this->_stockin_pagesize; 
        $total_page = $this->_stockin_pagination ? ceil($total/$page_size) : 1;
        do {

            $offset = ($page_no - 1) * $page_size;

            $sdf['items'] = $total_page > 1 ?  array_slice($items, $offset, $page_size, true) : $items;

            $params = $this->_format_stockin_create_params($sdf);

            $params['is_finished']      = ($page_no >= $total_page) ? 'true' : 'false';
            $params['current_page']     = $page_no;
            $params['total_page']       = $total_page;
            $params['item_total_num']   = $total;
            $params['line_total_count'] = $total;


            $callback = array(
                'class'  => get_class($this),
                'method' => 'stockin_create_callback',
                'params' => array('stockin_bn'=>$stockin_bn,'io_type'=>$sdf['io_type'],'obj_bn'=>$stockin_bn,'obj_type'=>strtolower($sdf['io_type'])),
            );

            $this->__caller->call(WMS_INORDER_CREATE, $params, $callback, $title, 10, $stockin_bn);

            if ($params['is_finished'] == 'true') break;

            $page_no++;
        } while (true);
    } 

    public function stockin_create_callback($response, $callback_params)
    {
        // 更新外部编码
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = @json_decode($response['data'],true);
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $stockin_bn = $callback_params['stockin_bn'];
        $io_type    = $callback_params['io_type'];

        if ($data['wms_order_code'] && $stockin_bn) {
            $db = kernel::database();
            if ($io_type == 'PURCHASE') {
                $db->exec("UPDATE sdb_purchase_po SET out_iso_bn='".$data['wms_order_code']."' WHERE po_bn='".$stockin_bn."'");
            } else {
                $db->exec("UPDATE sdb_taoguaniostockorder_iso SET out_iso_bn='".$data['wms_order_code']."' WHERE iso_bn='".$stockin_bn."'");
            }
        }

        $callback_params['obj_bn']   = $stockin_bn;
        $callback_params['obj_type'] = strtolower($io_type);
        return $this->callback($response, $callback_params);
    }

    protected function _format_stockin_create_params($sdf)
    {
        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'price');

                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'       => $v['bn'],
                    'item_name'       => $v['name'],
                    'item_quantity'   => $v['num'],
                    'item_price'      => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num'   => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'         => $v['bn'],// 商品ID
                    'is_gift'         => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'     => '',// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => $product['price']['price']['price'] ? $product['price']['price']['price'] : 0,
                );
            }
        }

        $create_time = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);
        $params = array(
            'uniqid'           => self::uniqid(),
            'out_order_code'   => $sdf['io_bn'],
            'order_type'       => $this->transfer_stockin_type($sdf['io_type']),
            'created'          => $create_time,
            'wms_order_code'   => $sdf['io_bn'],
            // 'is_finished'      => $cur_page >= $total_page ? 'true' : 'false',
            // 'current_page'     => $cur_page,// 当前批次,用于分批同步
            // 'total_page'       => $total_page,// 总批次,用于分批同步
            'logistics_code'   => '',// TODO: 快递公司（如果是汇购传递快递公司，则该项目不能为空，否则可以为空处理）
            'logistics_no'     => '',// TODO: 运输公司运单号
            'remark'           => $sdf['memo'],
            'shipper_name'     => $sdf['shipper_name'] ? $sdf['shipper_name'] : '未知',
            'shipper_zip'      => $sdf['shipper_zip'] ? $sdf['shipper_zip'] : '未知',// TODO: 收货人邮政编码
            'shipper_state'    => $sdf['shipper_state'] ? $sdf['shipper_state'] : '未知',// TODO: 退货人所在省
            'shipper_city'     => $sdf['shipper_city'] ? $sdf['shipper_city'] : '未知',// TODO: 退货人所在市
            'shipper_district' => $sdf['shipper_district'] ? $sdf['shipper_district'] : '未知',// TODO: 退货人所在县（区），注意有些市下面是没有区的
            
            'shipper_address'  => $sdf['shipper_address']  ? $sdf['shipper_address'] : '未知',// TODO: 收货地址（出库时非空）
            'shipper_phone'    => $sdf['shipper_phone']  ? $sdf['shipper_phone'] : '未知',// TODO: 收货人电话号码（如有分机号用“-”分隔）(电话和手机必选一项) 
            'shipper_mobile'   => $sdf['shipper_mobile']  ? $sdf['shipper_mobile'] : '未知',// TODO: 收货人手机号码(电话和手机必选一项) 
            'shipper_email'   => $sdf['shipper_email']  ? $sdf['shipper_email'] : '未知',// TODO: 收货人手机号码(电话和手机必选一项) 
            'total_goods_fee'  => $sdf['total_goods_fee'],// 订单商品总价（精确到小数点后2位）
            
            // 'line_total_count' => $sdf['line_total_count'],// TODO: 订单行项目数量
            // 'item_total_num'   => $sdf['item_total_num'],
            'storage_code'     => $sdf['storage_code'],// 库内存放点编号
            'items'            => json_encode($items),
        );

       return $params;
    }

    /**
     * 入库单取消
     *
     * @return void
     * @author 
     **/
    public function stockin_cancel($sdf){
        $stockin_bn = $sdf['io_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '入库单取消';

        $params = $this->_format_stockin_cancel_params($sdf);

        return $this->__caller->call(WMS_INORDER_CANCEL, $params, null, $title, 10, $stockin_bn); 
    } 

    protected function _format_stockin_cancel_params($sdf)
    {
        $params = array(
            'out_order_code' => $sdf['io_bn'],
            'order_type' => $this->transfer_stockin_type($sdf['io_type']),
        );
        return $params;
    }

    /**
     * 入库单查询
     *
     * @return void
     * @author 
     **/
    public function stockin_search($sdf)
    {
        $stockin_bn = $sdf['stockin_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '入库单查询';

        $params = $this->_format_stockin_search_params($sdf);

        return $this->__caller->call(WMS_INORDER_GET, $params, null, $title, 10, $stockin_bn); 
    }

    protected function _format_stockin_search_params($sdf)
    {
        $params = array(
            'out_order_code'=>$sdf['out_order_code'], 
        );

        return $params;
    }
}