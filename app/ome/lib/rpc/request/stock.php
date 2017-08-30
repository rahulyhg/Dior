<?php
/**
 * 库存同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_stock extends ome_rpc_request {
    
    /**
     * 清除预占库存
     * @access public
     * @param int $order_id 订单主键ID
     * @return boolean
     */
    public function clean_freeze($order_id){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id');
            $params['tid'] = $order['order_bn'];

            $callback = array(
                'class' => 'ome_rpc_request_stock',
                'method' => 'clean_freeze_callback',
            );
        
            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')清除预占库存(订单号:'.$order['order_bn'].')';
            }else{
                $title = '清除预占库存';
            }

            $this->request('store.trade.item.freezstore.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }
    
    function clean_freeze_callback($result){
        return $this->callback($result);
    }
    
    /**
     * 批量更新库存
     * @access public
     * @params array $stocks 待更新库存货号，多维数组
     * @params int $shop_id 前端店铺ID
     * @params string $shop_type 店铺类型
     * @return boolean
     */
    public function stock_update($stocks,$shop_id,$shop_type=''){

        if(!empty($stocks)){
            
            //保存库存同步管理日志
			$oApiLogToStock = kernel::single('ome_api_log_to_stock');
			$oApiLogToStock->save($stocks,$shop_id);
            
            //待更新库存BN
            $params['list_quantity'] = json_encode($stocks);
            //附加参数
            $addon = array('all_list_quantity'=>json_encode($stocks));
            
            $callback = array(
                'class' => 'ome_rpc_request_stock',
                'method' => 'stock_update_callback',
                'shop_id' => $shop_id,
            );

            if($shop_id){
                $shop_info = app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '批量更新店铺('.$shop_info['name'].')的库存(共'.count($stocks).'个)';
            }else{
                $title = '更新库存';
            }
            $api_name = 'store.items.quantity.list.update';
            $return = $this->request($api_name,$params,$callback,$title,$shop_id,10,false,$addon);
            if ($return !== false){
                app::get('ome')->model('shop')->update(array('last_store_sync_time'=>time()),array('shop_id'=>$shop_id));
            }            
        }else{
            return false;
        }
    }
    
    /**
     * 批量更新库存异步响应处理
     * 如果失败了，则更新提交参数，把失败的再执行一次
     * @access public
     * @params array $result 请求结果数据
     * @return array('rsp'=>$rsp,'res'=>$res,'msg_id'=>$log_detail['msg_id']);
     */
    public function stock_update_callback($result){ 
        $callback_params = $result->get_callback_params();
        $status = $result->get_status();
        $res = $result->get_result();
        $data = $result->get_data();
        
        $log_id = $callback_params['log_id'];
        $oApi_log = &app::get('ome')->model('api_log');

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
        $addon['error_lv'] = $data['error_level'];//错误等级
        if (!is_array($err_item_bn)){
            $err_item_bn = json_decode($data['error_response'],true);
        }
        
        $log_info = $oApi_log->dump($log_id);
        $log_params = unserialize($log_info['params']);

        $itemsnum = json_decode($log_params[1]['list_quantity'],true);

        $new_itemsnum = array();
        foreach($itemsnum as $k=>$v){
            if(in_array($v['bn'],$err_item_bn)){
                $new_itemsnum[] = $v;
            }
        }
        //当返回失败且BN为空时不更新list_quantity
        if ($api_status != 'fail' || $new_itemsnum){
            $log_params[1]['list_quantity'] = json_encode($new_itemsnum);
        }else{
            $new_itemsnum = $itemsnum;
        }
        
        if(isset($log_params)){
            $oApi_log->update_log($log_id,$res,$api_status,$log_params,$addon);
        }else{
            $oApi_log->update_log($log_id,$res,$api_status,null,$addon);
        }
        $log_detail = $oApi_log->dump($log_id, 'msg_id,params');
        
        //更新库存同步管理的执行状态
		$oApiLogToStock = kernel::single('ome_api_log_to_stock');
		$oApiLogToStock->save_callback($itemsnum,'success',$callback_params['shop_id'],$res,$log_detail);
        $oApiLogToStock->save_callback($new_itemsnum,'fail',$callback_params['shop_id'],$res,$log_detail);
        
        return array('rsp'=>$rsp,'res'=>$res,'msg_id'=>$log_detail['msg_id']);
    }
}