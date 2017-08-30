<?php
/**
 * 退货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_ftp_request_reship extends erpapi_wms_request_reship
{

    /**
     * 退货单创建
     *
     * @return void
     * @author 
     **/
    public function reship_create($sdf){
        $reship_bn = $sdf['reship_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($reship_bn);

        if ($iscancel) {
            return $this->succ('退货单已取消,终止同步');
        }

        $items = array();
        foreach ((array) $sdf['items'] as $v){
            $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');  // TODO:伊腾忠用条形码作唯一标识
            $items[] = array(
                'item_bn' => $product['barcode'],
                'num'     => $v['num'],
                'price'   => $v['price'],
            );
        }

        $title = $this->__channelObj->wms['channel_name'] . '退货单添加';
        $params = array(
            'order_bn'             => $sdf['reship_bn'],
            'warehouse'            => $sdf['branch_bn'],
            'member_name'          => $sdf['member']['name'],
            'logistics'            => $sdf['logi_name'],
            'logi_no'              => $sdf['logi_no'],
            'logi_money'           => $sdf['money'],
            'memo'                 => $sdf['memo'],
            'original_delivery_bn' => $sdf['original_delivery_bn'],// 原始发货单号
            'reshipper_name'       => $sdf['receiver_name'],// 退货人姓名
            'reshipper_zip'        => $sdf['receiver_zip'],// 退货人邮编
            'reshipper_telephone'  => $sdf['receiver_phone'],// 退货人固定电话
            'reshipper_mobile'     => $sdf['receiver_mobile'],// 退货人手机
            'reshipper_email'      => $sdf['receiver_email'],// 退货人邮箱
            'reshipper_province'   => $sdf['receiver_state'],// 退货人所在省(字符串，如上海市
            'reshipper_city'       => $sdf['receiver_city'],// 退货人所在市
            'reshipper_district'   => $sdf['receiver_district'] ? $sdf['receiver_district'] : '其它',// 退货人所在县区
            'reshipper_addr'       => $sdf['receiver_address'],// 退货人详细地址     
            'items'                => json_encode($items),
        );

        return $this->__caller->call(WMS_RETURNORDER_CREATE, $params, null, $title,10,$reship_bn);

    } 

    /**
     * 退货单创建取消
     *
     * @return void
     * @author 
     **/
    public function reship_cancel($sdf){
        return $this->error('接口方法不存在','w402');
    } 

    public function reship_search($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }
}