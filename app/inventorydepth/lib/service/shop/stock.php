<?php
/**
 * 店铺库存回写,RPC调用类
 * 
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_service_shop_stock {

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 库存回写 异步
     *
     * @return void
     * @author 
     **/
    public function items_quantity_list_update($stocks,$shop_id,$dorelease = false)
    {
        # 如果关闭，则不向前端店铺请求
        if ($dorelease === false ) {
            $request = kernel::single('inventorydepth_shop')->getStockConf($shop_id);
            if($request !== 'true') return false;
        }
        
        kernel::single('inventorydepth_rpc_request_shop_stock')->items_quantity_list_update($stocks,$shop_id,$dorelease);
    }

    
}
