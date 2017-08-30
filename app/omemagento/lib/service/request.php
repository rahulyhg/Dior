<?php
class omemagento_service_request{

	 public function __construct(&$app){
		  $this->app = $app;
		  $this->url = "http://dior:pcd-160308@www.dior.cn/beauty/zh_cn/store/oms_api/v1/";
		  $this->objBhc     = kernel::single('base_httpclient');
		  $this->log_mdl    = app::get('omemagento')->model('request_log');
	 }

	 public function do_request($method,$params){
		$url = $this->url.$method;

		$log_id = $this->write_log($method,$params);
		//$rs = $this->objBhc->post($url,json_encode($params));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		$rs = curl_exec($ch);
		curl_close($ch);
		$info = json_decode($rs,1);
		if ($info['success'] == true) {
			$logData = array(
					'log_id'=>$log_id,
					'status'=>'success',
				);
			$this->log_mdl->save($logData);
			return  true;
		}else{
			$logData = array(
					'log_id'=>$log_id,
					'status'=>'fail',
					'msg'=>$info['message'],
				);
			$this->log_mdl->save($logData);
			return false;
		}
	 }

	 public function retry_request($method,$params,$log_id,$retry_nums){
		 $url = $this->url.$method;

		 if($params['status']=='return_required'){
			$url = "http://dior:pcd-160308@www.dior.cn/beauty/zh_cn/store/oms_api/v1/recreateRMA";
		}

		//$rs = $this->objBhc->post($url,json_encode($params));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		$rs = curl_exec($ch);
		curl_close($ch);
	//	echo "<pre>";print_r($url);
	//	echo "<pre>";print_r($params);
		$info = json_decode($rs,1);
	//	echo "<pre>";print_r($info);exit;
		if ($info['success'] == true) {
			$logData = array(
					'log_id'=>$log_id,
					'status'=>'success',
				);
			$this->log_mdl->save($logData);
			return  true;
		}else{
			$logData = array(
					'log_id'=>$log_id,
					'status'=>'fail',
					'retry'=>$retry_nums+1,
					'msg'=>$info['message'],
				);
			$this->log_mdl->save($logData);
			return false;
		} 
	 }

	 public function write_log($method,$params){
		 if($method=='price'){
			 $msg = '更新商品价格';
		 }
		  if($method=='order'){
			 $msg = '更新订单状态';
		 }
		 if($method=='stock'){
			 $msg = '更新商品库存';
		 }
		$log_data = array(
				'original_bn'=>$params['order_id']?$params['order_id']:$params['sku'],
				'task_name'=>$msg,
				'status'=>'running',
				'worker'=>'omemagento_service_request',
				'original_params'=>array_merge($params,array('method'=>$method)),
				'sync'=>'true',
				//'msg'=>$params['order_id'],
				'log_type'=>'发起请求',
				'retry'=>0,
				'createtime'=>time(),
			);

		if($method=='order'){
			$log_id = $this->log_mdl->insert($log_data);
		}else{
			$log_id = $this->log_mdl->getList('log_id',array('original_bn'=>$params['sku'],'task_name'=>$msg));
			if($log_id){
				$log_data['log_id'] = $log_id[0]['log_id'];
				$this->log_mdl->save($log_data);
				$log_id = $log_id[0]['log_id'];
			}else{
				$log_id = $this->log_mdl->insert($log_data);
			}
		}
		return $log_id;
	 }

	 public function do_request_test($method,$params){
		$url = 'http://dior:pcd-160308@www.dior.cn/beauty/zh_cn/store/crm/test/rma';

	//	$log_id = $this->write_log($method,$params);
		//$rs = $this->objBhc->post($url,json_encode($params));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		$rs = curl_exec($ch);
		curl_close($ch);
		$info = json_decode($rs,1);
		echo "<pre>";print_r($params);
		echo "<pre>";print_r($info);exit;
		if ($info['success'] == true) {
			$logData = array(
					'log_id'=>$log_id,
					'status'=>'success',
				);
			$this->log_mdl->save($logData);
			return  true;
		}else{
			$logData = array(
					'log_id'=>$log_id,
					'status'=>'fail',
					'msg'=>$info['message'],
				);
			$this->log_mdl->save($logData);
			return false;
		}
	 }
}