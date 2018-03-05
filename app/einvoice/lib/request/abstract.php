<?php
class einvoice_request_abstract{
	
	public $accessKey = 'inv15947ac5e8129d';
	public $secretKey = 'l5rem6nrwhwg8cwscc84wccwcsk888c';

	public $request_url = 'http://10.0.101.177/api';
	public $accessToken ;

	public function __construct(&$app){
		$this->app = $app;
		
		$setting = app::get('ome')->getConf('einvoice.setting');
		

		if($setting['request_url']){
			$this->request_url = $setting['request_url'];
		}else{
			//$this->request_url = 'http://api.qiakr.com/external/';
		}
		
		if($setting['accessKey']){
			$this->accessKey = $setting['accessKey'];
		}

		if($setting['secretKey']){
			$this->secretKey = $setting['secretKey'];
		}
		$accessToken = app::get('ome')->getConf('einvoice.accessToken');
		$refeshToken = app::get('ome')->getConf('einvoice.refeshToken');

		$this->accessToken = $accessToken;
		$this->refreshToken = $refeshToken;
		
	}

	public function getToken(){

		if($this->refreshToken === 100){//token刷新接口暂时屏蔽
			$params = array(
					'grantType'=>'refresh_token',
					'refreshToken'=>$this->refreshToken
				);
		}else{
			$params =array(
					'grantType'=>'authorization_code',
					'accessKey'=>$this->accessKey,
					'secretKey'=>$this->secretKey,
				);
		}
		$logData = array(
				'original_bn'=>'token',
				'task_name'=>'token',
				'status'=>'running',
				'original_params'=>serialize($params),
				'createtime'=>time(),
			);
		$log_id = $this->wrriteLog($logData);
		$url = $this->request_url.'/token';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$uheader = array(
            'Content-Type: application/json',
        );
		curl_setopt($ch, CURLOPT_HEADER, $uheader);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS,$params) ;
		$res = curl_exec($ch);
		curl_close($ch);
		//echo"<pre>";print_r($res);
		$res = substr($res,strpos(strval($res),"{"));
		$result = json_decode($res,1);

		if(isset($result['accessToken'])){
			app::get('ome')->setConf('einvoice.accessToken',$result['accessToken']);
			app::get('ome')->setConf('einvoice.refeshToken',$result['refreshToken']);

			$uLogData = array(
					'status'=>'success',
					'response'=>$res,
				);
			$this->wrriteLog($uLogData,$log_id);
		}else{
			$uLogData = array(
					'status'=>'fail',
					'response'=>$res,
				);
			$this->wrriteLog($uLogData,$log_id);
		}
		return true;
	}


	public function request($params,$funtion){
		if($funtion == "getApplyInvoiceData"){
			$method = 'applyInvoice';
		}elseif($funtion == "getQueryInvoiceData"){
			$method = 'queryInvoice';
		}else{
			$method = 'cancelInvoice';
			$original_bn = $params['tranNo'];
			unset($params['tranNo']);
		}
		$original_bn = isset($original_bn) ? $original_bn : $params['tranNo'];
		$logData = array(
			'original_bn'=>$original_bn,
			'task_name'=>$method,
			'status'=>'running',
			'original_params'=>serialize($params),
			'createtime'=>time(),
		);
		$log_id = $this->wrriteLog($logData);
		$url = $this->request_url.'/'.$method;
		//print_r($url);exit;
		//$data_string = json_encode($params);
		$headers = array(
			"Content-Type: application/json; charset=utf-8",    
		//	"Content-Length: " . strlen($data_string)  
		);
//echo "<pre>";print_r($params);exit;
		$accessToken = app::get('ome')->getConf('einvoice.accessToken');
		//$accessToken = 'grs3yj40doo4wgccocgog80gk0wsw4w';
		$token[] = 'Token:'.$accessToken;
	//	echo "<pre>";print_r($token);exit;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// post数据
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $token);
		// post的变量
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
		$res = curl_exec($ch);
		curl_close($ch);
		$res = substr($res,strpos(strval($res),"{"));
		
		//用于调试的返回信息(发票申请) @author payne.wu

		$result = json_decode($res,1);
		if(isset($result['invoiceNo'])){
			$uLogData = array(
				'status'=>'success',
				'response'=>$res,
			);
			$this->wrriteLog($uLogData,$log_id);
		}else{
			$uLogData = array(
				'status'=>'fail',
				'response'=>$res,
			);
			$this->wrriteLog($uLogData,$log_id);
			$this->error_handle($params,$result,$method);
			$result = array();
		}
		return $result;
	}


	public function error_handle($params,$res,$method){
		$objOrder = app::get('ome')->model('orders');
		$objEinvoice = app::get('einvoice')->model('invoice');
		if($method=='applyInvoice'){
			$order_bn = explode('-',$params['tranNo']);
			$order_bn = $order_bn[0];

			$order_info = $objOrder->getList('*',array('order_bn'=>$order_bn));
			$memoArr = unserialize($order_info[0]['mark_text']);
			$c_memo = array('op_name'=>'system', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'电票开票失败');
			$memoArr[] = $c_memo;
			$oData['mark_text'] = serialize($memoArr);
			$oData['order_id'] = $order_info[0]['order_id'];
			$oData['einvoice_status']='fail';
			//echo "<pre>";print_r($oData);exit;
			$objOrder->save($oData);
			
			$content = '订单：'.$order_info[0]['order_bn'].' 发票开票失败<br>'; 
			$content .= '报错信息：'.$res['message'].','.$res['detail'];
			kernel::single('einvoice_request_email_sendemail')->sendEmail($content);
		}

		if($method=='cancelInvoice'){
			$info = $objEinvoice->getList('*',array('invoice_id'=>$params['id']));

			$order_info = $objOrder->getList('*',array('order_id'=>$info[0]['order_id']));


			$memoArr = unserialize($order_info[0]['mark_text']);
			$c_memo = array('op_name'=>'system', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'发票红冲失败');
			$memoArr[] = $c_memo;
			$oData['mark_text'] = serialize($memoArr);
			$oData['order_id'] = $order_info[0]['order_id'];
			$objOrder->save($oData);
			
			$content = '订单：'.$info[0]['order_bn'].' 发票红冲失败<br>';  
			$content .= '报错信息：'.$res['message'].','.$res['detail'];



			kernel::single('einvoice_request_email_sendemail')->sendEmail($content);

		}
	}

}