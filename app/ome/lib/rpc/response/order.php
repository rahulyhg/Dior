<?php
/**
 * 前端店铺订单数据业务处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */

//danny_freeze_stock_log
define('FRST_OPER_ID','88');
define('FRST_OPER_NAME','system');
define('FRST_TRIGGER_OBJECT_TYPE','订单：系统接口收订');
define('FRST_TRIGGER_ACTION_TYPE','ome_rpc_response_order：add');

class ome_rpc_response_order extends ome_rpc_response
{
    /**
     * 订单创建
     * @access public
     * @param Array $sdf 订单标准结构的数据
     * @param Object $responseObj 框架API接口实例化对象
     * @return array('order_id'=>'订单主键ID')
     */
    public function add($sdf, &$responseObj){
        //11.10临时增加收订统计监控
//        if(defined('MONITOR_ORDER_ACCEPT') && MONITOR_ORDER_ACCEPT){
//            $redis = new Redis();
//            $redis->connect(MONITOR_REDIS_HOST, MONITOR_REDIS_PORT);
//            $redis->incr('taoguan:apiorder.recv_succ');
//            $redis->zIncrBy('taoguan:apiorder:host_list.'.date('Ymd'),1,$_SERVER['SERVER_NAME']);
//        }

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;

        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','add',$sdf);

        $data = array('tid'=>$sdf['order_bn']);

        if (strpos($rs['logInfo'], '良无印发货订单') !== false) {

        }else{
            $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade',$sdf['order_bn']);
        }

        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }

    }

   /**
     * 更新订单状态
     * @access public
     * @param Array $order_sdf 待更新订单状态标准结构数据
     * @param Object $responseObj 框架API接口实例化对象
     */
    public function status_update($order_sdf, &$responseObj){
        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','status_update',$order_sdf);

        $data = array('tid'=>$order_sdf['order_bn']);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
           $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade',$order_sdf['order_bn']);
        }

        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }

        exit;
    }

    /**
     * 更新订单支付状态
     * @access public
     * @param Array $order_sdf  待更新订单支付状态标准结构数据
     * @param Object $responseObj  框架API接口实例化对象
     */
    public function pay_status_update($order_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','pay_status_update',$order_sdf);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
           $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade.payment',$order_sdf['order_bn']);
        }

        $data = array('tid'=>$sdf['order_bn']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;

        $log = &app::get('ome')->model('api_log');
        $logTitle = '更新订单支付状态接口['. $order_sdf['order_bn'] .']';
        $logInfo = '更新订单状支付态接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';

        $shop_id = $this->get_shop_id($responseObj);

        $status = $order_sdf['pay_status'];
        $order_bn = $order_sdf['order_bn'];

        //返回值
        $return_value = array('tid'=>$order_bn);

        if ($status==''){
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo,'','api.store.trade.payment',$order_sdf['order_bn']);

            $responseObj->send_user_error(app::get('base')->_('Order status '.$status.' is not exists'), $return_value);
        }
        $order_info = kernel::database()->selectrow("SELECT order_id FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        if(!empty($order_info)){

            $order_id = $order_info['order_id'];
            kernel::database()->exec("UPDATE sdb_ome_orders SET pay_status='$status' WHERE order_id='$order_id'");

            $logInfo .= '返回值为：' . var_export($return_value, true) . '<BR>';
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo,'','api.store.trade.payment',$order_sdf['order_bn']);

            return $return_value;

        }else{
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo,'','api.store.trade.payment',$order_sdf['order_bn']);

            $responseObj->send_user_error(app::get('base')->_('Order_bn: '.$order_bn.' is not exists'), $return_value);
        }

        $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo,'','api.store.trade.payment',$order_sdf['order_bn']);
    }

    /**
     * 更新订单发货状态
     * @access public
     * @param Array $order_sdf 待更新订单发货状态标准结构数据
     * @param Object $responseObj  框架API接口实例化对象
     */
    public function ship_status_update($order_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','ship_status_update',$order_sdf);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
           $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade.delivery',$order_sdf['order_bn']);
        }

        $data = array('tid'=>$order_sdf['order_bn']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;

    }

    /**
     * 添加买家留言
     * @access public
     * @param Array $order_sdf 买家留言标准结构数据
     * @param Object $responseObj 框架API接口实例化对象
     */
    public function custom_mark_add($order_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','custom_mark_add',$order_sdf);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
            $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade',$order_sdf['order_bn']);
        }


        $data = array('tid'=>$order_sdf['order_bn']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;
    }

    /**
     * 更新买家留言
     * @access public
     * @param Array $order_sdf 买家留言标准结构数据
     * @param Object $responseObj 框架API接口实例化对象
     */
    public function custom_mark_update($order_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','custom_mark_update',$order_sdf);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
            $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade',$order_sdf['order_bn']);
        }

        $data = array('tid'=>$order_sdf['order_bn']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;
    }

    /**
     * 添加订单备注
     * @access public
     * @param Array $order_sdf 订单备注标准结构数据
     * @param Object $responseObj 框架API接口实例化对象
     */
    public function memo_add($order_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','custom_mark_update',$order_sdf);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
           $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade',$order_sdf['order_bn']);
        }

        $data = array('tid'=>$order_sdf['order_bn']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;

    }


    /**
     * 更新订单备注
     * @access public
     * @param Array $order_sdf 订单备注注标准结构数据
     * @param Object $responseObj 框架API接口实例化对象
     */
    public function memo_update($order_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','memo_update',$order_sdf);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
           $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade',$order_sdf['order_bn']);
        }

        $data = array('tid'=>$order_sdf['order_bn']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;

    }

    public function payment_update($order_sdf, &$responseObj){

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'order','payment_update',$order_sdf);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
           $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade.payment',$order_sdf['order_bn']);
        }

        $data = array('tid'=>$order_sdf['order_bn']);
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;

    }

    public function getOrdersByDate($params, &$responseObj){
        $log = &app::get('ome')->model('api_log');

        $rs = $this->_getOrdersByDate($params);

        if(!empty($rs['logInfo'])||!empty($rs['logTitle'])){
           $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo'],'','api.store.trade',$order_sdf['order_bn']);
        }

        $data = $rs['data'];
        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $responseObj->send_user_error(app::get('base')->_($rs['msg']), $data);
        }
        exit;
    }

    private function _getOrdersByDate($params){
        $shopInfo = ome_shop_type::shop_name($params['order_type']);
        $shopInfo = $shopInfo ? $shopInfo : '未知';
        $logTitle = '矩阵补单获取订单接口 getOrdersByDate [店铺类型:'. $shopInfo .']';
        $logInfo = '矩阵补单获取订单接口 getOrdersByDate：<BR>';
        $logInfo .= '接收参数 $params 信息：' . var_export($params, true) . '<BR>';

        $rs = array('rsp'=>'fail','msg'=>'','data'=>array());
        $rs['logTitle'] = $logTitle;

        if(empty($params['start_time']) || empty($params['end_time']) || empty($params['order_type'])){
            $rs['msg'] = 'Necessary parameters are lost';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }

        //$orderObj = &app::get('ome')->model('orders');
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $page_no = intval($params['page_no']) ? intval($params['page_no']) : 1;
        $page_size = intval($params['page_size']) < 500 ? intval($params['page_size']) : 500;
        $order_type = $params['order_type'];

        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$page_size;
        }

        $orderCount = kernel::database()->selectrow("select count(order_id) as _count from sdb_ome_orders where outer_lastmodify >=".$start_time." and outer_lastmodify <".$end_time." and shop_type = '".$order_type."'");
        $orderLists = kernel::database()->select("select order_bn from sdb_ome_orders where outer_lastmodify >=".$start_time." and outer_lastmodify <".$end_time." and shop_type = '".$order_type."' order by order_id asc limit ".$offset.",".$page_size."");

        if(intval($orderCount['_count']) >0){
            $rs['data'] = array(
                'orderlists' => $orderLists,
                'count' => $orderCount['_count'],
            );

            $rs['rsp'] = 'success';
            $rs['logInfo'] = $logInfo;
        }else{
            $rs['data'] = array(
                'orderlists' => array(),
                'count' => 0,
            );

            $rs['rsp'] = 'success';
            $rs['logInfo'] = $logInfo;
        }
        return $rs;
    }
}
?>