<?php

class ome_sales_data_type{

    public static $__TYPE_LIST = array('goods','gift','pkg','giftpackage');

    public static $__DEFAULT_TYPE = 'goods';

    public function trans($type,$obj){
        $type = strtolower($type);
        if (in_array($type, self::$__TYPE_LIST) || $type = self::$__DEFAULT_TYPE) {
            $objLib = kernel::single(sprintf('ome_sales_data_type_%s',$type));
            if (method_exists($objLib, 'doTrans')) {
                return $objLib->doTrans($obj);
            } else {
                return sprintf("方法doTrans不存在。");
            }
        } else {
            return sprintf("未知类型{$type}。");
        }
    }
}