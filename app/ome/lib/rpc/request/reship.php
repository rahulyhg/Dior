<?php
/**
 * 退货同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_reship extends ome_rpc_request {

    //退货状态
    var $reship_status = array(
          'succ'=>'SUCC',
          'failed'=>'FAILED',
          'cancel'=>'CANCEL',
          'progress'=>'PROGRESS',
          'timeout'=>'TIMEOUT',
          'ready'=>'READY',
          'stop'=>'STOP',
          'back'=>'BACK'
    );

    /**
     * 添加交易退货单
     * @access public
     * @param int $reship_id 退货单主键ID
     * @return boolean
     */
    public function add($reship_id){

        if(!empty($reship_id)){

            $reshipObj = &app::get('ome')->model('reship');
            $reship_itemsObj = &app::get('ome')->model('reship_items');
            $return_productObj = &app::get('ome')->model('return_product');
            $orderObj = &app::get('ome')->model('orders');
            $memberObj = &app::get('ome')->model('members');
            $reship_items = $reship_itemsObj->getList('product_name,bn,num',array('reship_id'=>$reship_id,'return_type'=>array('return','refuse')),0,-1);
            $reship_detail = $reshipObj->dump(array('reship_id'=>$reship_id), '*');
            $order = $orderObj->dump($reship_detail['order_id'], 'order_bn,member_id');
            $memberinfo = $memberObj->dump($order['member_id'],'uname,name');

            //发货品信息
            $reshipitems = array();
            if ($reship_items)
            foreach ($reship_items as $k=>$v){
                $v['sku_type'] = 'goods';
                $v['name'] = $v['product_name'];
                $v['number'] = $v['num'];
                unset($v['product_name']);
                unset($v['num']);
                $reshipitems[] = $v;
            }
            $area = $reship_detail['consignee']['area'];
            if (strpos($area, ":")){
                $area = explode(":", $area);
                $area = explode("/", $area[1]);
            }

            if (!$reship_detail['t_end']){
                $reship_detail['t_end'] = time();
            }
            if (!$reship_detail['t_begin']){
                $reship_detail['t_begin'] = time();
            }

            $order = $orderObj->dump($reship_detail['order_id'], 'order_bn');
            $params['tid'] = $order['order_bn'];
            $params['reship_fee'] = $reship_detail['money'];
            $params['reship_id'] = $reship_detail['reship_bn'];
            $params['buyer_id'] = $memberinfo['account']['uname'];
            $params['buyer_uname'] = $memberinfo['account']['uname'];
            $params['create_time'] = date("Y-m-d H:i:s",$reship_detail['t_begin']);
            $params['is_protect'] = $reship_detail['is_protect'];
            $params['status'] = $this->reship_status[$reship_detail['status']];
            $params['reship_type'] = $reship_detail['delivery']?$reship_detail['delivery']:'';
            $params['logistics_id'] = $reship_detail['logi_id']?$reship_detail['logi_id']:'';
            $params['logistics_company'] = $reship_detail['logi_name']?$reship_detail['logi_name']:'';
            $params['logistics_no'] = $reship_detail['logi_no']?$reship_detail['logi_no']:'';
            $params['receiver_name'] = $reship_detail['consignee']['name']?$reship_detail['consignee']['name']:'';
            $params['receiver_state'] = $area[0]?$area[0]:'';#省
            $params['receiver_city'] = $area[1]?$area[1]:'';#市
            $params['receiver_district'] = $area[2]?$area[2]:'';#县
            $params['receiver_address'] = $reship_detail['consignee']['addr']?$reship_detail['consignee']['addr']:'';
            $params['receiver_zip'] = $reship_detail['consignee']['zip']?$reship_detail['consignee']['zip']:'';
            $params['receiver_mobile'] = $reship_detail['consignee']['mobile']?$reship_detail['consignee']['mobile']:'';
            $params['receiver_email'] = $reship_detail['consignee']['email']?$reship_detail['consignee']['email']:'';
            $params['receiver_phone'] = $reship_detail['consignee']['telephone']?$reship_detail['consignee']['telephone']:'';
            $params['memo'] = $reship_detail['memo']?$reship_detail['memo']:'';
            $params['t_begin'] = date("Y-m-d H:i:s",$reship_detail['t_begin']);
            $params['t_end'] = date("Y-m-d H:i:s",$reship_detail['t_end']);
            $params['reship_operator'] = kernel::single('desktop_user')->get_login_name();
            $params['reship_items']= json_encode($reshipitems);
            $params['ship_type']= 'return';
            $params['modify']= date("Y-m-d H:i:s",$reship_detail['t_end']);

            $callback = array(
                'class' => 'ome_rpc_request_reship',
                'method' => 'reship_add_callback',
            );

            $shop_id = $reship_detail['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')添加[交易退货单](订单号:'.$order['order_bn'].'退货单号:'.$reship_detail['reship_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.reship.add',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }

    }

    function reship_add_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新交易退货状态
     * @access public
     * @param int $reship_id 退货单主键ID
     * @return boolean
     */
    public function status_update($reship_id){

        if(!empty($reship_id)){
            $reshipObj = &app::get('ome')->model('reship');
            $orderObj = &app::get('ome')->model('orders');

            $reship_detail = $reshipObj->dump(array('reship_id'=>$reship_id), 'order_id,shop_id,status,reship_bn');
            $order = $orderObj->dump($reship_detail['order_id'], 'order_bn,shop_id');
            $params['tid'] = $order['order_bn'];
            $params['reship_id'] = $reship_detail['reship_bn'];
            $params['oid '] = '';#子订单id
            $params['status'] = $this->reship_status[$reship_detail['status']];

            $callback = array(
                'class' => 'ome_rpc_request_reship',
                'method' => 'reship_status_update_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新[交易退货状态]:'.$params['status'].'(订单号:'.$order['order_bn'].'退货单号:'.$reship_detail['reship_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.reship.status.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function reship_status_update_callback($result,$status){
        return $this->callback($result);
    }
}