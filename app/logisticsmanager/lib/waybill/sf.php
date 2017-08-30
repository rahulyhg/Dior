<?php

class logisticsmanager_waybill_sf {

    public static $businessType = array(
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '7' => 7,
        '28' => 28,
    );

    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     */
    public function logistics($logistics_code) {
        $logistics = array(
            '1' => array('code'=>'1','name'=>'标准快递'),
            '2'=>array('code'=>'2','name'=>'顺丰特惠'),
            '3'=>array('code'=>'3','name'=>'电商特惠'),
            '7'=>array('code'=>'7','name'=>'电商速配'),
            '28'=>array('code'=>'28','name'=>'电商专配'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }

    public function pay_method($method = '') {
        $payMethod = array(
            '1' => array('code' => '1', 'name' => '寄方式'),
            '2' => array('code' => '2', 'name' => '收方式'),
            '3' => array('code' => '3', 'name' => '第三方付'),
         );

        if (!empty($method)) {
            return $payMethod[$method];
        }
        return $payMethod;
    }

    public static function getBusinessType($type) {
        return self::$businessType[$type];
    }
}