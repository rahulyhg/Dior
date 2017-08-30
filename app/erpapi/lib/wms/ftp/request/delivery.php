<?php
/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_ftp_request_delivery extends erpapi_wms_request_delivery
{
    /**
     * 发货单创建
     *
     * @return 
     * @author 
     **/
    public function delivery_create($sdf){
        // 发货作业单
        $delivery_bn = $sdf['outer_delivery_bn'];

        $iscancel = kernel::single('ome_interface_delivery')->iscancel($delivery_bn);
        if ($iscancel) {
            return $this->succ('发货单已取消,终止同步');
        }

        // 发货明细
        $items = array();
        foreach ((array) $sdf['delivery_items'] as $v){
            $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');  // TODO:伊腾忠用条形码作唯一标识
            $items[] = array(
                'item_bn'         => $product['barcode'],
                'price'           => $v['price'],
                'item_sale_price' => $v['sale_price'],
                'num'             => $v['number'],
            );
        }

        $title = $this->__channelObj->wms['channel_name'] . '发货单添加';

        $logistics_code = kernel::single('wmsmgr_func')->getWmslogiCode($this->__channelObj->wms['channel_id'],$sdf['logi_code']);
        $params = array(
            'order_type'        => $sdf['shop_type'],
            'order_bn'          => $delivery_bn,
            'outer_order_bn'    => $sdf['outer_order_bn'],
            'original_order_bn' => $sdf['order_bn'],
            'warehouse'         => $sdf['branch_bn'],
            'logistics'         => $logistics_code ? $logistics_code : $sdf['logi_code'],
            'member_name'       => $sdf['member_name'],
            'ship_name'         => $sdf['consignee']['name'],
            'zip'               => $sdf['consignee']['zip'],
            'phone'             => $sdf['consignee']['telephone'],
            'mobile'            => $sdf['consignee']['mobile'],
            'province'          => $sdf['consignee']['province'],
            'city'              => $sdf['consignee']['city'],
            'district'          => $sdf['consignee']['district'] ? $sdf['consignee']['district'] : '其他',
            'addr'              => $sdf['consignee']['addr'],
            'cost_item'         => $sdf['total_amount'],
            'cost_freight'      => $sdf['logistics_costs'],
            'is_cod'            => $sdf['is_cod'],
            'cod_fee'           => $sdf['cod_fee'],
            'shop_code'         => $sdf['shop_code'],#售达方编号
            'delivery_center'   => $sdf['print_remark']['pszx_name'],#配送中心名称
            'memo'              => $sdf['memo'],
            'items'             => json_encode($items),
        );

        return $this->__caller->call(WMS_SALEORDER_CREATE, $params, null, $title,10,$delivery_bn);
    }

    /**
     * 发货单暂停
     *
     * @return void
     * @author 
     **/
    public function delivery_pause($sdf){
        return $this->error('接口方法不存在','w402');
    }

    /**
     * 发货单暂停恢复
     *
     * @return void
     * @author 
     **/
    public function delivery_renew($sdf){
        return $this->error('接口方法不存在','w402');
    }

    /**
     * 发货单取消
     *
     * @return void
     * @author 
     **/
    public function delivery_cancel($sdf){
        return $this->error('接口方法不存在','w402');
    }

    public function delivery_search($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }
}