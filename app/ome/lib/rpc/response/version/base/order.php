<?php
/**
 * 前端店铺订单数据业务处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_version_base_order extends ome_rpc_response
{

    public $shop_type2pay_type = array(
        'taobao' => 'alipaytrad',
        'paipai' => 'tenpaytrad',
        '360buy' => 'online',
    );

    /**
     * 订单接收
     * 包括订单的添加、更新等操作
     * @access public
     * @param Array $sdf 订单标准结构的数据
     * @return array('tid'=>'订单号')
     */
    public function add($sdf){

        //配送信息
        $sdf['shipping'] = json_decode($sdf['shipping'],true);
        //支付信息
        $sdf['payinfo'] = json_decode($sdf['payinfo'],true);
        //收货人信息
        $sdf['consignee'] = json_decode($sdf['consignee'],true);
        //发货人信息
        $sdf['consigner'] = json_decode($sdf['consigner'],true);
        
        //存放代销人信息(直销单),分销商信息(分销单)
        $selling_agent = json_decode($sdf['selling_agent'],true);

        if($sdf['t_type'] == 'fenxiao'){
            foreach($selling_agent as $k=>$v){
                if($k == 'agent'){
                    $selling_agent['member_info'] = $selling_agent['agent'];
                    unset($selling_agent['agent']);                    
                }
            }
        }

        $sdf['selling_agent'] = $selling_agent;

        //买家会员信息
        $sdf['member_info'] = json_decode($sdf['member_info'],true);
        //订单优惠方案
        $sdf['pmt_detail'] = json_decode($sdf['pmt_detail'],true);
        //支付单(兼容老版本)
        $sdf['payment_detail'] = json_decode($sdf['payment_detail'],true);
        //支付单(新版本)

        if(isset($sdf['payments'])){
            $sdf['payments'] = json_decode($sdf['payments'],true);
        }

        //订单商品
        $sdf['order_objects'] = json_decode($sdf['order_objects'],true);

        //保存订单
        return kernel::single('ome_rpc_response_func_order')->save($sdf);
    }

   /**
     * 更新订单状态
     * @access public
     * @param Array $order_sdf 待更新订单状态标准结构数据
     */
    public function status_update($order_sdf){

        /* Log info */
        $logTitle = '更新订单状态接口['. $order_sdf['order_bn'] .']';
        $logInfo = '更新订单状态接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info */

        $filter = array('node_id'=>$node_id);
        $responseObj = kernel::single('ome_rpc_response');
        $shop_info = $responseObj->filter($filter);

        $logInfo .= '店铺信息：' . var_export($shop_info, true) . '<BR>';

        $shop_id = $order_sdf['shop_id'];
        $shop_type = $order_sdf['shop_type'];
        $status = $order_sdf['status'];
        $order_bn = $order_sdf['order_bn'];

        //返回值
        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logInfo'=>'','logTitle'=>$logTitle);

        if ($status==''){
            $rs['msg'] = 'Order status '.$status.' is not exists';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
        $order_info = kernel::database()->selectrow("SELECT pay_status,order_id,op_id FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        $logInfo .= '订单信息：' . var_export($order_info, true) . '<BR>';

        if(!empty($order_info)){

            $order_id = $order_info['order_id'];

            if (!$order_info['op_id']){
                $user_info = kernel::database()->selectrow("SELECT user_id FROM sdb_desktop_users WHERE super='1' ORDER BY user_id asc ");
                $op_id = $user_info['user_id'];
                $op_idsql = ",op_id='".$op_id."'";
            }
            kernel::database()->exec("UPDATE sdb_ome_orders SET status='$status'$op_idsql WHERE order_id='".$order_id."'");

            $logInfo .= '更新订单状态：' . var_export($status, true) . '<BR>';

            //kernel::single('ome_order')->_update_status($order_id); //TODO:更新订单的扩展状态（待处理，已处理,待分派，已分派）

            if ($status == 'dead'){//订单取消
                //针对所有前端店铺业务逻辑处理:已支付/部分支付/部分退款的直接返回已支付状态,不作取消订单操作
                //$b2b_shop_list = ome_shop_type::b2b_shop_list();
                $shopex_shop_list = ome_shop_type::shopex_shop_type();//yangminsheng
                $logInfo .= 'shopex shop list：' . var_export($shopex_shop_list, true) . '<BR>';

                if (in_array($shop_type,$shopex_shop_list) && in_array($order_info['pay_status'],array('1','2','3','4'))){
                    $rs['msg'] = 'Order '.$order_bn.' has been paid';
                    $rs['logInfo'] = $logInfo;
                    return $rs;
                }else{
                    $logInfo .= '取消订单，ID为：' . $order_id . '<BR>';
                    $orderObj = &app::get('ome')->model('orders');
                    $orderObj->cancel($order_id,"订单被取消",$request=false);
                }
            }
            $logInfo .= '返回值为：' . var_export($rs_data, true) . '<BR>';
            $rs['logInfo'] = $logInfo;
            $rs['rsp'] = 'success';
            return $rs;
        }else{

            if ($status == 'dead'){
                //取消失败订单
                if (app::get('omeapilog')->is_installed()){
                    $fail_orderObj = &app::get('omeapilog')->model('orders');
                    $result = $fail_orderObj->cancel_order($order_bn, $shop_id);
                    $logInfo .= '取消店铺ID为：' . $shop_id . ' 的失败订单ID：' . $order_bn . '<BR>';
                }
            }
            if (!$result){
                $logInfo .= '取消失败订单没有成功<BR>';
                $rs['msg'] = 'Order Order_bn '.$order_bn.' is not exists';
                $rs['logInfo'] = $logInfo;
            }

            return $rs;
        }

        $rs['rsp'] = 'success';
        $rs['logInfo'] = $logInfo;
        return $rs;
    }

    /**
     * 更新订单支付状态
     * @access public
     * @param Array $order_sdf  待更新订单支付状态标准结构数据
     */
    public function pay_status_update($order_sdf){

        /* Log info*/
        $logTitle = '更新订单支付状态接口['. $order_sdf['order_bn'] .']';
        $logInfo = '更新订单状支付态接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info*/

        $status = $order_sdf['pay_status'];
        $order_bn = $order_sdf['order_bn'];
        $shop_id = $order_sdf['shop_id'];

        //返回值
        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'');

        if ($status==''){
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = 'Order status '.$status.' is not exists';
            return $rs;
        }
        $order_info = kernel::database()->selectrow("SELECT order_id FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        if(!empty($order_info)){

            $order_id = $order_info['order_id'];
            kernel::database()->exec("UPDATE sdb_ome_orders SET pay_status='$status' WHERE order_id='$order_id'");
            //kernel::single('ome_order')->_update_status($order_id);

            $logInfo .= '返回值为：' . var_export($rs_data, true) . '<BR>';

            $rs['rsp'] = 'success';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }else{
            $rs['msg'] = 'Order_bn: '.$order_bn.' is not exists';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }

        $rs['rsp'] = 'success';
        $rs['logInfo'] = $logInfo;
        return $rs;
    }

    /**
     * 更新订单发货状态
     * @access public
     * @param Array $order_sdf 待更新订单发货状态标准结构数据
     */
    public function ship_status_update($order_sdf){

        /* Log info*/
        $logTitle = '更新订单发货状态接口['. $order_sdf['order_bn'] .']';
        $logInfo = '更新订单状发货态接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info*/

        $status = $order_sdf['ship_status'];
        $order_bn = $order_sdf['order_bn'];
        $shop_id = $order_sdf['shop_id'];

        //返回值
        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data);


        if ($status==''){
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = 'Order status '.$status.' is not exists';
            return $rs;
        }
        $order_info = kernel::database()->selectrow("SELECT order_id FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        $logInfo .= '订单信息：' . var_export($order_info, true) . '<BR>';

        if(!empty($order_info)){

            $order_id = $order_info['order_id'];
            kernel::database()->exec("UPDATE sdb_ome_orders SET ship_status='$status' WHERE order_id='$order_id'");
            //kernel::single('ome_order')->_update_status($order_id);
            $logInfo .= '返回值为：' . var_export($rs_data, true) . '<BR>';
            $rs['logInfo'] = $logInfo;
            $rs['rsp'] = 'success';
            return $rs;
        }else{
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = 'Order_bn: '.$order_bn.' is not exists';
            return $rs;
        }

        $rs['logInfo'] = $logInfo;
        $rs['rsp'] = 'success';
        return $rs;
    }

    /**
     * 添加买家留言
     * @access public
     * @param Array $order_sdf 买家留言标准结构数据
     */
    public function custom_mark_add($order_sdf){

        /* Log info*/
        $logTitle = '添加买家留言接口['. $order_sdf['order_bn'] .']';
        $logInfo = '添加买家留言接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info*/

        $shop_id = $order_sdf['shop_id'];
        $order_bn = $order_sdf['tid'];
        $op_content = $order_sdf['message'];
        $op_name = $order_sdf['sender'];
        $op_time = kernel::single('ome_func')->date2time($order_sdf['add_time']);
        $order_info = kernel::database()->selectrow("SELECT order_id,custom_mark FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data);

        if(!empty($order_info)){
            $order_id = $order_info['order_id'];
            $orderObj = &app::get('ome')->model('orders');

            //取出买家留言信息
            $oldmemo= unserialize($order_info['custom_mark']);
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $custom_memo[] = $v;
            }
            $newmemo =  htmlspecialchars($op_content);
            $newmemo = array('op_name'=>$op_name, 'op_time'=>$op_time, 'op_content'=>$newmemo);
            $custom_memo[] = $newmemo;
            $order_memo['custom_mark'] = serialize($custom_memo);
            $filter = array('order_id'=>$order_id);
            $orderObj->update($order_memo, $filter);

            $rs['logInfo'] = $logInfo;
            $rs['rsp'] = 'success';
            return $rs;
        }else{
            $rs['msg'] = 'Order: '.$order_bn.' is not exists';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
    }

    /**
     * 更新买家留言
     * @access public
     * @param Array $order_sdf 买家留言标准结构数据
     */
    public function custom_mark_update($order_sdf){

        /* Log info*/
        $logTitle = '更新买家留言接口['. $order_sdf['order_bn'] .']';
        $logInfo = '更新买家留言接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info*/

        $shop_id = $order_sdf['shop_id'];
        $order_bn = $order_sdf['tid'];
        $op_content = $order_sdf['message'];
        $op_name = $order_sdf['sender'];
        $op_time = kernel::single('ome_func')->date2time($order_sdf['add_time']);
        $order_info = kernel::database()->selectrow("SELECT order_id,custom_mark FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data);

        if(!empty($order_info)){
            $order_id = $order_info['order_id'];
            $orderObj = &app::get('ome')->model('orders');

            //取出买家留言信息
            $oldmemo= unserialize($order_info['custom_mark']);
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $custom_memo[] = $v;
            }
            $newmemo =  htmlspecialchars($op_content);
            $newmemo = array('op_name'=>$op_name, 'op_time'=>$op_time, 'op_content'=>$newmemo);
            $custom_memo[] = $newmemo;
            $order_memo['custom_mark'] = serialize($custom_memo);
            $filter = array('order_id'=>$order_id);
            $orderObj->update($order_memo, $filter);
            $rs['logInfo'] = $logInfo;
            $rs['rsp'] = 'success';
            return $rs;
        }else{
            $rs['msg'] = 'Order: '.$order_bn.' is not exists';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
    }

    /**
     * 添加订单备注
     * @access public
     * @param Array $order_sdf 订单备注标准结构数据
     */
    public function memo_add($order_sdf){

        /* Log info*/
        $logTitle = '添加订单备注接口['. $order_sdf['order_bn'] .']';
        $logInfo = '添加订单备注接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info*/

        $shop_id = $order_sdf['shop_id'];
        $order_bn = $order_sdf['tid'];
        $op_content = $order_sdf['memo'];
        $op_name = $order_sdf['sender'];
        $op_time = kernel::single('ome_func')->date2time($order_sdf['add_time']);
        $order_info = kernel::database()->selectrow("SELECT order_id,mark_text FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data);

        if(!empty($order_info)){
            $order_id = $order_info['order_id'];
            $orderObj = &app::get('ome')->model('orders');

            //取出订单备注信息
            $oldmemo= unserialize($order_info['mark_text']);
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $custom_memo[] = $v;
            }
            $newmemo =  htmlspecialchars($op_content);
            $newmemo = array('op_name'=>$op_name, 'op_time'=>$op_time, 'op_content'=>$newmemo);
            $custom_memo[] = $newmemo;
            $order_memo['mark_text'] = serialize($custom_memo);
            $order_memo['mark_type'] = $order_sdf['flag'];
            $filter = array('order_id'=>$order_id);
            $orderObj->update($order_memo, $filter);
            $rs['logInfo'] = $logInfo;
            $rs['rsp'] = 'success';
            return $rs;
        }else{
            $rs['msg'] = 'Order: '.$order_bn.' is not exists';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
    }


    /**
     * 更新订单备注
     * @access public
     * @param Array $order_sdf 订单备注注标准结构数据
     */
    public function memo_update($order_sdf){

        /* Log info*/
        $logTitle = '更新订单备注接口['. $order_sdf['order_bn'] .']';
        $logInfo = '更新订单备注接口：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info*/

        $shop_id = $order_sdf['shop_id'];
        $order_bn = $order_sdf['tid'];
        $op_content = $order_sdf['memo'];
        $op_name = $order_sdf['sender'];
        $op_time = kernel::single('ome_func')->date2time($order_sdf['add_time']);
        $order_info = kernel::database()->selectrow("SELECT order_id,mark_text FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data);

        if(!empty($order_info)){
            $order_id = $order_info['order_id'];
            $orderObj = &app::get('ome')->model('orders');

            //取出订单备注信息
            $oldmemo= unserialize($order_info['mark_text']);
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $custom_memo[] = $v;
            }
            $newmemo =  htmlspecialchars($op_content);
            $newmemo = array('op_name'=>$op_name, 'op_time'=>$op_time, 'op_content'=>$newmemo);
            $custom_memo[] = $newmemo;
            $order_memo['mark_text'] = serialize($custom_memo);
            $order_memo['mark_type'] = $order_sdf['flag'];
            $filter = array('order_id'=>$order_id);
            $orderObj->update($order_memo, $filter);

            $rs['rsp'] = 'success';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }else{
            $rs['msg'] = 'Order: '.$order_bn.' is not exists';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
    }

    /**
     * 更新订单支付方式
     * @access public
     * @param Array $order_sdf 订单备注注标准结构数据
     */
    public function payment_update($order_sdf){

        /* Log info*/
        $logTitle = '更新支付单状态接口 payment_update [订单:'. $order_sdf['order_bn'] .']';
        $logInfo = '更新支付单状态接口 payment_update：<BR>';
        $logInfo .= '接收参数 $order_sdf 信息：' . var_export($order_sdf, true) . '<BR>';
        /* Log info*/

        $shop_id = $order_sdf['shop_id'];

        $logInfo .= '店铺ID：' . var_export($shop_id, true) . '<BR>';

        $order_bn = $order_sdf['order_bn'];
        $order_info = kernel::database()->selectrow("SELECT order_id,mark_text,cost_payment,total_amount,final_amount FROM sdb_ome_orders WHERE order_bn='".$order_bn."' AND shop_id='".$shop_id."'");

        $rs_data = array('tid'=>$order_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data);

        if(!empty($order_info)){
            $orderObj = &app::get('ome')->model('orders');
            $order_id = $order_info['order_id'];

            $order_payment['pay_bn'] = $order_sdf['pay_bn'];
            $order_payment['payinfo']['pay_name'] = $order_sdf['payment'];
            //支付费用修改
            $order_payment['payinfo']['cost_payment'] = $order_sdf['cost_payment'];
            //从新计算订单总额
            $order_payment['total_amount'] = $order_info['total_amount'] - $order_info['cost_payment'] + $order_sdf['cost_payment'];
            $order_payment['cur_amount'] = $order_payment['total_amount'];
            $order_payment['order_id'] = $order_id;

            $orderObj->save($order_payment);

            $rs['rsp'] = 'success';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }else{
            $rs['msg'] = 'Order: '.$order_bn.' is not exists';
            $rs['logInfo'] = $logInfo;
            return $rs;
        }
    }
}
?>