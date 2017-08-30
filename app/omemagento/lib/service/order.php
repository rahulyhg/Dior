<?php
/**
 * 发货同步到AX
 * @author lijun
 * @package omeftp_service_order
 *
 */
class omemagento_service_order{
	
	public function __construct(&$app){
        $this->app = $app;
		$this->request = kernel::single('omemagento_service_request');
	}

	public  function update_status($order_bn,$status,$tracking_code='',$event_time='',$reship_items=''){
		if($reship_items){
			$params = array('order_id'=>$order_bn,'status'=>$status,'tracking_code'=>$tracking_code,'event_time'=>date('Y-m-d H:i:s',time()),'refund_info'=>$reship_items);
		}else{
			if(!$event_time){
				$params = array('order_id'=>$order_bn,'status'=>$status,'tracking_code'=>$tracking_code,'event_time'=>date('Y-m-d H:i:s',time()));
			}else{
				$params = array('order_id'=>$order_bn,'status'=>$status,'tracking_code'=>$tracking_code,'event_time'=>date('Y-m-d H:i:s',$event_time));
			}
		}
		if($status=='return_required'){
			$params['is_refund_new'] = 1;
		}
		if($status=='refunding'||$status=='refund_required'){
			$params['is_refund_new'] = 0;
		}
		$this->request->do_request('order',$params);
	}

	public  function update_status_test($order_bn,$status,$tracking_code='',$event_time='',$reship_items=''){
		if($reship_items){
			$params = array('order_id'=>$order_bn,'status'=>$status,'tracking_code'=>$tracking_code,'event_time'=>date('Y-m-d H:i:s',time()),'refund_info'=>$reship_items);
		}else{
			if(!$event_time){
				$params = array('order_id'=>$order_bn,'status'=>$status,'tracking_code'=>$tracking_code,'event_time'=>date('Y-m-d H:i:s',time()));
			}else{
				$params = array('order_id'=>$order_bn,'status'=>$status,'tracking_code'=>$tracking_code,'event_time'=>date('Y-m-d H:i:s',$event_time));
			}
		}
		if($status=='return_required'){
			$params['is_refund_new'] = 1;
		}
		if($status=='refunding'||$status=='refund_required'){
			$params['is_refund_new'] = 0;
		}

		$this->request->do_request_test('order',$params);
	}

}