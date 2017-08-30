<?php
/**
* 请求抽象类
*
* @category apibusiness
* @package apibusiness/lib/request/
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-13-12 14:44Z
*/
@include(dirname(__FILE__).'/apiname.php');
abstract class apibusiness_request_abstract
{
    const _APP_NAME = 'ome';

    protected $_tgver = '';

    protected $_shop = array();

    public function __construct()
    {
        $this->_caller = kernel::single('apibusiness_request_caller',$this);
    }

    /**
     * 设置淘管版本
     *
     * @param String $tgver
     * @return Object
     * @author
     **/
    public function setTgVer($tgver)
    {
        $this->_tgver = $tgver;

        return $this;
    }

    /**
     * 设置店铺
     *
     * @return void
     * @author
     **/
    public function setShop($shop)
    {
        
        $this->_shop = $shop;

        return $this;
    }

    /**
     * 添加售后申请
     *
     * @param Array $returninfo 售后申请
     * @return void
     * @author
     **/
    public function add_aftersale($returninfo){}

    /**
     * 更新售后申请状态
     *
     * @param Array $returninfo 售后申请
     * @return void
     * @author
     **/
    public function update_aftersale_status($returninfo,$status='' , $mod='async'){}

    /**
     * 添加付款单
     * @access public
     * @param  $payment
     */
    public function add_payment($payment){}

    /**
     * 付款单状态更新
     * @access public
     * @param  $payment
     */
    public function update_payment_status($payment){}

    /**
     * 添加退款单
     *
     * @param Array $refund 退款单信息
     * @return void
     * @author
     **/
    public function add_refund($refund){}

    /**
     * 更新退款单状态
     *
     * @param Array $refundinfo 退款单
     * @return void
     * @author
     **/
    public function update_refund_status($refund){}



    /**
     * 添加交易退货单
     *
     * @param Array $reship 退货单信息
     * @return void
     * @author
     **/
    public function add_reship($reship){}

    /**
     * 更改退货单状态
     *
     * @param Array $reship 退货单信息
     * @return void
     * @author
     **/
    public function update_reship_status($reship){}


    /**
     * 获取店铺支付方式
     *
     * @return void
     * @author
     **/
    public function get_paymethod(){}

    /**
     * 清除预占库存
     *
     * @param Array $order 订单信息
     * @return void
     * @author
     **/
    public function clean_stock_freeze($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $params['tid'] = $order['order_bn'];

        $callback = array(
            'class' => get_class($this),
            'method' => 'clean_stock_freeze_callback',
        );

        $title = '店铺('.$this->_shop['name'].')清除预占库存(订单号:'.$order['order_bn'].')';

        $this->_caller->request(UPDATE_TRADE_ITEM_FREEZSTORE_RPC,$params,$callback,$title,$this->_shop);

        $rs['rsp'] = 'success';

        return $rs;
    }

    public function clean_stock_freeze_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更新库存
     *
     * @param Array $stocks 库存
     * @return void
     * @author
     **/
    public function update_stock($stocks,$dorelease = false)
    {

        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$stocks) {
            $rs['msg'] = 'no stocks';
            return $rs;
        }

        $shop_id = $this->_shop['shop_id'];
        $skuIds = array_keys($stocks);

        sort($stocks);

        //保存库存同步管理日志
         $oApiLogToStock = kernel::single('ome_api_log_to_stock');
         $oApiLogToStock->save($stocks,$shop_id);

        //待更新库存BN
        $params['list_quantity'] = json_encode($stocks);
        //附加参数
        $addon = array('all_list_quantity'=>json_encode($stocks));
        $addon['dorelease'] = $dorelease;

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_stock_callback',
            'shop_id' => $shop_id,
        );

        $title = '批量更新店铺('.$this->_shop['name'].')的库存(共'.count($stocks).'个)';

        $test_stock_api = $this->stock_api($stocks);

        //$api_name = 'store.items.quantity.list.update';
        $return = $this->_caller->request($test_stock_api,$params,$callback,$title,$shop_id,10,false,$addon);

        if ($return !== false){
            if ($dorelease === true) {
                if ($skuIds && app::get('inventorydepth')->is_installed()) {
                    app::get('inventorydepth')->model('shop_adjustment')->update(array('release_status'=>'running'),array('id'=>$skuIds));
                }
            }

            app::get(self::_APP_NAME)->model('shop')->update(array('last_store_sync_time'=>time()),array('shop_id'=>$shop_id));
        }

        $rs['rsp'] = 'success';

        return $rs;
    }

    public function update_stock_callback($result)
    {
        $callback_params = $result->get_callback_params();
        $status          = $result->get_status();
        $res             = $result->get_result();
        $data            = $result->get_data();
        $request_params = $result->get_request_params();
        $msg_id = $result->get_msg_id();

        // 店铺信息
        if ($callback_params['shop_id']) {
            $shopModel = app::get(self::_APP_NAME)->model('shop');
            $this->_shop = $shopModel->dump(array('shop_id'=>$callback_params['shop_id']),'business_type');
        }
        // LOG PARAMS
        $request_params['all_list_quantity'] = $request_params['list_quantity'];
        $log_params = array($this->stock_api($request_params),$request_params,array(get_class($this),'update_stock_callback',$callback_params));

        $log_id = $callback_params['log_id'];
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');

        $rsp = 'succ';
        if ($status != 'succ' && $status != 'fail' ){
            $res = $status . ome_api_func::api_code2msg('re001', '', 'public');
            $rsp = 'fail';
        }

        if($status == 'succ'){
            $api_status = 'success';
        }else{
            $api_status = 'fail';
        }

        //更新失败的bn会返回，然后下次retry时，只执行失败的bn更新库存
        $err_item_bn = $data['error_response'];
        //错误等级
        if (is_array($data) && isset($data['error_level']) && !empty($data['error_level'])){
            $addon['error_lv'] = $data['error_level'];
        }
        if (!is_array($err_item_bn)){
            $err_item_bn = json_decode($data['error_response'],true);
        }

        //$log_info = $oApi_log->dump($log_id);
        //$log_params = unserialize($log_info['params']);
        //调整通过缓存读取请求参数
        //$log_params = $request_params;
        //$msg_id = $log_params[3]['msg_id'];

        $itemsnum = json_decode($log_params[1]['list_quantity'],true);


        $new_itemsnum = $true_itemsnum = array();
        foreach($itemsnum as $k=>$v){
            if(in_array($v['bn'],$err_item_bn) && !in_array($v['bn'],(array) $data['true_bn']) ){
                $new_itemsnum[] = $v;
            } else {
                $true_itemsnum[] = $v;
            }
        }

        if (app::get('inventorydepth')->is_installed()) {
            $adjustmentModel = app::get('inventorydepth')->model('shop_adjustment');
            if ($err_item_bn) {
                $adjustmentModel->update(array('release_status'=>'fail'),array('shop_id'=>$callback_params['shop_id'],'shop_product_bn'=>$err_item_bn));
            }
            if ($data['true_bn']) {
                $adjustmentModel->update(array('release_status'=>'success'),array('shop_id'=>$callback_params['shop_id'],'shop_product_bn'=>$data['true_bn']));
            }
        }


        //当返回失败且BN为空时不更新list_quantity
        if ($api_status != 'fail' || $new_itemsnum){
            $log_params[1]['list_quantity'] = json_encode($new_itemsnum);
        }else{
            $new_itemsnum = $itemsnum;
        }

        if ($data['error_bn'] || $data['no_bn']) {
            if ($data['error_bn']) {
                $msg[] = '更新失败货号【'.implode(',', $data['error_bn']).'】';
            }
            if ($data['no_bn']) {
                $msg[] = '无效货号【'.implode(',', $data['no_bn']).'】';
            }
            $msg = $res.':<br/>'.implode('<br/>',$msg);
        } elseif($status == 'succ') {
            $msg = '成功';
        } else {
            $msg = '失败';
        }

        $oApi_log->update_log($log_id,$msg,$api_status,$log_params,$addon);

        $log_detail = array(
            'msg_id' => $msg_id,
            'params' => serialize($log_params),
        );

        //更新库存同步管理的执行状态
        $oApiLogToStock = kernel::single('ome_api_log_to_stock');
        if ($new_itemsnum) {
            $oApiLogToStock->save_callback($new_itemsnum,'fail',$callback_params['shop_id'],$res,$log_detail);
        }

        if ($true_itemsnum) {
            $oApiLogToStock->save_callback($true_itemsnum,'success',$callback_params['shop_id'],$res,$log_detail);
        }

        return array('rsp'=>$rsp,'res'=>$res,'msg_id'=>$msg_id);
    }

    //发货状态
    static public $ship_status = array(
        'succ'     =>'SUCC',
        'failed'   =>'FAILED',
        'cancel'   =>'CANCEL',
        'lost'     =>'LOST',
        'progress' =>'PROGRESS',
        'timeout'  =>'TIMEOUT',
        'ready'    =>'READY',
        'stop'     =>'STOP',
        'back'     =>'BACK',
        'verify'   => 'VERIFY',//TODO:新增加的校验
    );
    /**
     * 添加发货单
     *
     * @param Array $delivery
     * @return void
     * @author
     **/
    public function add_delivery($delivery)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$delivery) {
            $rs['msg'] = 'no delivery';
            return $rs;
        }

        $this->delivery_request($delivery);

        $rs['rsp'] = 'success';

        return $rs;
    }

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

        if ($delivery['type'] == 'reject') {
            $title = '店铺('.$this->_shop['name'].')添加[交易发货单](<font color="red">补差价</font>订单号:'.$delivery['order']['order_bn'].')';
        } else {
            $title = '店铺('.$this->_shop['name'].')添加[交易发货单](订单号:'.$param['tid'].',发货单号:'.$delivery['delivery_bn'].')';
        }
        $addon['bn'] = $delivery['order']['order_bn'];

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

        # 已经解绑的店铺订单，直接将订单设为回写失败
        if (!$this->_shop['node_id'] && $delivery['order']['createway'] == 'matrix') {
            $log['status'] = 'fail';
            $log['message'] = '店铺已解绑';
        }

        $shipmentLogModel = app::get(self::_APP_NAME)->model('shipment_log');
        $shipmentLogModel->save($log);

        $orderModel = app::get(self::_APP_NAME)->model('orders');

        $updateData = array('sync'=>'run');

        # 已经解绑的店铺订单，直接将订单设为回写失败
        if (!$this->_shop['node_id'] && $delivery['order']['createway'] == 'matrix') {
            $updateData['sync'] = 'fail';
        }

        $orderModel->update($updateData,array('order_id'=>$delivery['order']['order_id']));                

        $write_log = array('log_id' => $log_id);
        
        $this->_caller->request($this->delivery_api($delivery),$param,$callback,$title,$shop_id,10,false,$addon,$write_log);

        return true;
    }// TODO TEST

    /**
     * 取得发货接口名
     *
     * @return void
     * @author
     **/
    protected function delivery_api($delivery = '')
    {
        return LOGISTICS_OFFLINE_RPC;
    }// TODO TEST

    /**
     * 取得库存回写接口名
     *
     * @return void
     * @author
     **/
    protected function stock_api($stocks)
    {
        return UPDATE_ITEMS_QUANTITY_LIST_RPC;
    }
    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author
     **/
    abstract protected function getDeliveryParam($delivery);

    /**
     * 获取必要的发货数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author
     **/
    protected function format_delivery($delivery)
    {
        $orderModel     = app::get(self::_APP_NAME)->model('orders');
        $deliOrderModel = app::get(self::_APP_NAME)->model('delivery_order');
        $deliveryModel      = app::get(self::_APP_NAME)->model('delivery');

        // 判断发货单类型
        switch ($delivery['type']) {
            case 'reject':  // 售后发货单
                // 订单信息
                if ($delivery['order']['order_id']) {
                    $order_id = $delivery['order']['order_id'];
                } else {
                    $deliOrder = $deliOrderModel->dump(array('delivery_id'=>$delivery['delivery_id']),'*');
                    $order_id = $deliOrder['order_id'];
                }

                $order = $orderModel->dump(array('order_id'=>$order_id),'order_bn,shop_id,is_delivery,mark_text,sync,ship_area,order_id,self_delivery,createway');

                // 发货人地址
                $consignee_area = $this->_shop['area'];
                kernel::single('ome_func')->split_area($consignee_area);
                $receiver_state    = ome_func::strip_bom(trim($consignee_area[0]));
                $receiver_city     = ome_func::strip_bom(trim($consignee_area[1]));
                $receiver_district = ome_func::strip_bom(trim($consignee_area[2]));

                $delivery['receiver']['receiver_state']    = $receiver_state;
                $delivery['receiver']['receiver_city']     = $receiver_city;
                $delivery['receiver']['receiver_district'] = $receiver_district;

                $delivery['logi_no'] = $order['order_bn'];
                $delivery['logi_name'] = '其他物流公司';

                $delivery['dly_corp'] = array(
                    'type' => 'OTHER',
                    'name' => '其他物流公司',
                );

                break;
            case 'normal':  // 普通发货单
                // 如果是合并发货单，取父发货单物流信息
                $parent_id = $delivery['parent_id'];
                if ($parent_id > 0) {
                    $pDelivery = $deliveryModel->dump(array('delivery_id'=>$parent_id),'*');
                    $delivery['status']    = $pDelivery['status'];
                    $delivery['logi_id']   = $pDelivery['logi_id'];
                    $delivery['logi_name'] = $pDelivery['logi_name'];
                    $delivery['logi_no']   = $pDelivery['logi_no'];
                    $delivery['logi_code'] = $pDelivery['logi_code'];
                }

                // 物流发货单去BOM头
                $pattrn              = chr(239).chr(187).chr(191);
                $delivery['logi_no'] = trim(str_replace($pattrn, '', $delivery['logi_no']));

                // 如果订单信息不存在，重新读取
                if (!$delivery['order']) {
                    $deliOrder = $deliOrderModel->dump(array('delivery_id'=>$delivery['delivery_id']),'*');

                    $delivery['order'] = $orderModel->dump(array('order_id'=>$deliOrder['order_id']),'order_bn,shop_id,is_delivery,mark_text,sync,ship_area,order_id,self_delivery,createway');
                }

                // 发货地址
                $consignee_area = $this->_shop['area'];
                kernel::single('ome_func')->split_area($consignee_area);
                $receiver_state    = ome_func::strip_bom(trim($consignee_area[0]));
                $receiver_city     = ome_func::strip_bom(trim($consignee_area[1]));
                $receiver_district = ome_func::strip_bom(trim($consignee_area[2]));

                $delivery['receiver']['receiver_state']    = $receiver_state;
                $delivery['receiver']['receiver_city']     = $receiver_city;
                $delivery['receiver']['receiver_district'] = $receiver_district;

                // 物流公司信息
                $dlyCorpModel = app::get(self::_APP_NAME)->model('dly_corp');
                $delivery['dly_corp'] = $dlyCorpModel->dump(array('corp_id'=>$delivery['logi_id']),'type,name');

                break;
            default:
                return false;

                break;
        }

        return $delivery;
    }// TODO TEST

    /**
     * 发货回调
     *
     * @return void
     * @author
     **/
    public function add_delivery_callback($result)
    {
        #[发货配置]是否启动拆单 ExBOY
        $split_model   = $this->getDeliverySeting();
        
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        
        $request_params = $result->get_request_params();//回写参数 ExBOY
        $order_bn       = $request_params['tid'];//ExBOY
        
        if ($status == 'succ'){
            $request_params = $result->get_request_params();

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
        if(!empty($split_model))
        {
            $logi_no        = $request_params['logistics_no'];
            $delivery_bn    = $request_params['shipping_id'];
            
            $sync_status   = (strtolower($status) == 'succ' ? 'succ' : 'fail');
            $dlysyncModel  = app::get(self::_APP_NAME)->model('delivery_sync');
            
            if(!empty($delivery_bn))
            {
                $dlysyncModel->update(array('sync'=>$sync_status, 'dateline'=>time()), array('delivery_bn'=>$delivery_bn));
            }
            elseif($order_bn && $logi_no)//处理淘宝回写
            {
                $dlysyncModel->update(array('sync'=>$sync_status, 'dateline'=>time()), array('order_bn'=>$order_bn, 'logi_no'=>$logi_no));
            }
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

    public function update_logistics($delivery,$queue = false){}

    /**
     * 订单编辑 iframe
     *
     * @return MIX
     * @author
     **/
    public function update_iframe($order,$is_request=true,$ext=array())
    {
        // 默认本地编辑
        $data = array('edit_type'=>'local');

        return array('rsp'=>'success','msg'=>'本地订单编辑','data'=>$data);
    }// TODO TEST

    /**
     * 更新订单
     *
     * @param Array $order 订单主表信息
     * @return MIX
     * @author
     **/
    public function update_order($order){}

    /**
     * 更新订单状态
     *
     * @param int $order_id 订单主键ID
     * @param string $status 状态
     * @param string $memo 备注
     * @param string $mode 请求类型:sync同步  async异步
     * @return void
     * @author
     **/
    public function update_order_status($order , $status='' , $memo='' , $mode='sync')
    {}

    /**
     * 订单暂停与恢复
     *
     * @param Array $order 订单主表信息
     * @param string $status 状态(true:暂停  false:恢复)
     * @return MIX
     * @author
     **/
    public function update_order_pause_status($order,$status){}

    /**
     * 更新订单发票信息
     *
     * @param Array $order 订单信息
     * @return MIX
     * @author
     **/
    public function update_order_tax($order)
    {}

    /**
     * 更新订单发货状态
     *
     * @param int $order_id 订单主键ID
     * @param boolean $queue 是否走队列
     * @return void
     * @author
     **/
    public function update_order_ship_status($order,$queue = false)
    {}

    /**
     * 更新订单支付状态
     *
     * @param Array $order 订单主表信息
     * @return MIX
     * @author
     **/
    public function update_order_pay_status($order)
    {}

    /**
     * 更新订单交易备注
     *
     * @param int $order 订单主表信息
     * @param array $memo 备注内容
     * @return MIX
     * @author
     **/
    public function update_order_memo($order,$memo){}

    /**
     * 添加订单交易备注
     *
     * @param int $order_id 订单主键ID
     * @param array $memo 备注内容
     * @return void
     * @author
     **/
    public function add_order_memo($order,$memo)
    {}

    /**
     * 添加买家留言
     *
     * @param array $order 订单主表信息
     * @param array $memo 留言
     * @return void
     * @author
     **/
    public function add_order_custom_mark($order,$memo)
    {}


    /**
     * 更新交易收货人信息
     *
     * @param Array $order 订单信息
     * @return void
     * @author
     **/
    public function update_order_shippinginfo($order)
    {}

    /**
     * 更新交易发货人信息
     *
     * @param Array $order 订单信息
     * @return MIX
     * @author
     **/
    public function update_order_consignerinfo($order)
    {}

    /**
     * 更新代销人信息
     *
     * @return void
     * @author
     **/
    public function update_order_sellagentinfo($order)
    {}

    /**
     * 更新订单失效时间
     *
     * @param array $order 订单
     * @param string $order_limit_time 订单失效时间
     * @return void
     * @author
     **/
    public function update_order_limittime($order,$order_limit_time)
    {}

    /**
     * 获取店铺订单详情
     *
     * @param String $order_bn 订单号
     * @return void
     * @author
     **/
    public function get_order_detial($order_bn,$order_type='direct')
    {
        $params['tid'] = $order_bn;

        $shop_id = $this->_shop['shop_id'];

        $title = "店铺(".$this->_shop['name'].")获取前端店铺".$order_bn."的订单详情";

        if ($order_type == 'direct') {
            $api_name = GET_TRADE_FULLINFO_RPC;
        } else {
            $api_name = GET_FENXIAO_TRADE_FULLINFO_RPC;
        }

        $rsp = $this->_caller->call($api_name,$params,$shop_id,$time);
       
        $result = array();
        $result['rsp']     = $rsp->rsp;
        $result['err_msg'] = $rsp->err_msg;
        $result['msg_id']  = $rsp->msg_id;
        $result['res']     = $rsp->res;
        $result['data']    = json_decode($rsp->data,1);
        $result['order_type'] = $order_type;

        $Apilog = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $Apilog->gen_id();

        $callback = array(
            'class'   => get_class($this),
            'method'  => 'get_order_detial',
            '2'       => array(
                'log_id'  => $log_id,
                'shop_id' => $shop_id,
            ),
        );
        $Apilog->write_log($log_id,$title,'apibusiness_router_request','get_order_detial',array($api_name, $params, $callback),'','request','running','','','api.store.trade',$order_bn);
        if($rsp){
           if($rsp->rsp == 'succ'){
                //api日志记录
                $api_status = 'success';
                $msg = '获取订单详情成功<BR>';
                $filter_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $Apilog->update($filter_data,array('log_id'=>$log_id));
           }else{
                //api日志记录
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '获取订单详情失败('.$err_msg.')<BR>';
                $filter_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $Apilog->update($filter_data,array('log_id'=>$log_id));
           }
        }

        return $result;
    }

    /**
     * 获取店铺指定时间范围内的订单列表
     *
     * @param Int $start_time 开始时间
     * @param Int $end_time 结束时间
     * @param Array $shop 店铺信息
     * @return MIX
     * @author
     **/
    public function get_order_list($start_time,$end_time)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$data,'is_update_time'=>'false');

        $shop_id = $this->_shop['shop_id'];

        $orderModel = app::get(self::_APP_NAME)->model('orders');

        $params['start_time'] = date("Y-m-d H:m:s",$start_time);
        $params['end_time']   = date("Y-m-d H:m:s",$end_time);
        $params['page_size']  = 100;
        $params['fields']     = 'tid,status,pay_status,ship_status,modified';
        $result = $this->_caller->call(GET_TRADES_SOLD_RPC,$params,$shop_id,10);

        $return_data['rsp']     = $result->rsp;
        $return_data['err_msg'] = $result->err_msg;
        $return_data['msg_id']  = $result->msg_id;
        $return_data['res']     = $result->res;
        $return_data['data']    = json_decode($result->data,1);

        if($return_data['rsp'] == 'succ')
        {
            if(intval($return_data['data']['total_results'])<1){
               $rs['msg']            = '该时间段内没有订单.';
               $rs['is_update_time'] = 'true';
               $rs['msg_id']         = $return_data['msg_id'];
               $rs['rsp']            = 'success';
               $rs['data']           = array();
               return $rs;
            }

            $page_total = ceil($return_data['data']['total_results']/$params['page_size']);

            $tids = array();
            $aTmp = array();
            for($i=1;$i<=$page_total;$i++)
            {
                $matrix_tids              = array();
                $order_data               = array();
                $return_data_page['data'] = array();
                $params['page_no']        = $i;
                $resp = $this->_caller->call(GET_TRADES_SOLD_RPC,$params,$shop_id,10);

                $return_data_page['rsp']     = $resp->rsp;
                $return_data_page['err_msg'] = $resp->err_msg;
                $return_data_page['msg_id']  = $resp->msg_id;
                $return_data_page['res']     = $resp->res;
                $return_data_page['data']    = json_decode($resp->data,1);

                if($return_data_page['rsp'] == 'succ')
                {

                    foreach($return_data_page['data']['trades'] as $k=>$v){
                        $matrix_tids[$v['tid']]['status']      = $v['status'];
                        $matrix_tids[$v['tid']]['tid']         = $v['tid'];
                        $matrix_tids[$v['tid']]['modified']    = $v['modified'];
                        $matrix_tids[$v['tid']]['ship_status'] = $v['ship_status'];
                        $matrix_tids[$v['tid']]['pay_status']  = $v['pay_status'];
                    }//获取到矩阵返回的数据后，对数据进行重组


                    $matrix_tid_keys = array_keys($matrix_tids);
                    $row = $orderModel->getList('outer_lastmodify,order_bn',array('order_bn'=>$matrix_tid_keys));

                    //$row = $orderModel->db->select("select outer_lastmodify,order_bn from sdb_ome_orders where order_bn in ('".implode("','",$matrix_tid_keys)."')");

                    if(empty($row)){
                       $aTmp = array_merge($matrix_tids,$aTmp);
                    }else{
                        $local_exist_tids = array();
                        foreach($row as $return_k=>$return_v)
                        {
                            if($row && strtotime($matrix_tids[$order_bn]['modified'])<$return_v['outer_lastmodify']){
                                $local_exist_tids[] = $return_v['order_bn'];//将本地不需要的订单放入数组
                            }
                        }

                        foreach ($local_exist_tids as $value) {
                            unset($matrix_tids[$value]);
                        }//将不需要修改的订单从总list中删除.

                        $aTmp = array_merge($matrix_tids,$aTmp);
                    }

                }
                else{
                   $rs['msg']    = $return_data_page['err_msg'];
                   $rs['msg_id'] = $return_data_page['msg_id'];
                   return $rs;
                };
            }
            if(count($aTmp)==0){
               $rs['msg']            = '经过比对,该时间段内没有发现漏单情况';
               $rs['is_update_time'] = 'true';
            }
        }else{
            $rs['msg']    = $return_data['err_msg'];
            $rs['msg_id'] = $return_data['msg_id'];
            return $rs;
        }

        $rs['data']   = $aTmp;
        $rs['rsp']    = 'success';
        $rs['msg_id'] = $return_data_page['msg_id'];

        return $rs;
    }

    /**
     * 实时下载店铺商品
     *
     * @param Array $filter 筛选条件(approve_status)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function items_all_get($filter,$shop_id,$offset=0,$limit=100)
    {
        $timeout = 20;

        if(!$shop_id) return false;

        $param = array(
                'page_no'        => $offset,
                'page_size'      => $limit,
                'fields'         => 'iid,outer_id,bn,num,title,default_img_url,modified,detail_url,approve_status,skus,price,barcode ',
            );

        $param = array_merge((array)$param,(array)$filter);

        //$api_name = 'store.items.all.get';

        $result = $this->_caller->call(GET_ITEMS_ALL_RPC,$param,$shop_id,$timeout);
        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call(GET_ITEMS_ALL_RPC,$param,$shop_id,$timeout);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * 实时下载店铺商品
     *
     * @param Array $filter 筛选条件(approve_status)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function fenxiao_products_get($filter,$shop_id,$offset=0,$limit=20)
    {
        $timeout = 20;

        if(!$shop_id) return false;

        $param = array(
                'page_no'        => $offset,
                'page_size'      => $limit,
            );

        $param = array_merge((array)$param,(array)$filter);

        $result = $this->_caller->call(GET_FENXIAO_PRODUCTS,$param,$shop_id,$timeout);

        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call(GET_FENXIAO_PRODUCTS,$param,$shop_id,$timeout);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }

        return $result;
    }


    /**
     * 更新分销商品
     *
     * @param Array $param
     **/
    public function fenxiao_product_update($param)
    {
        $timeout = 20;
        
        if (!$param['pid']) {
            return false;
        }

        $result = $this->_caller->call(UPDATE_FENXIAO_PRODUCT,$param,$this->_shop['shop_id'],$timeout);

        return $result;
    }

    /**
     * 根据IID，实时下载店铺商品
     *
     * @param Array $iids 商品ID(不要超过限度20个)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array
     **/
    public function items_list_get($iids,$shop_id)
    {

        if(!$iids || !$shop_id) return false;

        if(is_array($iids)) $iids = implode(',', $iids);

        $timeout = 10;

        $param = array(
            'iids' => $iids,
        );

        $api_name = GET_ITEMS_LIST_RPC;

        $result = $this->_caller->call($api_name,$param,$shop_id,$timeout);
        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call($api_name,$param,$shop_id,$timeout);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * 获取单个商品明细
     *
     * @param Int $iid商品ID
     * @param String $shop_id 店铺ID
     * @return void
     * @author
     **/
    public function item_get($iid,$shop_id)
    {
        if(!$iid || !$shop_id) return false;

        $timeout = 20;

        $param = array(
            'iid' => $iid,
        );

        $api_name = GET_ITEM_RPC;

         $result = $this->_caller->call($api_name,$param,$shop_id,$timeout);
        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call($api_name,$param,$shop_id,$timeout);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }
        
        return $result;
    }

    /**
     * 目前适用苏宁()
     *
     * @param String $productCode 商品编码(支持商品ID,货品ID)
     * @return void
     * @author 
     **/
    public function items_custom_get($productCode)
    {
        if (!$productCode) return false;

        $shop_id = $this->_shop['shop_id'];

        $timeout = 20;

        $api_name = GET_ITEMS_CUSTOM;

        $params = array(
            'iid' => $productCode,
            //'bn'=>$productCode,
        );

        $result = $this->_caller->call($api_name,$params,$shop_id,$timeout);

        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call($api_name,$params,$shop_id,$timeout);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }
        
        return $result;
    }

    /**
     * 下载货品
     *
     * @param Array $sku
     * $sku = array(
     *  'sku_id' => {SKU的ID}
     *  'iid'    => {商品ID}
     *  'seller_uname' => {卖家帐号}
     * );
     * @param String $shop_id 店铺ID
     * @return void
     * @author
     **/
    public function item_sku_get($sku,$shop_id)
    {
        if(!$sku || !$shop_id) return false;

        $timeout = 10;

        $params = array(
            'sku_id' => $sku['sku_id'],
            'iid' => $sku['iid'],
            'num_iid' => $sku['iid'],
        );

        if($sku['seller_uname']) $params['seller_uname'] = $sku['seller_uname'];

        $api_name = GET_ITEM_SKU_RPC;

        $result = $this->_caller->call($api_name,$params,$shop_id,$timeout);
        if ($result->res_ltype > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->_caller->call($api_name,$params,$shop_id,$timeout);
                if ($result->res_ltype == 0) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * 单个更新上下架
     *
     * @return void
     * @author
     **/
    public function approve_status_update($approve)
    {
        if(!$approve) return false;

        $timeout = 60;
        $shop_id = $this->_shop['shop_id'];

        //$shop_info = app::get('ome')->model('shop')->getList('name',array('shop_id'=>$shop_id),0,1);

        $title = '更新店铺('.$this->_shop['name'].')的('.$approve['title'].')商品上下架状态';

        $params['iid'] = $approve['iid'];
        $params['approve_status'] = $approve['approve_status'];

        if($approve['approve_status'] == 'onsale') $params['num'] = $approve['num'];

        if($approve['outer_id']) $params['outer_id'] = $approve['outer_id'];

        //$api_name = 'store.item.approve_status.update';

        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $oApi_log->gen_id();
        $operinfo = ome_func::getDesktopUser();
        $params = array_merge((array) $params,(array) $operinfo);
        $oApi_log->write_log($log_id,$title,get_class($this),'call',array(UPDATE_ITEM_APPROVE_STATUS_RPC, $params),'','request','running');

        $result = $this->_caller->call(UPDATE_ITEM_APPROVE_STATUS_RPC,$params,$shop_id,$timeout);
        
        // 记日志
        $apilog_status = ($result->rsp == 'succ') ? 'success' : 'fail';
        $msg = ($result->rsp == 'succ') ? '成功' : '失败';
        $oApi_log->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$apilog_status),array('log_id'=>$log_id));

        return $result;
    }

    /**
     * 更新商品上下架
     *
     * @param Array $approve_status 上下架参数
     * @param String $shop_id 店铺ID
     * @param Array $addon 附加参数
     * @return Array
     **/
    public function approve_status_list_update($approve_status)
    {
        if(!$approve_status) return false;

        $approve_status_msg = '';
        switch ($approve_status[0]['approve_status']) {
            case 'onsale':
                $approve_status_msg = '上架';
                break;
            case 'instock':
                $approve_status_msg = '下架';
                break;
            case 'is_pre_delete':
                $approve_status_msg = '预删除';
                break;
        }

        $shop_id = $this->_shop['shop_id'];

        $title = '批量'.$approve_status_msg.'店铺('.$this->_shop['name'].')的商品(共'.count($approve_status).'个)';

        //$timeout = 60;

        $params = array(
            'list_quantity' => json_encode($approve_status),
        );

        $callback = array(
            'class' => get_class($this),
            'method' => 'approve_status_update_callback',
        );

        $return = $this->_caller->request(UPDATE_ITEM_APPROVE_STATUS_LIST_RPC,$params,$callback,$title,$shop_id,10,false,$addon);
    }

    /**
     * 上下架回调方法
     *
     * @param Object $result
     * @return void
     * @author
     **/
    public function approve_status_update_callback($result)
    {
        $callback_params = $result->get_callback_params();  // 请求时的参数
        $status          = $result->get_status();           // 返回状态
        $res             = $result->get_result();           // 错误码
        $data            = $result->get_data();             // 返回信息
        $request_params = $result->get_request_params();
        $msg_id = $result->get_msg_id();

        if ($status != 'succ' && $status != 'fail' ){
            $res = $status . ome_api_func::api_code2msg('re001', '', 'public');
        }

        $api_status = ($status == 'succ') ? 'success' : 'fail';

        $log_id = $callback_params['log_id'];
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        //$log_info = $oApi_log->dump($log_id);
        //$log_params = unserialize($log_info['params']);
        //$log_params = $request_params;
        //$msg_id = $log_params[3]['msg_id'];

        $list_quantity = json_decode($request_params['list_quantity'],true);
        $approve_status = $list_quantity[0]['approve_status'];
        $approve_status_msg = '';
        switch ($approve_status) {
            case 'onsale':
                $approve_status_msg = '上架';
                break;
            case 'instock':
                $approve_status_msg = '下架';
                break;
            case 'is_pre_delete':
                $approve_status_msg = '预删除';
                break;
        }

        # 错误BN
        $error_bn = $data['error_bn'];
        # 错误结果
        $error_response = $data['error_response'];
        # 无BN
        $no_bn = $data['no_bn'];
        # 成功上下架的BN
        $true_bn = $data['true_bn'];

        # 更新状态
        if ($true_bn) {
            $itemFilter = array(
                'bn' => $true_bn,
                'shop_id' => $callback_params['shop_id'],
            );
            if (app::get('inventorydepth')->is_installed()) {
                app::get('inventorydepth')->model('shop_items')->update(array('approve_status'=>$approve_status),$itemFilter);
            }
        }

        $msg = array();
        if ($error_bn) {
            $msg[] = '错误货号【'.implode('、', $error_bn).'】';
        }

        if ($no_bn) {
            $msg[] = '无货号【'.implode('、', $no_bn).'】';
        }

        if ($true_bn) {
            $msg[] = '上下架成功货号【'.implode('、', $true_bn).'】';
        }

        $msg = $res ? $res : implode('<br>', $msg);
        $oApi_log->update_log($log_id,$msg,$api_status);

        return array('rsp'=>$status,'res'=>$res,'msg_id'=>$msg_id);
    }

    /**
     * RPC同步返回数据接收
     * @access public
     * @param json array $res RPC响应结果
     * @param array $params 同步日志ID
     */
    public function response_log($res, $params){
        $response = json_decode($res, true);
        if (!is_array($response)){
            $response = array(
                'rsp' => 'running',
                'res' => $res,
            );
        }
        $status = $response['rsp'];
        $result = $response['res'];

        if($status == 'running'){
            $api_status = 'running';
        }elseif ($result == 'rx002'){
            //将解除绑定的重试设置为成功
            $api_status = 'success';
        }else{
            $api_status = 'fail';
        }

        $log_id = $params['log_id'];
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');

        //更新日志数据
        $oApi_log->update_log($log_id, $result, $api_status);

        if ($response['msg_id']){
            //更新日志msg_id及在应用级参数中记录task
            /*
            $log_info = $oApi_log->dump($log_id);
            $log_params = unserialize($log_info['params']);
            $rpc_key = $params['rpc_key'];
            $log_params[1]['task'] = $rpc_key;
            $update_data = array(
                'msg_id' => $response['msg_id'],
                'params' => serialize($log_params),
            );
            */
            $update_data = array(
                'msg_id' => $response['msg_id'],
            );
            $update_filter = array('log_id'=>$log_id);
            $oApi_log->update($update_data, $update_filter);
        }

        //只有接口类型为库存更新时，才调用库存callback函数
    }

    public function update_delivery_status($delivery , $status = '' , $queue = false)
    {}

    /**
     * 获取发票抬头
     *
     * @return void
     * @author
     **/
    public function get_order_invoice($order_bn)
    {
        //$api_name ='store.trade.invoice.get';
        if(!$order_bn) return false;

        $param = array(
            'tid' => $order_bn,
        );

        $timeout = 5;

        $shop_id = $this->_shop['shop_id'];

        $result = $this->_caller->call(GET_TRADE_INVOICE_RPC, $param, $shop_id, $timeout);

        return $result;
    }

    /**
     * 退款留言/凭证查询
     *
     * @return void
     * @author
     **/
    public function get_refund_message($refundinfo){
        if (!$refundinfo['refund_bn']) return false;
        $params = array(
            'refund_id'=>  $refundinfo['refund_bn'],  
        );
        $tbbusiness_type = strtoupper($this->_shop['tbbusiness_type']);
        if ($tbbusiness_type == 'B') {
           
            $params['refund_type'] = $refundinfo['refund_phase'];
            $params['refund_version'] = $refundinfo['refund_version'];
            $api_method = GET_REFUND_I_MESSAGE_TMALL;
        }else{
            $api_method = GET_REFUND_MESSAGE;
        }
        $timeout = 20;
        $shop_id = $this->_shop['shop_id'];
        $result = $this->_caller->call($api_method, $params, $shop_id, $timeout);
        
       	return $result;
    }
    
    /**
     * 拒绝退款单.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function refuse_refund($refund_bn)
    {
        
    }
     
    /**
     * 更新退款申请单状态
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function update_refund_apply_status($refundinfo,$status,$mod)
    {
        
    }
    /**
     * 退款留言凭证回写
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    public function add_refundmemo($refundinfo)
    {
        
    }
    /**
    * 退货拒绝
    *
    */
    public function refuse_return($returninfo){
    }
    
    /**
     * 退款单状态的接受
     */
    public function accept_refundstatus()
    {
        
    }
    
    /**
     * 查询卖家地址库
     * @param   type    $shop_id    rdef
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function searchAddress($rdef)
    {
        
    }
    
    /**
     * 退货物流单号回填
     * @param   type    
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function update_return_logistics()
    {
        
    }
    
    /**
     * 获取店铺退款单详情
     *
     * @param String $order_bn 订单号
     * @return void
     * @author
     **/
    public function get_refund_detial($refund_id ,$refund_phase,$tid)
    {
        $params['refund_id'] = $refund_id;
        $params['refund_phase'] = $refund_phase;
        $shop_id = $this->_shop['shop_id'];
        $params['tid'] = $tid;
        $title = "店铺(".$this->_shop['name'].")获取前端店铺".$order_bn."的售后单详情";

        $api_name = GET_TRADE_REFUND_RPC;
        $tbbusiness_type = strtoupper($this->_shop['tbbusiness_type']);
        #天猫的凭证获取走新接口
        if($tbbusiness_type == 'B'){
            $api_name = GET_TRADE_REFUND_I_RPC;
        }
        $rsp = $this->_caller->call($api_name,$params,$shop_id,$time);
       
        $result = array();
        $result['rsp']     = $rsp->rsp;
        $result['err_msg'] = $rsp->err_msg;
        $result['msg_id']  = $rsp->msg_id;
        $result['res']     = $rsp->res;
        $result['data']    = json_decode($rsp->data,1);

        if($tbbusiness_type == 'B'){
            if(!empty( $result['data'])){
                #新接口上，需要加这个字段
                $result['data']['spider_type'] = 'tm_refund_i';
            }
        }
        
        $Apilog = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $Apilog->gen_id();

        $callback = array(
            'class'   => get_class($this),
            'method'  => 'get_refund_detial',
            '2'       => array(
                'log_id'  => $log_id,
                'shop_id' => $shop_id,
            ),
        );
        $Apilog->write_log($log_id,$title,'apibusiness_router_request','get_refund_detial',array($api_name, $params, $callback),'','request','running','','','api.store.trade',$order_bn);
        if($rsp){
           if($rsp->rsp == 'succ'){
                //api日志记录
                $api_status = 'success';
                $msg = '获取售后单详情成功<BR>';
                $filter_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $Apilog->update($filter_data,array('log_id'=>$log_id));
           }else{
                //api日志记录
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '获取售后单详情失败('.$err_msg.')<BR>';
                $filter_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $Apilog->update($filter_data,array('log_id'=>$log_id));
           }
        }

        return $result;
    }

    /**
     * 淘宝全链路
     * @param String $topic
     * @param Json $content
     */
    public function add_tmc_message_produce($params)
    {
        if (!$params['tid']) return false;
        $api_method = ADD_TMC_MESSAGE_PRODUCE;
        $timeout = 5;
        $shop_id = $this->_shop['shop_id'];
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback_add_tmc_message_produce',
            'params' => $params,
        );
        $result = $this->_caller->call($api_method, $params, $shop_id, $timeout, $callback);
        return $result;
    }

    /**
     * callback淘宝全链路
     * @param Array $params
     */
    public function callback_add_tmc_message_produce($params)
    {
        ;
    }
    /**
     * 如果是同步请求，防超时，至少请求三次
     *
     * @return void
     * @author 
     **/
    protected function syncCall($method, $params, $shop_id, $timeout=2)
    {
        $request_limits = 3;

        $i = 1;
        do {
            
            $result = $this->_caller->call($method, $params, $shop_id, $timeout);
            if ($result->msg_id) {
                break;
            }

            $i++;
        } while ( $i <= $request_limits);

        return $result;        
    }
    /**
     +----------------------------------------------------------
     * [发货配置]获取拆单后回写发货单方式  ExBOY
     +----------------------------------------------------------
     * return   number
     +----------------------------------------------------------
     */
    public function getDeliverySeting()
    {
        $split_model    = 0;
        
        #拆单配置
        $deliveryObj    = &app::get('ome')->model('delivery');
        $split_seting   = $deliveryObj->get_delivery_seting();
        
        if($split_seting['split'] && $split_seting['split_model'])
        {
            $split_model    = intval($split_seting['split_model']);
        }
        
        return $split_model;
    }
    /**
     +----------------------------------------------------------
     * [拆单]判断订单是否进行了拆单操作  ExBOY
     +----------------------------------------------------------
     * @param   Number    $delivery_id 发货单id
     * return   Boolean
     +----------------------------------------------------------
     */
    public function check_order_is_split($delivery, $chk_oid=false)
    {
        #获取订单order_id
        $order_id    = intval($delivery['order']['order_id']);
        if(empty($order_id))
        {
            return false;
        }
        
        #获取订单关联的所有发货单id
        $sql    = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d 
                    ON(dord.delivery_id=d.delivery_id) WHERE dord.order_id='".$order_id."' AND (d.parent_id=0 OR d.is_bind='true') 
                    AND d.disabled='false' AND d.status NOT IN('failed','cancel','back','return_back')";
        $result = kernel::database()->select($sql);
        
        $dly_ids    = array();
        if($result)
        {
            foreach($result as $v)
            {
                $dly_ids[] = $v['delivery_id'];
            }
        }
        
        if(count($dly_ids) > 1)
        {
            return true;
        }
        
        #获取订单是否有未生成的发货单的商品
        $sql   = "SELECT item_id FROM sdb_ome_order_items WHERE order_id = '".$order_id."' AND nums != sendnum AND `delete` = 'false'";
        $row   = kernel::database()->selectrow($sql);
        
        if(!empty($row))
        {
            return true;
        }
        
        #拆单后_余单撤消
        $result     = $this->order_remain_cancel($order_id);
        if($result)
        {
            return true;
        }
        
        return false;
    }
    /**
     +----------------------------------------------------------
     * [余单撤消]根据拆单方式进行回写  ExBOY
     +----------------------------------------------------------
     * return   Bool
     +----------------------------------------------------------
     */
    public function order_remain_cancel($order_id)
    {
        $sql   = "SELECT process_status FROM sdb_ome_orders WHERE order_id = '".intval($order_id)."'";
        $row   = kernel::database()->selectrow($sql);
        
        return ($row['process_status'] == 'remain_cancel' ? true : false);
    }
    /**
     +----------------------------------------------------------
     * [发货单]获取成功发货的记录  ExBOY
     +----------------------------------------------------------
     *@param    $order_id   订单ID
     *@param    $out_delivery_id    排除的发货单ID
     * return   Array
     +----------------------------------------------------------
     */
    public function get_delivery_succ($order_id, $out_delivery_id = 0)
    {
        $sql    = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d 
                    ON(dord.delivery_id=d.delivery_id) WHERE dord.order_id='".intval($order_id)."' AND d.status='succ' AND d.process='true'";
        
        if($out_delivery_id)
        {
            $sql    .= " AND d.delivery_id != '".intval($out_delivery_id)."'";
        }
        
        $data   = kernel::database()->select($sql);
        
        return $data;
    }
    /**
     +----------------------------------------------------------
     * [拆单]判断"拆单方式"配置是否变更  ExBOY
     +----------------------------------------------------------
     * return   Array
     +----------------------------------------------------------
     */
    public function get_split_model_change($order_id)
    {
        $sql    = "SELECT syn_id, sync, split_model, split_type FROM sdb_ome_delivery_sync WHERE order_id = '".intval($order_id)."' AND sync='succ' ORDER BY dateline DESC";
        $row    = kernel::database()->selectrow($sql);
        
        if(empty($row) || $row['split_model'] == 0)
        {
            return '';//上次未开启拆单或无发货记录
        }
        
        #拆单配置
        $deliveryObj    = &app::get('ome')->model('delivery');
        $split_seting   = $deliveryObj->get_delivery_seting();
        
        if($row['split_model'] != $split_seting['split_model'] || $row['split_type'] != $split_seting['split_type'])
        {
            $split_seting['old_split_model']    = $row['split_model'];
            $split_seting['old_split_type']     = $row['split_type'];
            
            return $split_seting;
        }
        
        return '';
    }
    #订阅华强宝物流信息
    public  function get_hqepay_logistics($delivery = false ){
        $api_method = GET_HQEPAY_LOGISTICS;
        $delivery = $this->format_delivery($delivery);
        if ($delivery === false) return false;
    
        $params['company_code'] =  trim($delivery['dly_corp']['type']);
        $params['company_name'] = $delivery['logi_name'];
        $params['logistic_code_list'] = $delivery['logi_no'];
        $params['node_type'] =  'hqepay';
        $params['to_node_id']    = '1227722633';
    
        $shop_id = $delivery['shop_id'];
        $title = '添加物流订阅（物流单号：'.$delivery['logi_no'].'）';
        #记录物流订阅日志
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $oApi_log->gen_id();
    
        $time_out = 5;
        $res = &app::get('ome')->matrix()->set_realtime(true)->set_timeout($time_out)->call($api_method, $params);
        $params['msg_id']    = $res->msg_id;
        if($res->rsp == 'succ'){
            $api_status = 'success';
            $msg = '物流订阅成功<BR>';
        }else{
            $api_status = 'fail';
            $err_msg = $res->err_msg ? $res->err_msg : $res->res;
            $msg = '物流订阅失败('.$err_msg.')<BR>';
        }
        $oApi_log->write_log($log_id,$title,'apibusiness_router_request','rpc_request',array($api_method, $params),'','request',$api_status,$msg ,'',$api_method,$delivery['logi_no']);
        return true;
    }    
}