<?php

class logisticsmanager_waybill_yunda {

    public static $businessType = array(
        'PRO_LOCAL_SERVER' => 1,
    );

    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     */
    public function logistics($logistics_code) {
        $logistics = array(
            'PRO_LOCAL_SERVER' => array('code'=>'PRO_LOCAL_SERVER','name'=>'本地服务'),
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