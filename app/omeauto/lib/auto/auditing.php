<?php

/**
 * 系统自动审单
 *
 * @author ExBOY
 * @version 0.1b
 */
ini_set('memory_limit', '256M');

define('__STATUS_ON_PAY', 1);
define('__STATUS_MEMO', 2);

class omeauto_auto_auditing {
    /**
     * 订单模块APP名
     * @var String
     */

    const __ORDER_APP = 'ome';

    /**
     * 配置参数
     * @var Array
     */
    static $cnf = null;

    /**
     * 已支付的状态列表
     * @var Array
     */
    static $_PAY_STATUS = array('1', '4');

    /**
     * 插件列表
     * @var Array
     */
    static $_plugObjects = array();

    /**
     * 订单数据缓存
     * @var Array
     */
    private $_group = array();

    /**
     * 插件组
     */
    private $_plugins ;

    public function __construct()
    {
        $this->_plugins = array('pay', 'flag', 'logi', 'branch', 'store', 'abnormal', 'oversold', 'tbgift', 'crm', 'tax');
        
        if (self::getCnf('chkProduct') == 'Y') {

            $this->_plugins[] = 'product';
        }
      
    }

    /**
     * 订单合并处理
     *
     * @param Array $group 订单组
     * @return Mixed
     * @author hzjsq (2011/3/28)
     */
    public function process($group) {
        if (!is_array($group) || empty($group)) {

            return null;
        }

        //初始化订单组结构
        $this->_instanceItemObject($group);
        //获取审单规则用到的所有订单分组
        $orderFilters = $this->_getAutoOrderFilter();

        foreach ($this->_group as $key => $order) {
            foreach ($orderFilters as $filter) {
                if ($filter->vaild($order)) {
                    //加入订单
                    $filter->addItem($order);
                    break;
                }
            }
        }

        //按发组类型开始审单
        $result = array('total' => 0, 'succ' => 0, 'fail' => 0);
        foreach ($orderFilters as $orderGroup) {

            $ret = $orderGroup->process();

            $result['total'] += $ret['total'];
            $result['succ'] += $ret['succ'];
            $result['fail'] += $ret['fail'];
        }
        return $result;
    }

    /**
     * 订单分派处理
     *
     * @param Array $group 订单组
     * @return Mixed
     * @author hzjsq (2011/3/28)
     */
    public function dispatch($group) {
        if (!is_array($group) || empty($group)) {
            return null;
        }

        //初始化订单组结构
        $this->_instanceItemObject($group);

        //获取审单规则用到的所有订单分组
        $orderFilters = $this->_getAutoOrderFilter();

        foreach ($this->_group as $key => $order) {
            foreach ($orderFilters as $filter) {
                if ($filter->vaild($order)) {
                    return $filter->getConfig();
                    break;
                }
            }
        }

        return '';
    }

    /**
     * 获取所有可用的审单相关订单分组
     *
     * @param void
     * @return mixed
     */
    private function _getAutoOrderFilter() {

        $types = kernel::single('omeauto_auto_type')->getAutoOrderTypes();
        //设置已配置分组
        $filters = array();
        foreach ((array) $types as $type) {

            $filter = new omeauto_auto_audgroup();
            $filter->setConfig($type);
            $filters[] = $filter;
        }
        //增加缺省订单分组

        $filter = new omeauto_auto_audgroup();
        $filter->setDefault();
        $filters[] = $filter;
        //返回订单组
        return $filters;
    }

    /**
     * 生成订单结构
     *
     * @param Array $group
     * @retun void
     */
    private function _instanceItemObject($group) {

        //准备数据
        $ids = $this->_mergeGroup($group);
        $rows = app::get(self::__ORDER_APP)->model('orders')->getList('*', array('order_id' => $ids,'process_status'=>array('unconfirmed','confirmed','splitting','remain_cancel')));
        
        if (!$rows) return;

        foreach ($rows as $order) {

            $orders[$order['order_id']] = $order;
        }
        $ids = array_keys($orders);

        $items = app::get(self::__ORDER_APP)->model('order_items')->getList('*', array('order_id' => $ids, 'delete' => 'false'));
        foreach ($items as $item) {

            $orders[$item['order_id']]['items'][$item['item_id']] = $item;
        }

        $objects = app::get(self::__ORDER_APP)->model('order_objects')->getList('*', array('order_id' => $ids));
        foreach ($objects as $object) {
            $orders[$object['order_id']]['objects'][$object['obj_id']] = $object;
        }
        
        #过滤掉没有明细的订单 ExBOY
        foreach ($orders as $order_id => $order)
        {
            if (empty($order['objects']) || empty($order['items']))
            {
                unset($orders[$order_id]);
            }
        }
        if(empty($orders))
        {
            return ;
        }
        
        //生成对像
        foreach ($group as $item) {

            $gOrder = array();
            foreach ($item['orders'] as $orderId) {
                $gOrder[$orderId] = $orders[$orderId];
            }
            $this->_group[$item['hash']] = new omeauto_auto_group_item($gOrder);
        }

        unset($rows);
        unset($order);
    }

    /**
     * 得到订单组结构
     *
     * @param Array $group
     * @retun void
     */
    public function getItemObject($group) {
        $this->_instanceItemObject($group);
        return $this->_group;
    }

    /**
     * 获取所有订单ID
     *
     * @param Array $group 要处理的订单组结构
     * @return Array
     */
    private function _mergeGroup($group) {

        $ids = array();
        foreach ($group as $item) {

            $ids = array_merge($ids, $item['orders']);
        }

        return $ids;
    }

    /**
     * 通过插件名获取插件类并返回
     *
     * @param String $plugName 插件名
     * @return Object
     */
    private function & _instancePlugin($plugName) {

        $fullPluginName = sprintf('omeauto_auto_plugin_%s', $plugName);
        //echo $fullPluginName.'<br>';
        $fix = md5(strtolower($fullPluginName));

        if (!isset(self::$_plugObjects[$fix])) {

            $obj = new $fullPluginName();
            if ($obj instanceof omeauto_auto_plugin_interface) {

                self::$_plugObjects[$fix] = $obj;
            }
        }
        return self::$_plugObjects[$fix];
    }

    /**
     * 获取配置中的指定变量名
     *
     * @param String $name 参数名
     * @return Mixed
     */
    static public function getCnf($name) {

        if (empty(self::$cnf)) {

            self::$cnf = kernel::single('omeauto_config_setting')->getAutoCnf();
        }

        if (isset(self::$cnf[$name])) {

            return self::$cnf[$name];
        } else {

            return '';
        }
    }

    /**
     * 获取缓存时间
     *
     * @param void
     * @return integer
     */
    private function _getBufferTime() {

        return time() - self::getCnf('bufferTime') * 60;
    }

    /**
     * 获取所有可操作的订单组
     *
     * @param Integer $bufferTime 缓冲时间
     * @return Array
     */
    public function getBufferGroup($filter = array()) {
        $bufferTime = $this->_getBufferTime();

        //区分分销类型，生成不同的HASH。生成一下直销订单 hash
        kernel::database()->exec("UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod)) WHERE op_id IS NULL AND group_id IS NULL AND ((shop_type<>'shopex_b2b' AND shop_type<>'dangdang' AND shop_type<>'taobao' AND shop_type<>'amazon') or shop_type is null)");

        //当当订单如果是货到付款不合并
        kernel::database()->exec("UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',IF(is_cod='true',order_id,is_cod),'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type)) WHERE op_id IS NULL AND group_id IS NULL AND shop_type='dangdang'");
        //亚马逊如果是非自发货订单不合并
        kernel::database()->exec("UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',IF(self_delivery='false',order_id,self_delivery),'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type)) WHERE op_id IS NULL AND group_id IS NULL AND shop_type='amazon'");
       //淘宝代销订单不合并
        kernel::database()->exec("UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',IF(order_source='tbdx',order_id,order_source),'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type)) WHERE op_id IS NULL AND group_id IS NULL AND shop_type='taobao'");

        //生成一下分销订单 hash
        kernel::database()->exec("UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type)), order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod,'-',ship_tel,'-',shop_type)) WHERE op_id IS NULL AND group_id IS NULL AND shop_type='shopex_b2b'");

        $bufferFilter = $this->_getBufferFilter();
        if($filter['shop_id'] && $filter['shop_id'] != 'all'){
            $bufferFilter['shop_id'] = $filter['shop_id'];
        }

        //获取所有可处理订单
        $this->bufferOrder = app::get(self::__ORDER_APP)->model('orders')->getList('order_id, order_combine_hash, order_combine_idx, pay_status, is_cod, createtime, paytime', $bufferFilter, 0, 1500, 'createtime ASC');

        $orderGroup = array();
        if ($this->bufferOrder) {
            //整合数据, 合成订单组
            foreach ($this->bufferOrder as $key => $row) {
                $idx = sprintf('%s||%s', $row['order_combine_hash'], $row['order_combine_idx']);
                $orderGroup[$idx]['orders'][$key] = $row['order_id'];
                $orderGroup[$idx]['cnt'] += 1;
            }
            //去除无效数据
            foreach ($orderGroup as $key => $group) {
                if ($this->vaildBufferGroup($group['orders'], $bufferTime)) {
                    $orderGroup[$key]['orders'] = join(',', $group['orders']);
                } else {
                    unset($orderGroup[$key]);
                }
            }
        }
        return $orderGroup;
    }

    /**
     * 根据订单ID返回与buffer group相同的订单数据结构
     * @param 订单ID $orders
     * @return 订单信息  array
     */
    public function getOrderGroup($ids) {
        $orders = app::get(self::__ORDER_APP)->model('orders')->getList('order_id, order_combine_hash, order_combine_idx, pay_status, is_cod, createtime, createtime as paytime', array('order_id' => $ids));

        $orderGroup = array();
        if ($orders) {
            //整合数据, 合成订单组
            foreach ($orders as $key => $row) {
                $idx = sprintf('%s||%s', $row['order_combine_hash'], $row['order_combine_idx']);
                $orderGroup[$idx]['orders'][$key] = $row['order_id'];
                $orderGroup[$idx]['cnt'] += 1;
            }

            //去除无效数据
            foreach ($orderGroup as $key => $group) {
                $orderGroup[$key]['orders'] = join(',', $group['orders']);
            }
        }
        return $orderGroup;
    }

    /**
     * 检查订单组是否有效
     *
     * @param Array $orders 订单组
     * @param Integer $bufferTime 缓存时间
     * @return Boolean
     */
    private function vaildBufferGroup($orders, $bufferTime) {

        $gOrder = array();
        foreach ($orders as $idx => $ordersId) {
            $gOrder[$ordersId] = $this->bufferOrder[$idx];
        }

        $gObj = new omeauto_auto_group_item($gOrder);

        return $gObj->vaildBufferGroup($bufferTime);
    }

    /**
     * 获取缓冲池中订单的过滤条件
     *
     * @author hzjsq (2011/3/24)
     * @param void
     * @return Array
     */
    private function _getBufferFilter() {

        //if (self::getCnf('chkNoPayOrder') == 'Y') {
        //缺省获取所有订单
        return array('order_confirm_filter' => '(op_id IS NULL AND group_id IS NULL AND ((is_cod=\'true\' and pay_status=\'0\') or pay_status in (\'1\',\'4\')))', 'status' => 'active', 'ship_status' => '0', 'f_ship_status' => '0', 'confirm' => 'N', 'abnormal' => 'false', 'refund_status' => 0, 'is_auto' => 'false', 'is_fail' => 'false');
        //} else {
        //
        //    return array('assigned' => 'notassigned', 'status' => 'active', 'ship_status' => '0', 'f_ship_status' => '0', 'confirm' => 'N', 'abnormal' => 'false', 'refund_status' => 0,
        //        'order_confirm_filter' => "(pay_status in ('1','4') OR is_cod='true')", 'is_auto' => 'false', 'is_fail' => 'false');
        //}
    }

    /**
     * 通过输入的错误标志显示获取对应的错误信息
     *
     * @param Integer $status 错误标志
     * @prams Array $order 订单信息
     * @return Array
     */
    public function fetchAlertMsg($staus, $order) {

        if ($staus == 0) {

            return array();
        }
        $result = array();
        foreach ($this->_plugins as $plug) {

            $obj = $this->_instancePlugin($plug);

            if (is_object($obj)) {

                $_msg = $obj->getMsgFlag();
                if (($staus & $_msg) > 0) {
                    $result[] = $obj->getAlertMsg($order);
                }
            }
        }

        $mResult = array();
        $mark = kernel::single('omeauto_auto_group_mark');
        $mResult = $mark->fetchAlertMsg($staus, $order);
        $result = array_merge($result, $mResult);

        return $result;
    }

    /**
     * 获取各种状态的标志位及对应信息
     *
     * @param Void
     * @return Array
     */
    public function getErrorFlags() {

        $result = array();
        foreach ($this->_plugins as $plug) {

            $obj = $this->_instancePlugin($plug);
            if (is_object($obj)) {

                $_msg = $obj->getMsgFlag();
                $result[$_msg] = $obj->getTitle();
            }
        }

        return $result;
    }

    /**
     * 转换订单格式
     *
     * @param Array $o订单数组
     * @return Array
     */
    private function convertOrderFormat($o) {

        //数据格式转换
        $difftime = kernel::single('ome_func')->toTimeDiff(time(), $o['createtime']);
        $o['difftime'] = $difftime['d'] . '天' . $difftime['h'] . '小时' . $difftime['m'] . '分';
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
        if($markShowMethod == 'all'){
            $o['mark_text'] = $this->_formatMemo(unserialize($o['mark_text']));
            $o['custom_mark'] = $this->_formatMemo(unserialize($o['custom_mark']));
        }else{
            $mark_text = array_pop(unserialize($o['mark_text']));
            $custom_mark = array_pop(unserialize($o['custom_mark']));
            $o['mark_text'] = $mark_text['op_content'];
            $o['custom_mark'] = $custom_mark['op_content'];
        }

        //淘宝订单是否优惠赠品
        if($o['shop_type'] == 'taobao' && $o['abnormal_status'] >0 && ( ($o['abnormal_status'] & ome_preprocess_const::__HASGIFT_CODE) == ome_preprocess_const::__HASGIFT_CODE)){
            $tbgiftOrderItemsObj = &app::get('ome')->model('tbgift_order_items');
            $tmp_tbgifts = $tbgiftOrderItemsObj->getList('*',array('order_id'=>$o['order_id']),0,-1);
            $o['tbgifts'] = $tmp_tbgifts;
            $o['has_tbgifts'] = 1;
        }

        $o['items'] = app::get(self::__ORDER_APP)->model('orders')->getItemBranchStore($o['order_id']);
        //地区数据转换
        $consignee['area'] = $o['ship_area'];
        $consignee['addr'] = $o['ship_addr'];
        $consignee['name'] = $o['ship_name'];
        $consignee['mobile'] = $o['ship_mobile'];
        $consignee['hash'] = md5(join('-', $consignee));
        $consignee['telephone'] = $o['ship_tel'];
        $consignee['r_time'] = $o['r_time'];
        $consignee['email'] = $o['ship_email'];
        $consignee['zip'] = $o['ship_zip'];
        $o['consignee'] = $consignee;
        //读取店铺名称
        $shop = app::get(self::__ORDER_APP)->model('shop')->getList('name',array('shop_id'=>$o['shop_id']),0,1);
        $o['shop_name'] = $shop[0]['name'];
        if($o['member_id']){
            $member = app::get(self::__ORDER_APP)->model('members')->getList('uname',array('member_id'=>$o['member_id']),0,1);
            $o['member_name'] = $member[0]['uname'];
        }else{
            $o['member_name'] = '无用户';
        }

        $productModel    = app::get('ome')->model('products');
        $pkgGoodsModel   = app::get('omepkg')->model('pkg_goods');
        $pkgProductModel = app::get('omepkg')->model('pkg_product');

        //转换数据  addon
        foreach ($o['items'] as $type => $item) {

            foreach ($item as $objId => $object) {

                foreach ($object['order_items'] as $pid => $product) {

                    $o['items'][$type][$objId]['order_items'][$pid]['bn'] = preg_replace('/^:::/is', '', $o['items'][$type][$objId]['order_items'][$pid]['bn']);
                    $o['items'][$type][$objId]['order_items'][$pid]['max_left_nums'] = $product['left_nums'];

                    if ($o['items'][$type][$objId]['order_items'][$pid]['bn'] == '___') {
                        $o['items'][$type][$objId]['order_items'][$pid]['bn'] = '-';
                    }

                    if (empty($product['addon'])) {
                        $o['items'][$type][$objId]['order_items'][$pid]['addon'] = '-';
                    } else {
                        $spec = '';
                        $tmp = unserialize($product['addon']);
                        foreach ($tmp['product_attr'] as $value) {
                            $spec .= sprintf("%s：%s", $value['label'], $value['value']);
                        }
                        $o['items'][$type][$objId]['order_items'][$pid]['addon'] = $spec;
                    }

                    $pinfo = $productModel->dump($product['product_id'],'weight');
                    $o['items'][$type][$objId]['order_items'][$pid]['weight'] = $pinfo['weight'];

                }

                if ($object['obj_type'] == 'pkg') {
                    $pkgGood = $pkgGoodsModel->dump(array('pkg_bn' => $object['bn']),'goods_id,weight');
                    if ($pkgGood) {
                        $o['items'][$type][$objId]['weight'] = $pkgGood['weight'];
                    }

                    $o['items'][$type][$objId]['max_left_nums'] = $object['left_nums'];
                }
            }
        }

        return $o;
    }

 /**
  * 获取订单无用户信息情况下的过渡条件
  *
  * @param Array $order
  * @return Array
  */
     function _getNullMemberFilter($order) {
        $filter = array();
        $memberidconf = intval(app::get('ome')->getConf('ome.combine.memberidconf'));
        $memberidconf = $memberidconf=='1' ? '1' : '0';
        if ($memberidconf == '0') {
            $filter['order_id'] = $order['order_id'];
        }else{
            $filter = $this->_getAddrFilter($order);
        }
        return $filter;
    }

 /**
  * 获取地址一致的过渡条件
  *
  * @param Array $order
  * @return Array
  */
      function _getAddrFilter($order) {
        $filter = array();
        $combine_conf = app::get('ome')->getConf('ome.combine.addressconf');
        $ship_address = intval($combine_conf['ship_address'])=='1' ? '1' : '0' ;
        $mobile = intval($combine_conf['mobile'])=='1' ? '1' : '0' ;

        if ($ship_address == '0') {
            $filter['ship_name'] = $order['ship_name'];

            $filter['ship_area'] = $order['ship_area'];
            $filter['ship_addr'] = $order['ship_addr'];
        }
        if ($mobile == '0') {
            if(!empty($order['ship_mobile'])){
                $filter['ship_mobile'] = $order['ship_mobile'];
            }

            if(empty($filter)){
                $filter['order_id'] = $order['order_id'];
            }
        }

        return $filter;
     }

     private function _getCombineConf(&$combine_member_id, &$combine_shop_id){

        if (strval(app::get('ome')->getConf('ome.combine.member_id')) == '0') {

            $combine_member_id = false;
        }
        if (strval(app::get('ome')->getConf('ome.combine.shop_id')) == '0') {

            $combine_shop_id = false;
        }
    }

    /**
     * 获取相关可以合并订单
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function fetchCombineOrder($order) {

  //初始化变量
        $ids = array();
        $orders = array();
        $combine_member_id = true;
        $combine_shop_id = true;
        //统一查询收获相关信息，以免抛进来的不一致
        $orderData = app::get(self::__ORDER_APP)->model('orders')->db->selectrow('SELECT * from sdb_ome_orders WHERE order_id='.$order['order_id']);
  //重新更新 HASH
        $updateSql = "UPDATE sdb_ome_orders SET order_combine_hash=MD5(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod)), " .
            "order_combine_idx= CRC32(CONCAT(IFNULL(member_id,order_id),'-',shop_id,'-',ship_name,'-',ship_mobile,'-',ship_area,'-',ship_addr,'-',is_cod)) WHERE " .
            "archive='0' AND shop_id='{$order['shop_id']}' AND status='active' AND process_status in ('unconfirmed','confirmed') AND ship_status='0'";


        if(empty($orderData['member_id'])){
        //如用户ID为空，则只更新当前订单，不支持自动合并
            $updateSql.=' AND order_id ='.$order['order_id'];
        }else if($orderData['shop_type'] == 'shopex_b2b'){
        //分销订单只更新相同收货信息的订单HASH,防止在分销商活动时更新的订单数据量太大
            if( in_array($_SERVER['SERVER_NAME'],array('usashark.tg.taoex.com','misswell.tg.taoex.com','moaoncler.m.fenxiaowang.com')) ){
                $updateSql .= ' AND order_id="' . $order['order_id'] . '"';
            } else{
                $updateSql.=" AND ship_name ='".$orderData['ship_name']."' AND ship_area='".$orderData['ship_area']."' AND ship_addr='". $orderData['ship_addr']."' AND ship_mobile='".$orderData['ship_mobile']."'";
            }

        }else if(($orderData['shop_type'] == 'dangdang') && $orderData['is_cod']== 'true') {////如果店铺类型是当当，且是货到付款不合并
            $updateSql.=' AND order_id ='.$order['order_id'];
        }else if(($orderData['shop_type'] == 'amazon') && $orderData['self_delivery']== 'false'){
        //如果店铺类型是亚马逊，且不是自发货的不合并
            $updateSql.=' AND order_id ='.$order['order_id'];
        }else if( ($orderData['shop_type'] == 'taobao') && $orderData['order_source']== 'tbdx' ){
            //淘宝代销订单不合并 823修改淘宝代销走B2B逻辑
            //$updateSql.=' AND order_id ='.$order['order_id']; 
            $updateSql.=" AND ship_name ='".$orderData['ship_name']."' AND ship_area='".$orderData['ship_area']."' AND ship_addr='".
            $orderData['ship_addr']."' AND ship_mobile='".$orderData['ship_mobile']."'";
            
            }else{
        //正常订单，更新该用户的所有订单
            $updateSql.=" AND member_id = {$order['member_id']}";
        }
        
        kernel::database()->exec($updateSql);
        //更新HASH值重新获取一下订单内容
        $order = app::get(self::__ORDER_APP)->model('orders')->getList('*',array('order_id'=>$order['order_id']));

        $order = $order[0];

        $orderHash = $order['order_combine_hash'];
        $orderIdx = $order['order_combine_idx'];
        //新增合单逻辑
        $this->_getCombineConf($combine_member_id, $combine_shop_id);
        //
        $member_combine = app::get('ome')->getConf('ome.member.combine');//是否自动合单
        $member_combine = intval($member_combine);
        //
        //基础过滤条件[ExBOY 修改拆单 'ship_status'=>array(0, 2)]
        $filter = array('ship_status'=>array(0, 2),'process_status'=>array('unconfirmed', 'confirmed','splitting'),'status'=>'active','order_bn|noequal'=>'0','is_cod'=>$orderData['is_cod']);

        if ($orderData['shop_type'] == 'shopex_b2b') {
        //分销单,对支持跨店合的参数无视,直接内置规则处理
            if ($combine_member_id) {
            //需判断同一用户，因分销没有实际客户信息，以无用户信息方式处理
                if (empty($order['member_id'])) {
                    $filter['order_id'] = $order['order_id'];
                } else {
                    $filter['member_id'] = $order['member_id'];
                    $filter['shop_id'] = $order['shop_id'];
                    $filter = array_merge($filter, $this->_getNullMemberFilter($order));
                }

            } else {
            //检查是否导入订单
                if (empty($order['member_id'])) {
                 //如是导入的无用户订单，则无法判定前端销售的实际店铺，只取出当前订单
                    $filter['order_id'] = $order['order_id'];
                } else {
                    //有用户名,可确认前端销售的实际店铺
                    $filter['member_id'] = $order['member_id'];
                    $filter['shop_id'] = $order['shop_id'];
                    //判定地址一致
                    $filter = array_merge($filter, $this->_getAddrFilter($order));

                }
            }
        }else if($orderData['shop_type'] == 'dangdang' && $orderData['is_cod']== 'true'){//当当，且是货到付款不合并
            $filter['order_id'] = $order['order_id'];
        }else if(($orderData['shop_type'] == 'amazon') && $orderData['self_delivery']== 'false'){
        //如果店铺类型是亚马逊，且不是自发货的不合并
            $filter['order_id'] = $order['order_id'];
        }else if($orderData['shop_type'] == 'taobao' && $orderData['order_source']== 'tbdx'){
            //淘宝代销订单不合并 823修改淘代销走B2B逻辑
            //$filter['order_id'] = $order['order_id'];
            if ($combine_member_id) {
            //需判断同一用户，因分销没有实际客户信息，以无用户信息方式处理
                if (empty($order['member_id'])) {
                    $filter['order_id'] = $order['order_id'];
                } else {
                    $filter['member_id'] = $order['member_id'];
                    $filter['shop_id'] = $order['shop_id'];
                    $filter = array_merge($filter, $this->_getNullMemberFilter($order));
                }

            } else {
            //检查是否导入订单
                if (empty($order['member_id'])) {
                 //如是导入的无用户订单，则无法判定前端销售的实际店铺，只取出当前订单
                    $filter['order_id'] = $order['order_id'];
                } else {
                    //有用户名,可确认前端销售的实际店铺
                    $filter['member_id'] = $order['member_id'];
                    $filter['shop_id'] = $order['shop_id'];
                    //判定地址一致
                    $filter = array_merge($filter, $this->_getAddrFilter($order));

                }
            }
        }else{        //直销单
            if ($combine_member_id) {
                if (empty($order['member_id'])) {
                 //以无用户信息方式处理
                 $filter = array_merge($filter, $this->_getNullMemberFilter($order));
                } else {
                 //有用户名
                 $filter['member_id'] = $order['member_id'];
                }
            } else {
            //判定地址

                $filter = array_merge($filter, $this->_getAddrFilter($order));

            }
            if ($combine_shop_id) {
                $filter['shop_id'] = $order['shop_id'];
            }
            //排除b2b单否则如果相同地址的单子由普通订单点入时，会显示出b2b单在可合并中
            $filter['filter_sql'] = "(shop_type IS NOT NULL AND order_source<>'tbdx' and shop_type<>'shopex_b2b' and (is_cod='false' or (shop_type<>'dangdang' AND is_cod='true')) and (self_delivery='true' or (shop_type<>'amazon' and self_delivery='false')) OR shop_type IS NULL)";
        }
        
        //获取相关订单
       $row = app::get(self::__ORDER_APP)->model('orders')->getList('*', $filter);
       if ($member_combine=='0') {
           
       
            if (!empty($order['member_id'])) {

                if ($order['shop_type'] == 'shopex_b2b'){
                    $tmp = array();
                } else if ($orderData['shop_type'] == 'dangdang' && $orderData['is_cod']== 'true'){
                    $tmp = array();
                }else if($orderData['shop_type'] == 'amazon' && $orderData['self_delivery'] == 'false'){
                    $tmp = array();
                } else if($orderData['shop_type'] == 'taobao' && $orderData['order_source']== 'tbdx'){
                    $tmp = array();
                }else {
                    #拆单_增加确认状态('splitting')与发货状态(ship_status=2)部分发货条件  ExBOY
                    $tmp_filter = array('member_id' => $order['member_id'], 'shop_id' => $order['shop_id'], 'status' => 'active','process_status' => array('unconfirmed', 'confirmed', 'splitting'), 'ship_status' => array(0, 2), 'f_ship_status' => '0', 'order_bn|noequal' => '0','is_cod'=>$orderData['is_cod']);

                    $tmp = app::get(self::__ORDER_APP)->model('orders')->getList('*',$tmp_filter );

                    $row = array_merge($row, $tmp);
                    unset($tmp);
                }
            }
        }
        foreach ((array) $row as $o) {
            if (!in_array($o['order_id'], $ids)) {
                if ($o['order_combine_idx'] == $orderIdx && $o['order_combine_hash'] == $orderHash) {
                    $o['isCombine'] = true;
                } else {
                    $o['isCombine'] = false;

                }

                $orders[$o['order_id']] = $this->convertOrderFormat($o);
                $ids[] = $o['order_id'];
            }
        }

       


        unset($row);

        //检查是否已有发货单
        // $hasDeriveryIds = app::get(self::__ORDER_APP)->model('delivery_order')->getlist('*', array('order_id' => $ids));

        // if ($hasDeriveryIds) {
        //     $dIds = array();
        //     foreach ($hasDeriveryIds as $item) {
        //         $dIds[] = $item['delivery_id'];
        //         $oIds[$item['delivery_id']] = $item['order_id'];
        //     }

        //     $DeriveryIds = app::get(self::__ORDER_APP)->model('delivery')->getList('*', array('delivery_id' => $dIds, 'disabled' => 'false', 'status|notin' => array('cancel', 'back')));
        //     if ($DeriveryIds && is_array($DeriveryIds)) {
        //         foreach ((array) $DeriveryIds as $item) {
        //             $orders[$oIds[$item['delivery_id']]]['derviveryId'] = $item['delivery_id'];
        //         }
        //     }
        // }

        return $orders;
    }

    /**
     * 获该用户除指定用户外的所有订单数
     *
     * @param Integer $memberId 会员编号
     * @param Integer $shopId 店铺ID
     * @return Integer
     */
    public function getCombineMemberCount($memberId, $shopId) {

        $row = app::get(self::__ORDER_APP)->model('orders')->count(array('member_id' => $memberId, 'shop_id' => $shopId, 'status' => 'active',
            'process_status' => array('unconfirmed', 'confirmed'), 'ship_status' => '0', 'f_ship_status' => '0', 'order_bn|noequal' => '0'));
        return $row;
    }

    /**
     * 获取备注及留言的显示格式信息
     *
     * @param Arrar $input 输入的内容
     * @return String
     */
    private function _formatMemo($input) {

        $result = '';
        if (is_array($input)) {

            foreach ($input as $memo) {

                $result .= sprintf("%s\n", $memo['op_content']);
            }
        }

        return $result;
    }

    /**
     * 生成发货单
     *
     * @param Array $orders 订单数组
     * @return Boolean
     */
    public function mkDelivery($orderIds, $consignee, $corpId,$splitting_product = array()) {

        $is_part_split  = false;//[拆单]是否包含部分拆分订单 ExBOY
        $rows = app::get(self::__ORDER_APP)->model('orders')->getList('*', array('order_id' => $orderIds));
        foreach ($rows as $order) {
            $orders[$order['order_id']] = $order;
            
            if($order['process_status'] == 'splitting')
            {
                $is_part_split  = true;
            }
        }
        unset($rows);
        
        #[拆单]多个订单合并生成发货单&&没有订单关联的发货单 ExBOY
        if($is_part_split == false && count($orderIds) > 1 && !empty($splitting_product))
        {
            $in_order_id    = implode(',', $orderIds);
            $sql            = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id in(".$in_order_id.") AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                                            AND d.status NOT IN('failed','cancel','back')";
            $rows           = kernel::database()->selectrow($sql);
            if(empty($rows))
            {
                unset($splitting_product);//注销后则可跳过繁琐的特殊处理
            }
            unset($rows);
        }

        #[拆单]订单明细条件
        $filter = array(
            'order_id' => $orderIds,
        );
        if ($splitting_product) {
            foreach ($splitting_product as $item_type => $product) {
                foreach ($product as $product_id => $nums) {
                    if ($item_type == 'pkg' || $item_type == 'giftpackage') {
                        $filter['filter_sql'][] = ' (product_id="'.$product_id.'" and item_type="'.$item_type.'" ) ';
                    } else {
                        $filter['filter_sql'][] = ' product_id="'.$product_id.'" ';
                    }
                }
            }

            if ($filter['filter_sql'])
            {
                $filter['filter_sql'] = '('.implode(' OR ' , $filter['filter_sql']).') AND `delete`="false"';//过滤删除的商品
            }
        }
        
        $chk_repeat_product     = $repeat_product_ids = array();//记录重复的普通商品  ExBOY
        
        $items = app::get(self::__ORDER_APP)->model('order_items')->getList('*',$filter);
        foreach ($items as $item)
        {
            #[拆单]只处理选择发货的商品[防止多个捆绑商品中有相同货号] ExBOY
            if(!empty($splitting_product) && count($orders) == 1)
            {
                $split_item_type        = $item['item_type'];
                $split_product_id       = $item['product_id'];
                $split_item_id          = $item['item_id'];
                
                if($split_item_type == 'pkg' || $split_item_type == 'giftpackage')
                {
                    if(!empty($splitting_product[$split_item_type][$split_product_id][$split_item_id]))
                    {
                        $item['nums']   = $splitting_product[$split_item_type][$split_product_id][$split_item_id];
                        $orders[$item['order_id']]['items'][$split_item_id]     = $item;
                    }
                }
                else 
                {
                    if(!empty($splitting_product[$split_item_type][$split_product_id]))
                    {
                        $item['nums']   = $splitting_product[$split_item_type][$split_product_id];
                        $orders[$item['order_id']]['items'][$split_item_id]     = $item;
                        
                        $chk_repeat_product[$split_product_id]['num']++;//判断重复出现的普通商品
                        if($chk_repeat_product[$split_product_id]['num'] > 1)
                        {
                            $repeat_product_ids[$split_product_id]   = $split_product_id;
                        }
                    }
                }
            }
            else 
            {
                $orders[$item['order_id']]['items'][$item['item_id']] = $item;
            }
        }
        
        #[拆单]普通商品有重复的则重新读取items中获取购买数量  ExBOY
        if(!empty($splitting_product) && !empty($repeat_product_ids))
        {
            $temp_items     = array();
            foreach ($items as $key => $val) 
            {
                $temp_items[$val['item_id']][$val['product_id']]    = intval($val['nums']) - $val['sendnum'];//待发货数量
            }
            
            foreach ($orders as $order_id => $order) 
            {
                foreach ($order['items'] as $key => $item_row) 
                {
                    $split_product_id       = $item_row['product_id'];
                    $split_item_id          = $item_row['item_id'];
                    if($item_row['item_type'] == 'product' && !empty($repeat_product_ids[$split_product_id]))
                    {
                        if($temp_items[$split_item_id][$split_product_id] ==0 )
                        {
                            unset($orders[$order_id]['items'][$split_item_id]);//已拆分完数量则过滤掉
                        }
                        else 
                        {
                            $orders[$order_id]['items'][$split_item_id]['nums']     = $temp_items[$split_item_id][$split_product_id];
                        }
                    }
                }
            }
            unset($temp_items);
        }
        
        #[拆单]多个订单合并审核&&并包含部分发货订单_重新获取发货数量 ExBOY
        if(count($orders) > 1 && $is_part_split)
        {
            foreach($orders as $order_id => $order)
            {
                if(empty($order['items'])){ continue; }
                
                foreach($order['items'] as $item_id => $item)
                {
                    $sql    = "SELECT SUM(did.number) AS num FROM `sdb_ome_delivery_items_detail` did
                                JOIN `sdb_ome_delivery` d ON d.delivery_id=did.delivery_id
                                WHERE did.order_id='".$order['order_id']."'
                                AND did.order_item_id='".$item['item_id']."'
                                AND did.product_id='".$item['product_id']."'
                                AND d.status != 'back' AND d.status != 'cancel' AND d.status != 'return_back' AND d.is_bind = 'false'";
                    $oi    = kernel::database()->selectrow($sql);
        
                    $orders[$order_id]['items'][$item_id]['nums']    = intval($item['nums']) - intval($oi['num']);//剩余未发货数量
                    if($orders[$order_id]['items'][$item_id]['nums'] <= 0)
                    {
                        unset($orders[$order_id]['items'][$item_id]);
                    }
                }
            }
        }
        
        // 过滤掉没有明细的订单
        foreach ($orders as $order_id => $order) {
            if (empty($order['items'])) unset($orders[$order_id]);
        }

        $group = new omeauto_auto_group_item($orders);

        if ($group->canMkDelivery()) {
            $corp = app::get('ome')->model('dly_corp')->dump($corpId, 'corp_id, name, type, is_cod, weight');
            if (!empty($corp)) {
                $group->setBranchId($consignee['branch_id']);
                unset($consignee['branch_id']);
                $group->setDlyCorp($corp);
                return $group->mkDelivery($consignee);
            }
        } else {

            return false;
        }
    }

    public function getStatus($order) {
        $plugin = array('pay'=>array(), 'flag'=>array(), 'logi'=>array(), 'member'=>array(), 'ordermulti'=>array(), 'branch'=>array(), 'store'=>array(),'oversold'=>array(),'tbgift'=>array(),'shopcombine'=>array(),'crm'=>array(),'tax'=>array());

        $statusList = array();

        foreach ($plugin as $p=>$h) {
            $pInstance = $this->_instancePlugin($p);

            //$status = $order['auto_status'] & $pInstance->getMsgFlag();
            $status = $pInstance->getStatus($order['auto_status'], $order);

            if($status>0) {
                $msg = $pInstance->getAlertMsg($order);
                $msg['msg'] = str_replace(array('<b>', '</b>', '<br />'), '', $msg['msg']);

                $statusList[] = $msg;
            }
        }

        return $statusList;
    }


    /**
     * 获该用户除指定用户外的所有订单数
     *
     * @param Integer $memberId 会员编号
     * @param Integer $shopId 店铺ID
     * @return Integer
     */
    public function getCombineShopMemberCount($orders) {
         /*
        *新增合单逻辑
        */
        $combine_member_id = true;
        $combine_shop_id = true;
        $this->_getCombineConf($combine_member_id, $combine_shop_id);
        $filter = array('status' => 'active','process_status' => array('unconfirmed', 'confirmed'), 'ship_status' => '0', 'f_ship_status' => '0', 'order_bn|noequal' => '0','is_cod'=>$orders['is_cod']);

        if ($orders['shop_type'] == 'shopex_b2b') {
            //分销单,对支持跨店合的参数无视,直接内置规则处理
            if ($combine_member_id) {
            //需判断同一用户，因分销没有实际客户信息，以无用户信息方式处理
                if (empty($orders['member_id'])) {
                    $filter['order_id'] = $orders['order_id'];
                } else {
                    $filter['member_id'] = $orders['member_id'];
                    $filter['shop_id'] = $orders['shop_id'];
                    $filter = array_merge($filter, $this->_getNullMemberFilter($orders));
                }
            } else {
            //检查是否导入订单
                if (empty($orders['member_id'])) {
                 //如是导入的无用户订单，则无法判定前端销售的实际店铺，只取出当前订单
                    $filter['order_id'] = $orders['order_id'];
                } else {
                     //有用户名,可确认前端销售的实际店铺
                     $filter['member_id'] = $orders['member_id'];
                     $filter['shop_id'] = $orders['shop_id'];
                     //判定地址一致
                     $filter = array_merge($filter, $this->_getAddrFilter($orders));
                }
            }
        }else if($orders['shop_type'] == 'dangdang' && $orders['is_cod'] == 'true'){
            $filter['order_id'] = $orders['order_id'];
        }else if($orders['shop_type'] == 'amazon' && $orders['self_delivery'] == 'false'){
            $filter['order_id'] = $orders['order_id'];        }
        else if($orders['shop_type'] == 'taobao' && $orders['order_source'] == 'tbdx'){
            //823修改淘分销走b2b流程
            //$filter['order_id'] = $orders['order_id'];        
            if ($combine_member_id) {
            //需判断同一用户，因分销没有实际客户信息，以无用户信息方式处理
                if (empty($orders['member_id'])) {
                    $filter['order_id'] = $orders['order_id'];
                } else {
                    $filter['member_id'] = $orders['member_id'];
                    $filter['shop_id'] = $orders['shop_id'];
                    $filter = array_merge($filter, $this->_getNullMemberFilter($orders));
                }
            } else {
            //检查是否导入订单
                if (empty($orders['member_id'])) {
                 //如是导入的无用户订单，则无法判定前端销售的实际店铺，只取出当前订单
                    $filter['order_id'] = $orders['order_id'];
                } else {
                     //有用户名,可确认前端销售的实际店铺
                     $filter['member_id'] = $orders['member_id'];
                     $filter['shop_id'] = $orders['shop_id'];
                     //判定地址一致
                     $filter = array_merge($filter, $this->_getAddrFilter($orders));
                }
            }
        } else {
        //直销单
            if ($combine_member_id) {
                if (empty($orders['member_id'])) {
                 //以无用户信息方式处理
                    $filter = array_merge($filter, $this->_getNullMemberFilter($orders));
                } else {
                 //有用户名
                    $filter['member_id'] = $orders['member_id'];
                }
            } else {
            //判定地址
                $filter = array_merge($filter, $this->_getAddrFilter($orders));
            }
            if ($combine_shop_id) {
                $filter['shop_id'] = $orders['shop_id'];
            }
            $filter['filter_sql'] = "(shop_type IS NOT NULL AND order_source<>'tbdx' and shop_type<>'shopex_b2b' and (is_cod='false' or (shop_type<>'dangdang' AND is_cod='true')) and (self_delivery='true' or (shop_type<>'amazon' and self_delivery='false')) OR shop_type IS NULL)";
        }
        
        $row = app::get(self::__ORDER_APP)->model('orders')->count($filter);
        
        return $row;
    }

}