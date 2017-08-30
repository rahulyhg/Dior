<?php
/**
* 蘑菇街支付状态处理
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: tboversold.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_mogujie extends apibusiness_response_order_plugin_abstract
{
    const _APP_NAME = 'ome';
    /**
     * 更新前处理
     *
     * @return void
     * @author 
     **/
    public function preUpdate(){
      if($this->_platform->_tgOrder['status'] =='active' && $this->_platform->_ordersdf['ship_status'] == '0'){
        #部分退款
        if($this->_platform->_ordersdf['status'] == 'active' && $this->_platform->_ordersdf['pay_status'] =='4'){
            $this->_platform->_newOrder['pay_status'] = '4'; #支付状态置为部分付款
            #异常处理
            $this->_checkAbnormal($this->_platform->_tgOrder['order_id']);
        }
        #全额退款
        if($this->_platform->_ordersdf['status'] == 'dead' && $this->_platform->_ordersdf['pay_status'] =='5'){
            $this->_platform->_newOrder['pay_status'] = '5';
            
        }
        #退款中
        if($this->_platform->_ordersdf['status'] == 'active' && $this->_platform->_ordersdf['pay_status'] =='6'){
            $this->_platform->_newOrder['pay_status'] = '6';
            #异常处理
            $this->_checkAbnormal($this->_platform->_tgOrder['order_id']);
        }
      }
    }
    /**
     * 检查是否部分退款异常
     * @access protected
     * @param string order_id
     * @return boolean
     */
    protected function _checkAbnormal($order_id){
        if (empty($order_id)) return false;
        $orderObj = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderObj->dump(array('order_id'=>$order_id),'status,ship_status');
        if($tgOrder['status'] == 'active' && $tgOrder['ship_status'] == '0'){
            #添加部分退款异常并暂停订单
            $abnormalObj = app::get(self::_APP_NAME)->model('abnormal');
            $abnormalTypeObj = app::get(self::_APP_NAME)->model('abnormal_type');
            $abnormalTypeInfo = $abnormalTypeObj->dump(array('type_name'=>'订单未发货部分退款'),'type_id,type_name');

            if($abnormalTypeInfo){
                $tmp['abnormal_type_id'] = $abnormalTypeInfo['type_id'];
            }else{
                #新增异常类型
                $add_arr['type_name'] = '订单未发货部分退款';
                $abnormalTypeObj->save($add_arr);
                $tmp['abnormal_type_id'] = $add_arr['type_id'];
            }
    
            $abnormalInfo = $abnormalObj->dump(array('order_id'=>$order_id),'abnormal_id,abnormal_memo');
            $memo = '';
            if($abnormalInfo){
                $tmp['abnormal_id'] = $abnormalInfo['abnormal_id'];
                $oldmemo= unserialize($abnormalInfo['abnormal_memo']);
                if ($oldmemo){
                    foreach($oldmemo as $k=>$v){
                        $memo[] = $v;
                    }
                }
            }
    
            $op_name = 'system';
            $newmemo =  '订单未发货部分退款，系统自动设置为异常并暂停。';
            $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
            $tmp['abnormal_memo'] = serialize($memo);
    
            $tmp['abnormal_type_name'] ='订单未发货部分退款';
            $tmp['is_done'] = 'false';
            $tmp['order_id'] = $order_id;
    
            $abnormalObj->save($tmp);
    
            #订单暂停并设置为异常
            $order_data = array('order_id'=>$order_id,'abnormal'=>'true','pause'=>'true');
            $orderObj->save($order_data);
        }else{
            return true;
        }
    }    
}