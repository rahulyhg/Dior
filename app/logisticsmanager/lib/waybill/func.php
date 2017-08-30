<?php
class logisticsmanager_waybill_func {
    //获取面单来源渠道
    public function channels($channel_type) {
        $channels = array(
            //'wlb'=>array('code'=>'wlb','name'=>'物流宝'),
            'ems'=>array('code'=>'ems','name'=>'EMS官方'),
            '360buy' =>array('code'=>'360buy','name'=>'京东'),
            'taobao'=>array('code'=>'taobao','name'=>'淘宝'),
            'sf'=>array('code'=>'sf','name'=>'顺丰'),
            'yunda'=> array('code' => 'yunda', 'name' => '韵达'),
            'sto'=> array('code' => 'sto', 'name' => '申通'),
              
        );

        if(!empty($channel_type)) {
            return $channels[$channel_type];
        }

        return $channels;
    }
}