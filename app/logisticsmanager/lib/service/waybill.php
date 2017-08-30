<?php
class logisticsmanager_service_waybill {

    const _APP_NAME = 'logisticsmanager';

    /**
    * 从缓存池获取单个电子面单
    *
    * @access public
    * @param array $params 
    * @return void
    */
    public function get_waybill($params) {

        $wbFilter = array(
            'channel_id'=>$params['channel_id'],
            'status'=>0,
        );

        //返回缓存池中的单个面单
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $waybill = $waybillObj->dump($wbFilter,'id,waybill_number','ORDER BY waybill_number,create_time ASC');

        if($waybill['waybill_number']) {
            $waybillObj->update(array('status'=>1),array('id'=>$waybill['id']));
            $affect_row = $waybillObj->db->affect_row();

            // 如果影响的条数大于0，返回面单号
            if ($affect_row > 0) {
                return $waybill['waybill_number'];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
    * 回收电子面单
    *
    * @access public
    * @param array $params 
    * @return void
    */
    public function recycle_waybill($waybill_number,$channel_id,$delivery_id) {

        if($waybill_number) {
            $waybillObj = app::get('logisticsmanager')->model('waybill');
            $stoObj = kernel::single('logisticsmanager_service_sto' );
            $channel_type = $this->recycle_channel($channel_id);
            $recyle_flag = false;
            if ($channel_type && in_array($channel_type,array('sto'))){
                $recyle_flag = true;//重新恢复成可使用状态
            }

            //运单号暂时都不回收（作废）
            if ($recyle_flag) {
                $waybillObj->update(array('status'=>0,'create_time'=>time()),array('waybill_number'=>$waybill_number));
               //判断是否sto如果sto需发送取消接口
                if ($channel_type && in_array($channel_type,array('sto'))) {
                    $recycle_params = array(
                        'channel_id'=>$channel_id,
                        'billno'=>$waybill_number,
                    );
                    //$stoObj->cancel_billno($recycle_params);
                }
                //大头笔关系解除
                $waybillObj->db->exec("DELETE FROM sdb_logisticsmanager_waybill_extend WHERE waybill_id in(SELECT id FROM sdb_logisticsmanager_waybill WHERE waybill_number='".$waybill_number."')");
            }else{
                $waybillObj->update(array('status'=>2,'create_time'=>time()),array('waybill_number'=>$waybill_number));
            }
            $this->cancel_waybill(array('channel_type'=>$channel_type,'channel_id'=>$channel_id,'billno'=>$waybill_number,'delivery_id'=>$delivery_id));
        }
        return true;
    }

    /**
    * 打接口获取电子面单
    *
    * @access public
    * @param array $params 
    * @return void
    */
    public function request_waybill($params) {
        //$limit = 1000;

        $limit = 5000;        

        $wbFilter = array(
            'channel_id'=>$params['channel_id'],
            'status'=>0,
        );

        //如果缓存池中此类型面单数量小于设定值，则打接口获取面单
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $count = $waybillObj->count($wbFilter);
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $channel = $channelObj->dump($params['channel_id']);
        if($count<$limit) {
            //获取渠道信息

            // 相同的来源渠道只请求一次

            if (!$request_time[$params['channel_id']]) {
                if(in_array($channel['channel_type'],array('wlb','ems','360buy','sto'))) {
                    $func = 'request_waybill_'.$channel['channel_type'];

                    $this->$func($channel);
                }
                $request_time[$params['channel_id']] = 1;            
           }
        }


        return true;
    }

    /**
    * 打接口获取物流宝电子面单
    *
    * @access public
    * @param array $params 
    * @return void
    */
    public function request_waybill_wlb($channel) {
        if($channel) {
            $wlbObj = kernel::single('logisticsmanager_waybill_wlb');
            $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
            $log_id = $waybillLogObj->gen_id();
            //请求接口参数
            $rpcData = array(
                'num' => 500,
                'service_code' => $wlbObj->service_code($channel['logistics_code']),
                'out_biz_code' => $log_id,
                'pool_type' => $wlbObj->pool_type($channel['logistics_code']),
            );
            //重试日志信息
            $logSdf = array(
                'log_id' => $log_id,
                'channel_id' => $channel['channel_id'],
                'status' => 'running',
                'create_time' => time(),
                'params' => $rpcData,
            );
            if($waybillLogObj->insert($logSdf)) {
                $router = kernel::single('apibusiness_router_request');
                $router->setShopId($channel['shop_id'])->get_waybill_number($rpcData);
            }
        }
        return true;
    }

    /**
    * 打接口获取EMS官方电子面单
    *
    * @access public
    * @param array $params 
    * @return void
    */
    public function request_waybill_ems($channel) {
        $emsAccount = explode('|||',$channel['shop_id']);
        if($channel && $channel['bind_status']=='true' && $emsAccount[0] && $emsAccount[1]) {
            $emsObj = kernel::single('logisticsmanager_waybill_ems');
            $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
            $log_id = $waybillLogObj->gen_id();

            //请求接口参数
            $rpcData = array(
                'sysAccount' => $emsAccount[0], //客户号
                'passWord' => $emsAccount[1], //客户密码
                'businessType' => $emsObj->businessType($channel['logistics_code']),
                'billNoAmount' => 100,
                'out_biz_code' => $log_id,
                'channel_id'=>$channel['channel_id'],
            );
            //重试日志信息
            $logSdf = array(
                'log_id' => $log_id,
                'channel_id' => $channel['channel_id'],
                'status' => 'running',
                'create_time' => time(),
                'params' => $rpcData,
            );
            if($waybillLogObj->insert($logSdf)) {
                $emsRpcObj = kernel::single('logisticsmanager_rpc_request_ems');
                $emsRpcObj->get_waybill_number($rpcData);
            }
        }
        return true;
    }
    /**
     * 打接口获取京东官方电子面单
     * Enter description here ...
     * @param unknown_type $channel
     */
    public function request_waybill_360buy($channel) {
        $jdAccount = explode('|||', $channel['shop_id']);
        if ($channel && $jdAccount[0]) {
            $jdObj = kernel::single('logisticsmanager_waybill_360buy');
            $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
            $log_id = $waybillLogObj->gen_id();
            $rpcData = array(
                'preNum' => 1,
                'customerCode' => $jdAccount[0],
                'out_biz_code' => $log_id,
                'businessType' => $jdObj->businessType($channel['logistics_code']),
            );
            //重试日志信息
            $logSdf = array(
                'log_id' => $log_id,
                'channel_id' => $channel['channel_id'],
                'status' => 'running',
                'create_time' => time(),
                'params' => $rpcData,
            );
            if($waybillLogObj->insert($logSdf)) {
                $jdRpcObj = kernel::single('logisticsmanager_rpc_request_360buy');
                $jdRpcObj->get_waybill_number($rpcData);
            }
        }
    }

    /**
     * 获取运单号
     * @param Array $params 面单参数
     */
    public function getWaybillLogiNo($params) {
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $channel = $channelObj->dump($params['channel_id']);
        if (!$channel) die('电子面单厂商不存在');
        $class = 'logisticsmanager_service_' . $channel['channel_type'];
        $obj = kernel::single($class);
        //如果有c_id参数那么当然获取的是补打的面单
        if(isset($params['c_id']) && $params['c_id'] >0){
            $obj->setCurrChildBill($params['c_id']);
        }
        $result = $obj->get_waybill_number($params);
        return $result;
    }

    /**
     * 获取面单扩展信息
     * @param Arrar $params 面单参数
     */
    public function getWayBillExtend($params) {
        
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $channel = $channelObj->dump($params['channel_id']);
        if (!$channel) die('电子面单厂商不存在');
        $result = array();
        $zlList = array('yunda', 'taobao','sto');
        if ($channel && in_array($channel['channel_type'],$zlList)) {
            $class = 'logisticsmanager_service_' . $channel['channel_type'];
            $obj = kernel::single($class);
            $result = $obj->getWayBillExtend($params);
        }
        return $result;
    }

    /**
    * 打接口获取申通电子面单
    *
    * @access public
    * @param array $params 
    * @return void
    */
    public function request_waybill_sto($channel) {
        $stoAccount = explode('|||',$channel['shop_id']);
        if($channel && $channel['bind_status']=='true' && $stoAccount[0] && $stoAccount[1]) {
            $stoObj = kernel::single('logisticsmanager_waybill_sto');
            $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
            $log_id = $waybillLogObj->gen_id();

            //请求接口参数
            $rpcData = array(
                'cusname' => $stoAccount[0], //客户名称
                'cusite' => $stoAccount[1], //网点名称
                'businessType' => $stoObj->getbusinessType($channel['logistics_code']),
                'billNoAmount' => 100,
                'out_biz_code' => $log_id,
                'cuspwd'=>$stoAccount[2], //网点密码
                'channel_id'=>$channel['channel_id'],
            );
            //重试日志信息
            $logSdf = array(
                'log_id' => $log_id,
                'channel_id' => $channel['channel_id'],
                'status' => 'running',
                'create_time' => time(),
                'params' => $rpcData,
            );
            if($waybillLogObj->insert($logSdf)) {
                $stoRpcObj = kernel::single('logisticsmanager_rpc_request_sto');
                $stoRpcObj->setChannelType('sto')->get_waybill_number($rpcData);
            }
        }
        return true;
    }

    /**
     * 是否回收面单类型
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function recycle_channel($channel_id)
    {
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $cFilter = array(
                'channel_id' => $channel_id,
                'status'=>'true',
            );
        $channel = $channelObj->dump($cFilter);
        $channel_type = $channel['channel_type'];
        return $channel_type;
        
    }
  
  
  /**
   * 取消面单号.
   * @param 
   * @return  
   * @access  public
   * @author sunjing@shopex.cn
   */
  function cancel_waybill($params)
  {
      $channel_type = $params['channel_type'];
      //指定来源的才取消
      if ($channel_type && in_array($channel_type,array('taobao'))){
            $class = 'logisticsmanager_rpc_request_' . $channel_type;
            $obj = kernel::single($class);
            $obj->cancel_billno($params);
            
      }
  }

    /**
     * 获取订购地址
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_ship_address($channel_id)
    {
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $channel = $channelObj->dump($channel_id);
        $channel_type = $channel['channel_type'];
        if ($channel_type && in_array($channel_type,array('taobao'))) {
            $class = 'logisticsmanager_rpc_request_' . $channel_type;
            
            $obj = kernel::single($class);
            $params = array(
                'cp_code'=>$channel['logistics_code'],
                'shop_id'=>$channel['shop_id'],
                'channel_id'=>$channel_id,
            );

            $result = $obj->get_ship_address($params);
            return $result;
        }
    }
}