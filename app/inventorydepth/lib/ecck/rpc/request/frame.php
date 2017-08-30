<?php
/**
 * 更新商品上下架，RPC实现类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_ecck_rpc_request_frame extends ome_rpc_request {

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 更新商品上下架
     *
     * @param Array $approve_status 上下架参数
     * @param String $shop_id 店铺ID
     * @return Array
     **/
    public function approve_status_list_update($approve_status,$shop_id)
    {
    }
}
