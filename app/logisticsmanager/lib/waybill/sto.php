<?php

class logisticsmanager_waybill_sto {
    public static $businessType = array(
        '1' => 1,
       
    );
    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     */
    public function logistics($logistics_code) {
        $logistics = array(
            '1' => array('code'=>'1','name'=>'标准快递'),
         
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }

    public static function getBusinessType($type) {
        return self::$businessType[$type];
    }

}