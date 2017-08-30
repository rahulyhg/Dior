<?php
/**
* 更新库存RPC接口实现
*
* @author chenping<chenping@shopex.cn>
* @version 2012-5-30 18:04
*/
class inventorydepth_ecck_rpc_request_stock extends ome_rpc_request
{

    function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * 前端店铺更新库存
     *
     * @param Array $stocks 矩阵更新库存结构
     * @param String $shop_id 店铺ID
     * @param Array $addon 附加参数
     *
     **/
    public function stock_update($stocks,$shop_id,$addon='')
    {

    }
}