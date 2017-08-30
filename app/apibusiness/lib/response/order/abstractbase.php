<?php
/**
* 订单处理抽象类 
*
* @category apibusiness
* @package apibusiness/response/order/
* @author chenping<chenping@shopex.cn>
* @version $Id: abstractbase.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_abstractbase
{
    // 订单接收格式
    public $_ordersdf = array();

    // 订单标准格式
    public $_newOrder = array();

    // 响应对象
    public $_respservice = null;

    // 日志记录
    public  $_apiLog = array();

    // 创建方式（更新 OR 创建）
    protected $_operationsel = null;

    // 订单组件
    protected $_component = null;

    // 订单插件
    protected $_plugins = null; 

    // 淘管版本
    protected $_tgver = '1';

    // 版本对象
    protected $_tgverObj = null;

    // 淘管订单
    public $_tgOrder = array();

    // 店铺信息
    public $_shop = array();

    const _APP_NAME = 'ome';

    function __construct($ordersdf)
    {
        $this->_ordersdf = $ordersdf;

        // 组件集合
        $this->_components = kernel::single('apibusiness_response_order_component_broker');

        // 插件集合
        $this->_plugins = kernel::single('apibusiness_response_order_plugin_broker');
    }

    /**
     * SET 响应对象
     *  
     */
    public function setRespservice($respservice)
    {
        $this->_respservice = $respservice;

        return  $this;
    }

    /**
     * 版本号
     *
     * @return void
     * @author 
     **/
    public function setTgVer($tgver)
    {
        $this->_tgver = $tgver;
        
        try {
            // 版本对象
            if (class_exists('apibusiness_response_order_v'.$this->_tgver)) {
                $this->_tgverObj = kernel::single('apibusiness_response_order_v'.$this->_tgver);
                if (!$this->_tgverObj instanceof apibusiness_response_order_vinterface) {
                    //trigger_error("class `{$this->_tgverObj}` is not instanceof apibusiness_response_order_vinterface",E_USER_ERROR);
                    if ($this->_respservice) {
                        $this->_respservice->send_user_error("class `{$this->_tgverObj}` is not instanceof apibusiness_response_order_vinterface",array('tid'=>$this->_ordersdf['order_bn']));
                    }
                }

                $this->_tgverObj->setPlatform($this);
            }   
        } catch (Exception $e) {
            // do nothing
        }

        return $this;
    }

    /**
     * 店铺信息
     *
     * @param Array $shop 店铺信息
     * @return Object
     * @author 
     **/
    public function setShop($shop)
    {
        $this->_shop = $shop;

        return $this;
    }

    /**
     * 异常处理
     *
     * @param String $fun 调用方法
     * @return void
     * @author 
     **/
    public function exception($fun)
    {
        $api_name = ($fun == 'payment_update') ? 'api.store.trade.payment' : 'api.store.trade';

        $logModel = app::get('ome')->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,
                             $this->_apiLog['title'],
                             get_class($this), 
                             $fun, 
                             '', 
                             '', 
                             'response', 
                             'fail', 
                             implode('<hr/>',$this->_apiLog['info']),
                             '',
                             $api_name,
                             $this->_ordersdf['order_bn']);

        $data = array('tid' => $this->_ordersdf['order_bn']);
        //$this->_respservice->send_user_error($this->_apiLog['info']['msg'],$data);
        $this->_respservice->send_user_success($this->_apiLog['info']['msg'],$data);

        exit;
    }

    /**
     * 正常退出
     *
     * @return void
     * @author 
     **/
    public function shutdown($fun)
    {
        $api_name = ($fun == 'payment_update') ? 'api.store.trade.payment' : 'api.store.trade';

        $logModel = app::get('ome')->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,
                             $this->_apiLog['title'],
                             get_class($this), 
                             $fun, 
                             '', 
                             '', 
                             'response', 
                             'success', 
                             implode('<hr/>',$this->_apiLog['info']),
                             '',
                             $api_name,
                             $this->_ordersdf['order_bn']);

        $data = array('tid' => $this->_ordersdf['order_bn']);
        $this->_respservice->send_user_success($this->_apiLog['info']['msg'],$data);

        exit;
    }
    
    /**
     * 解析订单解构
     *
     * @return void
     * @author 
     **/
    protected function analysis()
    {
        // 配送信息
        if(is_string($this->_ordersdf['shipping']))
        $this->_ordersdf['shipping'] = json_decode($this->_ordersdf['shipping'],true);

        // 支付信息
        if(is_string($this->_ordersdf['payinfo']))
        $this->_ordersdf['payinfo'] = json_decode($this->_ordersdf['payinfo'],true);

        // 收货人信息
        if(is_string($this->_ordersdf['consignee']))
        $this->_ordersdf['consignee'] = json_decode($this->_ordersdf['consignee'],true);

        // 发货人信息
        if (is_string($this->_ordersdf['consigner'])) 
        $this->_ordersdf['consigner'] = json_decode($this->_ordersdf['consigner'],true);

        // 代销人信息
        if(is_string($this->_ordersdf['selling_agent']))
        $this->_ordersdf['selling_agent'] = json_decode($this->_ordersdf['selling_agent'],true);

        // 买家会员信息
        if(is_string($this->_ordersdf['member_info']))
        $this->_ordersdf['member_info'] = json_decode($this->_ordersdf['member_info'],true);

        // 订单优惠方案
        if(is_string($this->_ordersdf['pmt_detail'])){
            $this->_ordersdf['pmt_detail'] = json_decode($this->_ordersdf['pmt_detail'],true);
        }

        // 订单商品
        if(is_string($this->_ordersdf['order_objects']))
        $this->_ordersdf['order_objects'] = json_decode($this->_ordersdf['order_objects'],true);

        // 支付单(兼容老版本)
        if(is_string($this->_ordersdf['payment_detail']))
        $this->_ordersdf['payment_detail'] = json_decode($this->_ordersdf['payment_detail'],true);
        
        if(is_string($this->_ordersdf['payments']))
        $this->_ordersdf['payments'] = $this->_ordersdf['payments'] ? json_decode($this->_ordersdf['payments'],true) : array();

        // 版本的扩展解析
        if ($this->_tgverObj) 
        $this->_tgverObj->analysis($this->_ordersdf);
        

        // 去首尾空格
        self::trim($this->_ordersdf);
    }

    /**
     * 去首尾空格
     *
     * @param Array
     * @return Array
     * @author 
     **/
    static function trim(&$arr)
    {        
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                self::trim($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }
        }
    }

    /**
     * 获取格式转换组件
     *
     * @return void
     * @author 
     **/
    protected function get_convert_components()
    {
        $component = array('master','items','shipping','consignee','consigner','custommemo','markmemo','marktype');

        return $component;
    }

    /**
     * 订单转换
     *
     * @return void
     * @author 
     **/
    protected function component_convert()
    {
        $this->_components->clearComponents();

        foreach ($this->get_convert_components() as $component) {
            $this->_components->registerComponent($component);
        }

        $this->_components->setPlatform($this)->convert();
    }

    /**
     * 订单操作：创建 OR 更新
     *
     * @return void
     * @author 
     **/
    protected function operationSel()
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $this->_tgOrder = $orderModel->dump(array('order_bn'=>$this->_ordersdf['order_bn'],'shop_id'=>$this->_shop['shop_id']),'*',array('order_objects'=>array('*',array('order_items'=>array('*')))));
        $lastmodify = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);
        if (empty($this->_tgOrder)) {
            $this->_operationsel = 'create';
        }  elseif ($lastmodify > $this->_tgOrder['outer_lastmodify']) {
            // 防并发操作
            $orderModel->update(array('outer_lastmodify'=>$lastmodify),array('order_id'=>$this->_tgOrder['order_id'],'outer_lastmodify|lthan'=>$lastmodify));
            $affect_row = $orderModel->db->affect_row();

            // 如果影响的条数大于0 做更新
            if ($affect_row > 0) {
                $this->_operationsel = 'update';
            }
        } elseif (($this->_tgOrder['shop_type'] == 'alibaba') && ($lastmodify==$this->_tgOrder['outer_lastmodify']) && empty($this->_tgOrder['consignee']['area'])) {
            $this->_operationsel = 'update';
        }
    }

    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    protected function canAccept()
    {
        // 记录接收数据
        $this->_apiLog = array(
            'title' => '订单创建接口[订单：'. $this->_ordersdf['order_bn'] .']',
            'info' => array(
                '接收参数：' . var_export($this->_ordersdf, true),
                '店铺信息：' . var_export($this->_shop,true),
                '淘管接口版本：v'.$this->_tgver,
            ),
        );
        
        if (empty($this->_ordersdf) || empty($this->_ordersdf['order_bn']) || empty($this->_ordersdf['order_objects'])) {
            $this->_apiLog['info']['msg'] = '接收数据不完整';
            return false;
        }

        // 确认是否已归档
       //查看是否归档里存在否则不创建
        $is_archive = $this->is_archive();

        if ($is_archive===false) {
            $this->_apiLog['info']['msg'] = '已归档订单不接收!';
            return false;
        }

        //
        // 针对南极人的处理        

        // 是否接收物流宝发货的订单
        $wuliubao = app::get('ome')->getConf('ome.delivery.wuliubao');
        if ($wuliubao == 'false' && strtolower($this->_ordersdf['is_force_wlb']) == 'true') {
            $this->_apiLog['info']['msg'] = '物流宝发货订单不接收';
            return false;
        }

        $result = $this->accept_dead_order();
        if ($result === false) {
            return false;
        }

        // omeapiplugin
        if ($this->_ordersdf['order_from'] == 'omeapiplugin') {
            $this->_apiLog['info']['msg'] = '来自omeapiplugin的订单不接收';
            return false;
        }

        return true;
    }

    /**
     * 是否接收(除活动订单外的其他订单)
     *
     * @return void
     * @author 
     **/
    protected function accept_dead_order()
    {
        if ($this->_ordersdf['status'] != 'active') {
            if ($this->_ordersdf['status'] == 'close') {
                $this->_apiLog['info']['msg'] = '取消的订单不接收';
            } else {
                $this->_apiLog['info']['msg'] = '完成的订单不接收';
            }

            return false;
        }

        return true;
    }

    /**
     * 临时增加收订统计监控
     *
     * @return void
     * @author 
     **/
    protected function monitor()
    {
        //if(defined('MONITOR_ORDER_ACCEPT') && MONITOR_ORDER_ACCEPT){
            //$redis = new Redis();
            //$redis->connect(MONITOR_REDIS_HOST, MONITOR_REDIS_PORT);
            //$redis->incr('taoguan:apiorder.recv_succ');
            //$redis->zIncrBy('taoguan:apiorder:host_list.'.date('Ymd'),1,$_SERVER['SERVER_NAME']);
        //}
    }

    /**
     * 对平台接收的数据纠正(有些是前端打的不对的)
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        // 如果是担保交易,订单支付状态修复成已支付
        if ($this->_ordersdf['pay_status'] == '2') {
            $this->_ordersdf['pay_status'] = '1';
        }

        $this->_ordersdf['shop_id']   = $this->_shop['shop_id'];
        $this->_ordersdf['shop_type'] = $this->_shop['shop_type'];

        // 如果是货到付款的，重置支付金额，支付单
        if ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_ordersdf['payed'] = '0';
            $this->_ordersdf['payments'] = array();
            $this->_ordersdf['payment_detail'] = array();    
        }
    }

    /**
     * 订单新增、更新入口
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        // 订单监控
        $this->monitor();

        // 数据解析
        $this->analysis();

        $result = $this->canAccept();
        if ($result === false) {
            $this->exception(__METHOD__); return;
        }

        $this->reTransSdf();

        $this->operationSel();
        
        switch ($this->_operationsel) {
            case 'create':
                $this->_apiLog['title'] = '订单创建接口[订单：' . $this->_ordersdf['order_bn'] . ']';
                $this->createOrder();
                break;
            case 'update':
                $this->_apiLog['title'] = '订单更新接口[订单：' . $this->_ordersdf['order_bn'] . ']';
                $this->updateOrder();
                break;
            default:
                $this->_apiLog['title'] = '订单更新接口[订单：' . $this->_ordersdf['order_bn'] . ']';
                $this->_apiLog['info'][] = '更新时间没变，订单不需要更新！';
                break;
        }
    }

    /**
     * 能够创建订单
     *
     * @return void
     * @author 
     **/
    public function canCreate()
    {
        if ($this->_ordersdf['ship_status'] != '0') {
            $this->_apiLog['info']['msg'] = '已发货订单不接收';
            return false;
        }        
        return true;
    }

    /**
     * 获取插件
     *
     * @return void
     * @author 
     **/
    public function get_create_plugins()
    {
        $plugins = array('member','payment','promotion','cod', 'tax');

        return $plugins;
    }

    /**
     * 注册插件
     *
     * @return void
     * @author 
     **/
    protected function registerPluginForCreate()
    {
        $this->_plugins->clearPlugins()->setPlatform($this);
        foreach ($this->get_create_plugins() as $key => $value) {
            $this->_plugins->registerPlugin($value);
        }
    }

    /**
     * 订单创建处理前的
     *
     * @return void
     * @author 
     **/
    public function preCreate()
    {
        $this->_plugins->preCreate();
    }

    /**
     * 保存订单后操作
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        $this->_plugins->postCreate();
    }

    /**
     * 创建订单
     *
     * @return void
     * @author 
     **/
    protected function createOrder()
    {
        // 允许创建
        $result = $this->canCreate();
        if ($result === false) {
            $this->exception('add'); return;
        }

        // 订单处理前
        if($service = kernel::servicelist('service.order')){
            foreach ($service as $instance){
                if (method_exists($instance, 'pre_add_order')){
                    $instance->pre_add_order($this->_ordersdf);
                }
            }
        }

        // 组件处理
        $this->component_convert();

        $this->registerPluginForCreate();

        $this->preCreate();

        $this->_apiLog['info'][] = '淘管标准$sdf结构：'.var_export($this->_newOrder,true);

        // 事务
        kernel::database()->beginTransaction();

        $rs = app::get(self::_APP_NAME)->model('orders')->create_order($this->_newOrder);
        if (!$rs) {
            // 事务回写
            kernel::database()->rollBack();

            $this->_apiLog['info'][] = '保存失败：'.kernel::database()->errorinfo();
            $this->exception(__METHOD__);
        }

        # 更新订单下载时间
        $shopModel = app::get(self::_APP_NAME)->model('shop');
        $shopModel->update(array('last_download_time'=>time()), array('shop_id'=>$this->_shop['shop_id']));

        // 提交事务
        kernel::database()->commit();

        $this->postCreate();

        $this->_apiLog['info'][] = '返回值：订单创建成功！订单ID：'.$this->_newOrder['order_id'];

        if($service = kernel::servicelist('service.order')){
            foreach ($service as $instance){
                if (method_exists($instance, 'after_add_order')){
                    $instance->after_add_order($this->_newOrder);
                }
            }
        }
    }

    /**
     * 组件更新
     *
     * @return void
     * @author 
     **/
    public function component_update()
    {
        $this->_components->clearComponents();

        foreach ($this->get_update_components() as $component) {
            $this->_components->registerComponent($component);
        }
        $this->_components->setPlatform($this)->update();
    }

    /**
     * 需更新的组件
     *
     * @return void
     * @author 
     **/
    protected function get_update_components()
    {
        $component = array('master','items','shipping','consignee','custommemo','markmemo','marktype');

        return $component;
    }

    /**
     * 允许更新
     *
     * @return void
     * @author 
     **/
    protected function canUpdate()
    {
        // 已发货的订单不再变更
        if ($this->_tgOrder['ship_status'] == '1' && $this->_tgOrder['status'] == 'finish' && $this->_tgOrder['archive'] == '1') {
            $this->_apiLog['info']['msg'] = '已发货的订单，不再更新订单';
            return false;
        }

        return true;
    }

    /**
     * 更新订单前的操作
     *
     * @return void
     * @author 
     **/
    protected function preUpdate()
    {
        $this->_plugins->preUpdate();
    }

    /**
     * 更新完成后操作
     *
     * @return void
     * @author 
     **/
    protected function postUpdate()
    {
        $this->_plugins->postUpdate();
    }

    /**
     * 获取更新信息插件
     *
     * @return void
     * @author 
     **/
    public function get_update_plugins()
    {
        $plugins = array();

        return $plugins;
    }

    /**
     * 注册更新插件
     *
     * @return void
     * @author 
     **/
    protected function registerPluginForUpdate()
    {
        $this->_plugins->clearPlugins()->setPlatform($this);
        foreach ($this->get_update_plugins() as $key => $value) {
            $this->_plugins->registerPlugin($value);
        }
    }

    /**
     * 更新订单
     *
     * @return void
     * @author 
     **/
    protected function updateOrder()
    {
        $result = $this->canUpdate();
        if ($result === false) {
            $this->exception(__METHOD__); return;
        }

        $this->registerPluginForUpdate();
        
        kernel::database()->beginTransaction();

        $this->component_update();

        $this->preUpdate();

        if ($this->_newOrder) {
            $this->_newOrder['order_id'] = $this->_tgOrder['order_id'];
            $this->_newOrder['outer_lastmodify'] = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);

            $rs = app::get(self::_APP_NAME)->model('orders')->save($this->_newOrder);
            $this->_apiLog['info'][] = '更新订单SDF结构：'.var_export($this->_newOrder,true);

            if (!$rs) {
                kernel::database()->rollBack();

                $this->_apiLog['info'][] = '更新失败：'.kernel::database()->errorinfo();
                $this->exception(__METHOD__); 
            }
        }

        // 提交事务
        kernel::database()->commit();

        $this->postUpdate();


        $orderPauseAllow = app::get(self::_APP_NAME)->getConf('ome.orderpause.to.syncmarktext');
        // 备注发生变更，拆分订单暂停
        if ($this->_newOrder['mark_text'] && $this->_tgOrder['process_status'] == 'splited' && $orderPauseAllow !== 'false') {
            app::get(self::_APP_NAME)->model('orders')->pauseOrder($this->_tgOrder['order_id']);
        }

        if ($this->_newOrder) {
            $this->_apiLog['info'][] = '返回值：订单更新成功';
        } else {
            $this->_apiLog['info'][] = '返回值：订单无数据变化，不需要更新';
        }
    }

    /**
     * 更新订单状态
     *
     * @return Array
     * @author 
     **/
    public function status_update()
    {
       if (is_object($this->_tgverObj)) {
            $this->_apiLog['title']  = '更新订单状态接口['. $this->_ordersdf['order_bn'] .']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;
            $this->_tgverObj->status_update();
       }
    }

    /**
     * 更新支付状态
     *
     * @return Array
     * @author 
     **/
    public function pay_status_update()
    {
       if (is_object($this->_tgverObj)) {
            $this->_apiLog['title']  = '更新订单支付状态接口['. $this->_ordersdf['order_bn'] .']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;

            $this->_tgverObj->pay_status_update();
       }
    }

    /**
     * 更新订单发货状态
     *
     * @return Array
     * @author 
     **/
    public function ship_status_update()
    {
       if (is_object($this->_tgverObj)) {
            $this->_apiLog['title']  = '更新订单发货状态接口['. $this->_ordersdf['order_bn'] .']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;

            $this->_tgverObj->ship_status_update();
       }
    }

    /**
     * 添加买家留言
     *
     * @return Array
     * @author 
     **/
    public function custom_mark_add()
    {
       if (is_object($this->_tgverObj)) {
            $this->_ordersdf['order_bn'] = $this->_ordersdf['order_bn'] ? $this->_ordersdf['order_bn'] : $this->_ordersdf['tid'];
            $this->_apiLog['title']  = '添加买家留言接口['. $this->_ordersdf['order_bn'] .']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;           

            $this->_tgverObj->custom_mark_add();
       }
    }

    /**
     * 更新买家留言
     *
     * @return Array
     * @author 
     **/
    public function custom_mark_update()
    {
       if (is_object($this->_tgverObj)) {
            $this->_ordersdf['order_bn'] = $this->_ordersdf['order_bn'] ? $this->_ordersdf['order_bn'] : $this->_ordersdf['tid'];
            $this->_apiLog['title']  = '更新买家留言接口['. $this->_ordersdf['order_bn'] .']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver; 

            $this->_tgverObj->custom_mark_update();
       }
    }

    /**
     * 添加订单备注
     *
     * @return Array
     * @author 
     **/
    public function memo_add()
    {
       if (is_object($this->_tgverObj)) {
            $this->_ordersdf['order_bn'] = $this->_ordersdf['order_bn'] ? $this->_ordersdf['order_bn'] : $this->_ordersdf['tid'];
            $this->_apiLog['title']  = '添加订单备注接口['. $this->_ordersdf['order_bn'] .']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver; 
                       
            $this->_tgverObj->memo_add();
       }
    }

    /**
     * 更新订单备注
     *
     * @return Array
     * @author 
     **/
    public function memo_update()
    {
       if (is_object($this->_tgverObj)) {
            $this->_ordersdf['order_bn'] = $this->_ordersdf['order_bn'] ? $this->_ordersdf['order_bn'] : $this->_ordersdf['tid'];
            $this->_apiLog['title']  = '更新订单备注接口['. $this->_ordersdf['order_bn'] .']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver; 

            $this->_tgverObj->memo_update();
       }
    }

    /**
     * 更新订单支付方式
     *
     * @return Array
     * @author 
     **/
    public function payment_update()
    {
       if (is_object($this->_tgverObj)) {
            $this->_apiLog['title']  = '更新支付单状态接口[' . $this->_ordersdf['order_bn'] . ']';
            $this->_apiLog['info'][] = '接收参数$SDF：' . var_export($this->_ordersdf, true);
            $this->_apiLog['info'][] = '店铺信息：' . var_export($this->_shop, true);
            $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver; 
            
            $this->_tgverObj->payment_update();
       }
    }

     public function is_archive()
    {
        $arciveModel = app::get('archive')->model('orders');
        $archive = $arciveModel->dump(array('order_bn'=>$this->_ordersdf['order_bn'],'shop_id'=>$this->_shop['shop_id']),'*');
        
        if ($archive) {
            return false;
        }
        return true;
    }
}