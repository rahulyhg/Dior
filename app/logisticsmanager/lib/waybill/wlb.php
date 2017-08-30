<?php
class logisticsmanager_waybill_wlb extends logisticsmanager_waybill_abstract implements logisticsmanager_waybill_interface{
    //获取物流公司
    public function logistics($logistics_code) {
        $logistics = array(
            'EMS'=>array('code'=>'EMS','name'=>'普通EMS'),
            'EYB'=>array('code'=>'EYB','name'=>'经济EMS'),
            'SF'=>array('code'=>'SF','name'=>'顺丰'),
            'ZJS'=>array('code'=>'ZJS','name'=>'宅急送'),
        );

        if(!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }

        return $logistics;
    }

    //获取服务编码
    public function service_code($logistics_code) {
        $service_code = array(
            'EMS' => 'EMS',
            'EYB' => 'EMS',
            'SF' => 'SF',
            'ZJS' => 'ZJS',
        );

        if(!empty($logistics_code)) {
            return $service_code[$logistics_code];
        }

        return $service_code;
    }

    //获取面单类型
    public function pool_type($logistics_code) {
        $pool_type = array(
            'EMS' => 'T01',
            'EYB' => 'T02',
            'SF' => 'SF',
            'ZJS' => 'ZJS',
        );

        if(!empty($logistics_code)) {
            return $pool_type[$logistics_code];
        }

        return $pool_type;
    }

    //获取物流公司编码
    public function logistics_code($service_code, $pool_type) {
        $key = $service_code.$pool_type;
        $logistics_code = array(
            'EMST01' => 'EMS',
            'EMST02' => 'EYB',
            'SFSF' => 'SF',
            'ZJSZJS' => 'ZJS',
        );

        if(!empty($key)) {
            return $logistics_code[$key];
        }

        return $logistics_code;
    }

    /**
    * 打接口获取物流宝电子面单
    *
    * @access public
    * @param array $params 
    * @return void
    */
    public function request_waybill() {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');

        // $wlbObj = kernel::single('logisticsmanager_waybill_wlb');
        $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
        $log_id = $waybillLogObj->gen_id();

        //请求接口参数
        $rpcData = array(
            'num'          => 500,
            'service_code' => $this->service_code($this->_channel['logistics_code']),
            'out_biz_code' => $log_id,
            'pool_type'    => $this->pool_type($this->_channel['logistics_code']),
        );

        //重试日志信息
        $logSdf = array(
            'log_id'      => $log_id,
            'channel_id'  => $this->_channel['channel_id'],
            'status'      => 'running',
            'create_time' => time(),
            'params'      => $rpcData,
        );

        if($waybillLogObj->insert($logSdf)) {
            $router = kernel::single('apibusiness_router_request');
            $router->setShopId($this->_channel['shop_id'])->get_waybill_number($rpcData);
        }

        $rs['rsp'] = 'succ';

        return $rs;
    }

    /**
     * 获取缓存中的运单号前动作
     *
     * @return void
     * @author 
     **/
    public function pre_get_waybill()
    {
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');

        $rs['rsp'] = $this->_channel['shop_id'] == $this->_shop['shop_id'] ? 'succ' : 'fail';

        return $rs;
    }
}