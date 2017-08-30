<?php
/**
 * 入库单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_ftp_request_stockin extends erpapi_wms_request_stockin
{ 

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    protected function transfer_stockin_type($io_type)
    {
        $stockin_type = array(
            'PURCHASE'  => 'I',// 采购入库
            'ALLCOATE'  => 'T',// 调拨入库
            'DEFECTIVE' => 'D',// 残损入库
        );

        return $stockin_type[$io_type];
    }

    /**
     * 入库单创建
     *
     * @return void
     * @author 
     **/
    public function stockin_create($sdf){
        // 入库单号
        $io_bn = $sdf['io_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($io_bn);
        if ($iscancel) {
            return $this->succ('入库单已取消,终止同步');
        }

        $items = array();
        foreach ((array) $sdf['items'] as $v){
            $product = app::get('ome')->model('products')->dump(array('bn'=>$v['bn']),'barcode');  // TODO:伊腾忠用条形码作唯一标识
            $items[] = array(
                'item_bn' => $product['barcode'],
                'price'   => $v['price'],
                'num'     => $v['num'],
            );
        }


        $title = $this->__channelObj->wms['channel_name'] . '入库单添加';
        $params = array(
            'warehouse' => $sdf['branch_bn'],
            'order_bn'  => $sdf['io_bn'],
            'type'      => $this->transfer_stockin_type($sdf['io_type']),
            'items'     => json_encode($items),
        );

        return $this->__caller->call(WMS_INORDER_CREATE, $params, null, $title,10,$io_bn);
    } 

    /**
     * 入库单取消
     *
     * @return void
     * @author 
     **/
    public function stockin_cancel($sdf){
        return $this->error('接口方法不存在','w402');
    } 

    public function stockin_search($sdf)
    {
        return $this->error('接口方法不存在','w402');
    }
}