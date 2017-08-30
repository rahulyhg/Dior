<?php
/**
 * 前端店铺绑定关系处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_shop extends ome_rpc_response
{

    /**
     * 更新绑定或解除店铺的状态信息
     * 当前端店铺申请绑定或解除关系时，矩阵中心会调用此方法，以更新绑定店铺的绑定状态
     * @access public
     * @param $result 中心返回的店铺数据
     * @method POST
     */
    function shop_callback($result){
        $shop_id = $result['shop_id'];
        $nodes = $_POST;

        $status = $nodes['status'];
        $node_id = $nodes['node_id'];
        $node_type = $nodes['node_type']; 
        $api_v = $nodes['api_v'];
        $filter = array('shop_id'=>$shop_id);
        $shopObj = &app::get('ome')->model('shop');
        /**  支付宝授权  **/
        if ($shop_id && isset($nodes['callback_type']) && $nodes['callback_type'] == 'alipay_subscribe_auth' ) {
          if ($nodes['is_alipay_subscribe'] == '1') {
              $shopObj->update(array('alipay_authorize' => 'true'),array('shop_id' => $shop_id));
          }
          die(1);
        }
        
        $shopdetail = $shopObj->dump(array('node_id'=>$node_id), 'node_id');
        
        if ($status=='bind' and !$shopdetail['node_id']){
            if ($node_id){
                $data = array('api_version'=>$api_v,'node_id'=>$node_id,'node_type'=>$node_type,'shop_type'=>$node_type);
                //添加已绑定的京东店铺类型business_type（SOP、LBP、SOPL）2012-3-14
                if (isset($nodes['business_type'])&&$nodes['business_type']){
                    $data['addon']['type'] = $nodes['business_type'];
                    $data['tbbusiness_type'] = $nodes['business_type'];//区分淘宝还是天猫
                }

                if (isset($nodes['nickname'])&&$nodes['nickname']){
                    $data['addon']['nickname'] = $nodes['nickname'];
                }
                if (isset($nodes['tb_user_id'])&&$nodes['tb_user_id']){
                    $data['addon']['tb_user_id'] = $nodes['tb_user_id'];
                }

                // 记录绑定时间
                $data['addon']['bindtime'] = time();

                //接受单据类型：tb_fx_order  分销单,tb_zx_order  直销单
                if(isset($nodes['subbiztype'])&&$nodes['subbiztype']){
                    $data['business_type'] = $nodes['subbiztype'];
                }
                
                $shopObj->update($data, $filter);                //给有需要的service发送绑定信息
                foreach(kernel::servicelist('ome_shop_relation') as $object){
                    if(method_exists($object, 'bind')){
                        $object->bind($shop_id);
                    }
                }
                die('1');
            }
        }elseif ($status=='unbind'){
            app::get('ome')->setConf('taobao_session_'.$node_id, 'false');
            base_kvstore::instance('setting_ome')->store('api_v_'.$node_id,'');
            $data = array('node_id'=>'','business_type'=>'zx');
            $shopObj->update($data, $filter);
            //给有需要的service发送绑定信息
            foreach(kernel::servicelist('ome_shop_relation') as $object){
                if(method_exists($object, 'unbind')){
                    $object->unbind($shop_id);
                }
            }
            die('1');
        }
        die('0');
    }

    /**
     * 批量获取订单
     * 此功能属个性定制，如有巧合，纯属意外
     * @param $params 请求查询条件参数
     * @return 订单号数据
     */
    function batch_get_orders(){

        $params = $_POST;
        $node_id = $params['to_node_id'];
        $shopObj = &app::get('ome')->model('shop');
        $shop_detail = $shopObj->dump(array('node_id'=>$node_id), 'shop_id');
        $shop_id = $shop_detail['shop_id'];

        if (empty($shop_id)) die('');

        $from_time = kernel::single('ome_func')->date2time($params['start_time']);
        $end_time = kernel::single('ome_func')->date2time($params['end_time']);
        $page = $params['page'] ? $params['page'] : '1';
        $limit = $params['limit'] ? $params['limit'] : '-1';
        if ($limit == '-1'){
            $lim = '0';
        }else{
            $lim = ($page-1)*$limit;
        }
        $return_order_list = array();
        if ($from_time && $end_time){
            $orderObj = &app::get('ome')->model('orders');
            $filter = array('createtime|between'=>array($from_time,$end_time), 'shop_id'=>$shop_id);
            $order_list = $orderObj->getList('order_bn', $filter, $lim, $limit);
            if ($order_list){
                foreach ($order_list as $val){
                    $return_order_list[] = $val['order_bn'];
                }
            }
        }
        $return_order_list = array(
            'total' => count($return_order_list),
            'tid' => $return_order_list,
        );
        die(json_encode($return_order_list));
    }
    /**
     * 获取中心通知的淘宝session过期的时间
     *  
     * @param  void
     * @return void
     * @author 
     **/
    public function shop_session($data)
    {   
        $data = $_POST;
        $certi_ac = $data['certi_ac'];
        unset($data['certi_ac']);
        $token = base_certificate::get('token');
        $sign = $this->genSign($data,$token);
        if($certi_ac != $sign){
           echo json_encode(array('res'=>'fail','msg'=>'签名错误'));
           exit;
        }
        $filter = array('node_id'=>$data['to_node']);
        $session_expire_time = $data['session_expire_time'];
        $shopMdl  = app::get('ome')->model('shop');
        $shopinfo = $shopMdl->getList('addon',$filter);

        if(is_array($shopinfo) && count($shopinfo)>0){
            if ($addon = $shopinfo[0]['addon'] ) {
                $newaddon['addon'] = array_merge($addon,$data);
            }
            $shopMdl->update($newaddon,$filter);
            echo json_encode(array('res'=>'succ','msg'=>''));
        }else{
            echo json_encode(array('res'=>'fail','msg'=>'没有找到网店'));
        }
        exit;
    }
    
    public function genSign($params,$token)
    {
     ksort($params);
     $str = '';
     foreach ($params as $key =>$value) {
         
              if ($key != 'certi_ac' && $key != 'certificate_id') {
                 $str .= $value;
              }
     }
     $signString = md5($str.$token);
     return $signString;
    }
}