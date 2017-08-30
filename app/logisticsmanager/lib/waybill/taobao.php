<?php

class logisticsmanager_waybill_taobao {
    /**
     * 云栈订单来源列表
     * @var Array $channelsTypeList
     */
    public static $channelsTypeList = array(
        'C' => 'TB',//淘宝
        'B' => 'TM',//天猫
        'OTHER' => 'OTHERS',//其它
    );
    /**
     * 默认订单来源类型
     * @var String 默认来源
     */
    public static $defaultChannelsType = 'OTHER';
    
    public static $businessType = array(
        'EMS' => 1,
        'EYB' => 2,
        'SF' => 3,
        'ZJS' => 4,
        'ZTO' => 5,
        'HTKY' => 6,
        'UC' => 7,
        'YTO' => 8,
        'STO' => 9,
        'TTKDEX' => 10,
        'DBKD'=>11
    );

    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     */
    public function logistics($logistics_code) {
        $logistics = array(
            'EMS' => array('code'=>'EMS','name'=>'普通EMS'),
            'EYB'=>array('code'=>'EYB','name'=>'经济EMS'),
            'SF'=>array('code'=>'SF','name'=>'顺丰'),
            'ZJS' => array('code' => 'ZJS', 'name'=>'宅急送'),
            'ZTO' => array('code' => 'ZTO', 'name' => '中通'),
            'HTKY' => array('code' => 'HTKY', 'name'=>'百事汇通'),
            'UC' => array('code' => 'UC', 'name' => '优速'),
            'YTO' => array('code' => 'YTO', 'name' => '圆通'),
            'STO' => array('code' => 'STO', 'name' => '申通'),
            'TTKDEX' => array('code' => 'TTKDEX', 'name' => '天天'),
            'QFKD' => array('code' => 'QFKD', 'name' => '全峰'),
            'FAST' => array('code' => 'FAST', 'name' => '快捷'),
            'POSTB' => array('code' => 'POSTB', 'name' => '邮政小包'),
            'GTO' => array('code' => 'GTO', 'name' => '国通'),
            'YUNDA'=>array('code' => 'YUNDA', 'name' => '韵达'),
            'DBKD'=>array('code' => 'DBKD', 'name' => '德邦快递'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }

    /**
     * 获取订单来源类型
     * @param String $type 类型
     */
    public static function get_order_channels_type($type) {
        $type = strtoupper($type);
        if (in_array($type, array_keys(self::$channelsTypeList))) {
            $channelsType =  self::$channelsTypeList[$type];
        }
        else {
            $channelsType = self::$channelsTypeList[self::$defaultChannelsType];
        }
        return $channelsType;
    }

    public static function getBusinessType($type) {
        $type = strtoupper($type);
        return self::$businessType[$type];
    }
}