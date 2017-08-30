<?php
/**
* 接口所支持的店铺
*/
class inventorydepth_shop_api_support
{
    
    function __construct($app)
    {
        $this->app = $app;
    }

    public static $item_sku_get_shops = array('taobao','paipai','360buy');
    /**
     * 接口store.item.sku.get
     *
     * @param String $shop_type 店铺类型
     * @return void
     * @author 
     **/
    public static function item_sku_get_support($shop_type)
    {
        return in_array($shop_type,self::$item_sku_get_shops);
    }

    public static $items_all_get_shops = array('taobao','paipai','360buy');
    /**
     * 接口store.item.all.get
     *
     * @return void
     * @author 
     **/
    public static function items_all_get_support($shop_type,$business_type='zx')
    {
        return in_array($shop_type,self::$items_all_get_shops);
    }


    public static $items_get_shops = array('taobao','paipai','360buy');
    /**
     * 接口store.item.get 
     *
     * @return void
     * @author 
     **/
    public static function items_get_support($shop_type)
    {
        return in_array($shop_type,self::$items_get_shops);
    }


}