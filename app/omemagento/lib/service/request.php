<?php
class omemagento_service_request{

	 public function __construct(&$app){
		  $this->app = $app;
		  $this->url = "http://dior:pcd-160308@www.dior.cn/beauty/zh_cn/store/oms_api/v1/";
		  $this->objBhc     = kernel::single('base_httpclient');
		  $this->log_mdl    = app::get('omemagento')->model('request_log');

		  $request_url = app::get('ome')->getConf('magento_setting');
		  if($request_url){
			   $this->url = $request_url;
		  }
	 }

	 public function do_request($method,$params){
		$url = $this->url.$method;
		
		if($method=="refundlog"){
			$log_dir=DATA_DIR.'/magento/'.$method.'/';
			if(!is_dir($log_dir))$res = mkdir($log_dir,0777,true);//创建日志目录
			error_log('Request:'.json_encode($params),3,$log_dir.date("Ymd").'zjrorder.txt');
		}
		
		$log_id = $this->write_log($method,$params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		if($method=="getAllExchangeSku"){
			curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
		}else{
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		$rs = curl_exec($ch);
		curl_close($ch);
		
		if($method=="refundlog"){
			error_log('Response:'.$rs,3,$log_dir.date("Ymd").'zjrorder.txt');
		}
		
		$info = json_decode($rs,1);
		
		if($method=="price"||$method=="stock"){
			foreach($info as $return){
				$sku=$return_status=array();
				$status='fail';
				
				$sku=array_keys($return);
				$return_status=array_values($return);
				
				if($return_status[0]=="1"){
					$status='success';
				}else{
					$status='fail';
				}
				$logData = array(
					'log_id'=>$log_id[$sku[0]],
					'status'=>$status,
				);
				$this->log_mdl->save($logData);
			}
			return true;
		}
		
		if ($info['success'] == true) {
			$logData = array(
					'log_id'=>$log_id,
					'status'=>'success',
				);
			$this->log_mdl->save($logData);
			if($method=="getAllExchangeSku"){
				return $info;
			}else{
				return true;
			}
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
		if($method=="price"||$method=="stock"){
			$return_status=array_values($info[0]);
			if($return_status[0]=="1"){
				$logData = array(
					'log_id'=>$log_id,
					'status'=>'success',
				);
				$this->log_mdl->save($logData);
				return true;
			}else{
				$logData = array(
					'log_id'=>$log_id,
					'status'=>'fail',
					'retry'=>$retry_nums+1,
					'msg'=>'error',
				);
				$this->log_mdl->save($logData);
				return false;
			}
		}
		
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
		 if($method=='refundlog'){
			 $msg = '更新退款单状态';
		 }
		 if($method=='exchangeOrder'){
			 $params['order_id']=$params['order_bn'];
			 $msg = '更新换货单状态';
		 }
		 if($method=='getAllExchangeSku'){
			 $msg = '获取可换货商品';
		 }
		 if($method=='exchange'){
			 $msg = '新增换货单';
			 $params['order_id']=$params['order_bn'];
		 }
		 
		if($method=="price"||$method=="stock"){
			$arrStoreLogsId=array();
			foreach($params as $k=>$sku){
				$log_data=$arrSaveLog=array();
				
				$arrSaveLog[]=$sku;
				
				$log_data = array(
					'original_bn'=>$sku['sku'],
					'task_name'=>$msg,
					'status'=>'running',
					'worker'=>'omemagento_service_request',
					'original_params'=>array_merge($arrSaveLog,array('method'=>$method)),
					'sync'=>'true',
					//'msg'=>$params['order_id'],
					'log_type'=>'发起请求',
					'retry'=>0,
					'createtime'=>time(),
				);
				$log_id = $this->log_mdl->getList('log_id',array('original_bn'=>$sku['sku'],'task_name'=>$msg));
				
				if($log_id){
					$log_data['log_id'] = $log_id[0]['log_id'];
					$this->log_mdl->save($log_data);
					$log_id = $log_id[0]['log_id'];
				}else{
					$log_id = $this->log_mdl->insert($log_data);
				}
				
				$arrStoreLogsId[$sku['sku']]=$log_id;
			}
			
			return $arrStoreLogsId;
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
		
		$log_id = $this->log_mdl->insert($log_data);

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