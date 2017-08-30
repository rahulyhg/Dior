<?php
/**
 * 店铺SKU,RPC调用类
 * 
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_service_shop_skus {

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 下载货品
     *
     * @param Array $sku
     * $sku = array(
     *  'sku_id' => {SKU的ID}
     *  'iid'    => {商品ID}
     *  'seller_uname' => {卖家帐号}
     * );
     * @param String $shop_id 店铺ID
     * @param String $errormsg 错误信息
     * @return void
     * @author 
     **/
    public function item_sku_get($sku,$shop_id,&$errormsg)
    {
        $result = kernel::single('inventorydepth_rpc_request_shop_skus')->item_sku_get($sku,$shop_id);

        if ($result === false) {
            $errormsg = $this->app->_('请求失败：数据错误或请求超时');
            return false;
        } elseif ($result->rsp !== 'succ') {
            $errormsg = $this->app->_('请求失败：'.$result->err_msg);
            return false;
        }

        return json_decode($result->data,true);
    }
    
}
