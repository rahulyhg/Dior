<?php
/**
 * 店铺商品上下架,RPC调用类
 * 
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_service_shop_frame {

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 店铺商品上下架 异步
     *
     * @return void
     * @author 
     **/
    public function approve_status_list_update($approve_status,$shop_id,$check_status=true)
    {
        # 如果关闭，则不向前端店铺请求
        $request = kernel::single('inventorydepth_shop')->getFrameConf($shop_id);

        if($check_status == true && $request !== 'true'){ 
            $msg = $this->app->_('店铺上下架功能未开启');
            return false;
        }

        kernel::single('inventorydepth_rpc_request_shop_frame')->approve_status_list_update($approve_status,$shop_id);
    }

    /**
     * 单个商品上下架
     *
     * @return void
     * @author 
     **/
    public function approve_status_update($approve,$shop_id,&$msg)
    {

        # 如果关闭，则不向前端店铺请求
        /*
        $request = kernel::single('inventorydepth_shop')->getFrameConf($shop_id);

        if($request !== 'true'){ 
            $msg = $this->app->_('店铺上下架功能未开启');
            return false;
        }*/
        
        $result = kernel::single('inventorydepth_rpc_request_shop_frame')->approve_status_update($approve,$shop_id);   

        if ($result === false) {
            $msg = $this->app->_('请求超时!');
            return false;
        }

        $approve_status = ($approve['approve_status'] == 'onsale') ? '上架' : '下架';

        if ($result->rsp == 'succ') {
            $msg = $approve_status.'成功!';
            return true;
        }else{
            $msg = $approve_status.'失败!';
            return false;
        }
    }

    
}
