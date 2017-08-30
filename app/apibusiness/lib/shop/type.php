<?php
class apibusiness_shop_type{

    /**
     * C2C前端店铺列表
     * @return array
     */
    static function shop_list(){
        $shop = array('taobao','paipai','youa','360buy','yihaodian','qq_buy','dangdang');
        
        return $shop;
    }
}