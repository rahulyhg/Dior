<?php
/**
* 店铺商品处理工厂类
* 
* chenping<chenping@shopex.cn>
*/
class inventorydepth_service_shop_factory
{
    
    function __construct(&$app)
    {
        $this->app = $app;
    }

    /**
     * 工厂方法
     *
     * @return void
     * @author 
     **/
    public static function createFactory($shop_type,$business_type='zx')
    {
        switch ($shop_type) {
            case 'taobao':
                if ($business_type == 'fx') {
                    return kernel::single('inventorydepth_service_shop_tbfx');
                } else {
                    return kernel::single('inventorydepth_service_shop_taobao');
                }
                
                break;
            case '360buy':
                return kernel::single('inventorydepth_service_shop_360buy');
                break;
            case 'paipai':
                return kernel::single('inventorydepth_service_shop_paipai');
                break;
            case 'yihaodian':
                return kernel::single('inventorydepth_service_shop_yihaodian');
                break;
            default:
                return false;
                break;
        }
    }
}