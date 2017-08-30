<?php
class apibusiness_response_remark_360buy_v1 extends apibusiness_response_remark_v1{
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($remark = array()){
        return parent::canAccept($remark);
    }
    /**
     * 添加京东订单备注
     *
     * @return void
     * @author 
     **/
    public function add(){
       $obj_order = app::get('ome')->model('orders');
       $oOperation_log = app::get('ome')->model('operation_log');
       $sdf = $this->remark;
       $shop_id = $sdf['shop']['shop_id'];
       $order_bn =  $sdf['tid'];
       
       $tgOrder =  $obj_order->dump(array('order_bn'=>$order_bn),'order_id,process_status,custom_mark');
       #本地没有，则单拉订单
       if(empty( $tgOrder)){
           $order_type = ($sdf['shop']['business_type']=='fx') ? 'agent' : 'direct';
           #获取京东订单
           $orderRsp = kernel::single('apibusiness_router_request')->setShopId($shop_id)->get_order_detial($order_bn,$order_type);
           if ($orderRsp['rsp'] == 'succ') {
               #保存到本地
               $rs = kernel::single('ome_syncorder')->get_order_log($orderRsp['data']['trade'],$shop_id,$msg);
               if ($rs) {
                   $tgOrder = $obj_order->dump(array('order_bn'=>$order_bn),'order_id,process_status,custom_mark');
               }
           }
           if (!$tgOrder) {
               $this->_apiLog['info']['msg'] = 'no order in erp';
               return false;
           }           
       }
       $process_status = array('unconfirmed','confirmed','splitting','splited');
       if(!in_array($tgOrder['process_status'],$process_status)){
           $this->_apiLog['title'] = '接受京东备注（订单号：' . $order_bn. '）';
           $this->_apiLog['info'][] = "接受参数：".var_export($sdf,true);
           $this->_apiLog['info']['msg'] = '当前订单状态不能再更新,不接受！';
           return false;
       }
       #原来的备注
       $remark = unserialize($tgOrder['custom_mark']);
       #本次新的备注
       $new_remark = array('op_name'=>$sdf['shop']['name'],'op_content'=>$sdf['remark'],'op_time'=>$sdf['modified']);
       #检测新老备注是否发生变化
       if(!empty($remark)){
           foreach($remark as $val){
              #时间一样
              if($val['op_time'] == $new_remark['op_time']){
                  #内容也一样，则说明本次是重复推送
                  if($val['op_content'] == $new_remark['op_content'] ){
                       $this->_apiLog['title'] = '接受京东备注（订单号：' . $order_bn. '）';
                       $this->_apiLog['info'][] = "接受参数：".var_export($sdf,true);
                       $this->_apiLog['info']['msg'] = '订单备注没变化,不更新！';
                       return false;
                  }
              }
           }
       }
       #追加到老的备注上
       $remark[] = $new_remark;
       $obj_order->update(array('custom_mark'=>serialize($remark)),array('order_id'=>$tgOrder['order_id'])); #更新买家备注
       $memo ='更新京东订单备注状态：（订单号：'.$order_bn.'）';
       $oOperation_log->write_log('order_edit@ome',$tgOrder['order_id'],$memo);
       
       $this->_apiLog['title'] = '接受京东订单备注（订单号：' . $order_bn. '）';
       $this->_apiLog['info'][] = "接受参数：".var_export($sdf,true);
       $this->_apiLog['info']['msg'] = '更新京东订单备注成功！';
    }
}
?>