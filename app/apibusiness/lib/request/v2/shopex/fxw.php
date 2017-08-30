<?php
/**
* fxw(分销王系统)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v2
* @author chenping<chenping@shopex.cn>
* @version $Id: fxw.php 2013-13-12 14:44Z
*/
class apibusiness_request_v2_shopex_fxw extends apibusiness_request_v2_shopex_abstract
{

   /**
     * 添加支付单
     *
     * @param Array $delivery 发货单信息
     * @return Bool
     * @author 
     **/
    public function add_delivery($delivery)
    {
        // 发货后打发货接口
        if ($delivery['process'] != 'true') return false;

        return parent::add_delivery($delivery);
    }// TODO TEST

    /**
     * 获取必要的发货数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author 
     **/
    protected function format_delivery($delivery)
    {
        $delivery = parent::format_delivery($delivery);

        #针对B2B 同一货号 前端是普通商品，淘管是捆绑商品 发货处理
        #如果存在捆绑商品 发货时取订单obj上的bn 回写前端,但有个缺陷 如果以后出现捆绑商品支持部分发货 就不行了。目前只是临时解决办法
        
        $orderObjModel = app::get(self::_APP_NAME)->model('order_objects');
        $objCount = $orderObjModel->count(array('order_id'=>$delivery['order']['order_id'],'obj_type'=>'pkg'));

        if($objCount > 0){
            $orderObj = $orderObjModel->getList('*',array('order_id'=>$delivery['order']['order_id']));

            // 订单明细
            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $orderItems = $orderItemModel->getList('*',array('order_id'=>$delivery['order']['order_id'],'delete'=>'false'));
            $order_items = array();
            foreach ($orderItems as $key => $item) {
                $order_items[$item['obj_id']][] = $item;
            }
            unset($orderItems);

            $delivery_items = array();
            foreach ($orderObj as $obj) {
                if ($order_items[$obj['obj_id']]) {
                    if ($obj['obj_type'] == 'pkg') {
                        $delivery_items[] = array(
                            'number' => $obj['quantity'],
                            'name' => trim($obj['name']),
                            'bn' => trim($obj['bn']),
                        );    
                    } else {
                        foreach ($order_items[$obj['obj_id']] as $item) {
                            $delivery_items[] = array(
                                'number' => $item['nums'],//$item['sendnum'] 分销王回写 ExBOY
                                'name' => trim($item['name']),
                                'bn' => trim($item['bn']),
                            );
                        }
                    }     
                }
            }
            
            $delivery['delivery_items'] = $delivery_items;
        }

        return $delivery;
    }// TODO TEST

    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        $params = parent::getDeliveryParam($delivery);

        $params['t_begin'] = $params['t_end'] = $params['modify'] = date('Y-m-d H:i:s',$delivery['last_modified']);

        return $params;
    }// TODO TEST

    /**
     * 发货请求
     *
     * @return void
     * @author 
     **/
    protected function delivery_request($delivery)
    {
        $delivery = $this->format_delivery($delivery);

        if ($delivery === false) return false;

        $param = $this->getDeliveryParam($delivery);

        $callback = array(
           'class' => get_class($this),
           'method' => 'add_delivery_callback',
        );

        $shop_id = $delivery['shop_id'];
        $title = '店铺('.$this->_shop['name'].')添加[交易发货单](订单号:'.$param['tid'].',发货单号:'.$delivery['delivery_bn'].')';

        // 记录发货日志
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $oApi_log->gen_id();

        $opInfo = kernel::single('ome_func')->getDesktopUser();
        //增加更新发货状态日志
        $log = array(
            'shopId'           => $shop_id,
            'ownerId'          => $opInfo['op_id'],
            'orderBn'          => $delivery['order']['order_bn'],
            'deliveryCode'     => $delivery['logi_no'],
            'deliveryCropCode' => $delivery['dly_corp']['type'],
            'deliveryCropName' => $delivery['logi_name'],
            'receiveTime'      => time(),
            'status'           => 'send',
            'updateTime'       => '0',
            'message'          => '',
            'log_id'           => $log_id,
        );
        $shipmentLogModel = app::get(self::_APP_NAME)->model('shipment_log');
        $shipmentLogModel->save($log);

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $orderModel->update(array('sync'=>'run'),array('order_id'=>$delivery['order']['order_id']));

        $write_log = array('log_id' => $log_id);
        $addon['bn'] = $delivery['order']['order_bn'];
        $this->_caller->request(ADD_SHIPPING_RPC,$param,$callback,$title,$shop_id,10,false,$addon,$write_log);

        return true;
    }// TODO TEST

    /**
     * 订单暂停与恢复
     *
     * @param Array $order 订单主表信息
     * @param string $status 状态(true:暂停  false:恢复)
     * @return void
     * @author 
     **/
    public function update_order_pause_status($order,$status='')
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $params['tid'] = $order['order_bn'];

        // 状态不存在取订单上的状态
        $status = $status ? $status : $order['pause'];

        $params['status'] = $status == 'true' ? 'TRADE_PENDING' : 'TRADE_ACTIVE';
        $params['type'] = 'status';
        $params['modify'] = date('Y-m-d H:i:s', time());
        
        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_pause_status_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[订单状态]:'.$params['status'].'(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $this->_caller->request(UPDATE_TRADE_STATUS_RPC,$params,$callback,$title,$shop_id);

        $rs['rsp'] = 'success';

        return  $rs;
    }// TODO TEST

    /**
     * 订单暂停回调
     *
     * @return void
     * @author 
     **/
    public function update_order_pause_status_callback($result)
    {
        return $this->_caller->callback($result);
    }// TODO TEST

    public function update_logistics($delivery,$queue = false){}
    public function update_delivery_status($delivery , $status = '' , $queue = false){}

    public function add_delivery_callback($result)
    {
        #[发货配置]是否启动拆单 ExBOY
        $split_model   = parent::getDeliverySeting();
        
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        
        $request_params = $result->get_request_params();//回写参数 ExBOY
        
        if ($status == 'succ'){

            $msg_id = $result->get_msg_id();

            $apiLogMoel = app::get(self::_APP_NAME)->model('api_log');
            //$apilog_detail = $apiLogMoel->dump(array('log_id'=>$log_id), 'params');
            //$apilog_detail = unserialize($apilog_detail['params']);
            //$apilog_detail = $request_params;
            $order_bn = $request_params['tid'];

            if ($order_bn && $shop_id) {
                // 更新回调时间
                $orderModel->update(array('up_time'=>time()), array('order_bn'=>$order_bn,'shop_id'=>$shop_id));
            }
        }
        
        //[回写]更新发货单状态 ExBOY
        $delivery_bn    = $request_params['shipping_id'];
        if(!empty($delivery_bn) && !empty($split_model))
        {
            $sync_status   = (strtolower($status) == 'succ' ? 'succ' : 'fail');
            $dlysyncModel  = app::get(self::_APP_NAME)->model('delivery_sync');
            $dlysyncModel->update(array('sync'=>$sync_status, 'dateline'=>time()), array('delivery_bn'=>$delivery_bn));
        }

        $msg = json_decode($result->get_result(), true);
        if($msg){
            $msg = serialize($msg);
        }else{
            $msg = $result->get_result();
        }

        # 返回结果中文提示
        $err_msg = $result->get_err_msg();
        if ($err_msg) {
            $msg .= '：'.$err_msg;
        }

        $ret = $this->_caller->callback($result);

        //增加订单状态回写
        //$callback_params = $result->get_callback_params();

        //$log_id = $callback_params['log_id'];
        $log = array('status' => $result->get_status(), 'updateTime' => time(), 'message' => $msg);
        $logFilter = array('log_id' => $log_id);

        $shipment_log = app::get(self::_APP_NAME)->model('shipment_log');
        $shipment_log->update($log,$logFilter);

        $res = $shipment_log->dump(array('log_id' => $log_id), '*');

        if ($res) {
            // 订单信息
            $order = $orderModel->dump(array('order_bn' => $res['orderBn'], 'shop_id' => $res['shopId']), '*');
            if ($order) {
                $order_id = $order['order_id'];
                if (trim($order['sync']) <> 'succ') {
                    $status = $result->get_status();
                } else {
                    $status = 'succ';
                }
                $sdf = array('order_id' => $order_id, 'sync' => $status, 'up_time' => time());

                //增加同步失败类型
                if($status != 'succ') {
                    $sync_code = $result->get_result();
                    $sync_code = trim($sync_code);
                    switch ($sync_code) {
                        case 'W90010':
                        case 'W90012':
                            $sdf['sync_fail_type'] = 'shipped';
                            break;
                        case 'W90011':
                        case 'W90013':
                        case 'W90014':
                            $sdf['sync_fail_type'] = 'params';
                            break;
                        default:
                            $sdf['sync_fail_type'] = 'none';
                            break;
                    }
                }

                // 更新回写状态
                $orderModel->save($sdf);
            }
        }

        return $ret;
    }
}