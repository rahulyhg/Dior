<?php
class einvoice_request_invoice extends einvoice_request_abstract{
	
	public function __construct(&$app){
		parent::__construct($app);
		$this->log_mdl = app::get('einvoice')->model('request_log');

	}

	public function  invoice_request($order_id,$dataFun='getQueryInvoiceData'){
		if($dataFun=='getApplyInvoiceData'){
			$objOrders = app::get('ome')->model('orders');
			$order_info = $objOrders->getList('*',array('order_id'=>$order_id));
			if($order_info[0]['is_einvoice']=='false'){
				return true;
			}
		}
		$requst_data = $this->$dataFun($order_id);
	//	echo "<pre>";var_dump($requst_data);exit;
		if($requst_data){
			$res = $this->request($requst_data,$dataFun);
		}
		// @todo 假设返回(接口请求成功的情况下)结果为absract.php中的数组(待完善) @author payne.wu 2017-07-06
		//发票申请结果返回SUCCESS，结果存储到invoice表
		if(!empty($res) && $dataFun == 'getApplyInvoiceData'){
			$data = array(
				'order_id' => $order_id,
				'order_bn' => $requst_data['tranNo'],
				'invoice_id' => $res['id'],
				'invoiceCode' => $res['invoiceCode'],
				'invoiceNo' => $res['invoiceNo'],
				'invoiceTime' => $res['invoiceTime'],
				'pdfUrl' => $res['pdfUrl'],
				'invoice_type' => 'active',
			);
			
			$objInvoice = $this->app->model('invoice');
			$id = $objInvoice->getList('*',array('order_id'=>$order_id,'invoice_type'=>'ready'));
			$data['id'] = $id[0]['id'];
			$objInvoice->save($data);

			$objOrders = app::get('ome')->model('orders');
			$order_info = $objOrders->getList('order_bn,mark_text',array('order_id'=>$order_id));
			
			$memoArr = unserialize($order_info[0]['mark_text']);
			
			$c_memo = array('op_name'=>'system', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'电票开票成功');
			$memoArr[] = $c_memo;
			$oData['mark_text'] = serialize($memoArr);
			$oData['order_id'] = $order_id;
			$oData['einvoice_status']='succ';
			$objOrders->save($oData);
			kernel::single('omemagento_service_order')->send_einvoice($order_info[0]['order_bn']);
		}
		if(!empty($res) && $dataFun=='getCancelInvoiceData'){
			$objInvoice = $this->app->model('invoice');
			$id = $objInvoice->getList('*',array('order_id'=>$order_id,'invoice_type'=>'active'));
			$objInvoice->update(array('invoice_type'=>'cancel'),array('id'=>$id[0]['id']));
		}

	}

	public function getApplyInvoiceData($order_id){
		
		$objOrders = app::get('ome')->model('orders');
		$objOrderItem = app::get('ome')->model('order_items');
		$objInvoice = $this->app->model('invoice');
		$objReship = app::get('ome')->model('reship');

		$order_data = $objOrders->getList('*',array('order_id'=>$order_id));

		$order_data = $order_data[0];
		
		$reship_info = $objReship->getList('reship_id,return_type',array('order_id'=>$order_id,'status'=>'succ'));
		
		//MCD
		$mcdFlag=false;
		if(!empty($reship_info)){
			foreach($reship_info as $return){
				if($return['return_type']=="change"){
					$mcdFlag=true;
					break;
				}
			}
		}
		
		if($mcdFlag!==true){
			if($order_data['payed']<=0&&$order_data['ship_status']!=1){
				return false;
			}
		}
		
		$perfix = '';
		if(empty($reship_info)){
			$perfix = '';
		}else{
			$count = count($reship_info);
		}
		while(true){
			if($count>0){
				$perfix = '-R'.$count;
			}
			$hasApply = $objInvoice->getList('*',array('order_bn'=>$order_data['order_bn'].$perfix,'invoice_id|than'=>'0'));
			if($hasApply){
				$count = $count+1;
			}else{
				break;
			}
		}
		$requst_data = array(
				'tranNo'=>$order_data['order_bn'].$perfix,
				'occurTime'=>date('Y-m-d H:i:s',$order_data['createtime']),
				'title'=>$order_data['tax_company'],
				'storeNo'=>'storeNo',
				'posNo'=>'posNo',
				'mobile'=>'',
				'email'=>'',
				'taxIdentity'=>$order_data['taxpayer_identity_number'],
				'bankFullName'=>'',
				'bankAccount'=>'',
				'address'=>'',
				'phone'=>'',
			);
		
		$invoice_items = array();
		$order_items = $objOrderItem->getList('*',array('order_id'=>$order_id));
		foreach($order_items as $item){
			if($item['price']==0||$item['sale_price']==0){
				continue;
			}
			if($item['sendnum']-$item['return_num']>0){
				$invoice_items[] = array(
						'code'=>$item['bn'],
						'name'=>$item['name'],
						'spec'=>$item['name'],
						'unit'=>'件',
						'unitPrice'=>$item['price'],
						'num'=>$item['sendnum']-$item['return_num'],
						'price'=>$item['price']*($item['sendnum']-$item['return_num']),
						'discount'=>$item['ax_pmt_price']/$item['nums']*($item['sendnum']-$item['return_num']),
					);
			}
		}
		
		//MCD
		if($mcdFlag===true){
			
			$arrRelateOrderBn=array();
			$arrRelateOrderBn=$objOrders->getList("order_id,order_bn",array('relate_order_bn'=>$order_data['order_bn'],'createway'=>'after','is_mcd'=>'true','ship_status'=>'1'));
			
			if(!empty($arrRelateOrderBn)){
				foreach($arrRelateOrderBn as $relateOrder){
					$arrRelateOrderItems=array();
					$arrRelateOrderItems = $objOrderItem->getList('*',array('order_id'=>$relateOrder['order_id']));
					foreach($arrRelateOrderItems as $item){
						if($item['price']==0||$item['sale_price']==0){
							continue;
						}
						if($item['sendnum']-$item['return_num']>0){
							$invoice_items[] = array(
									'code'=>$item['bn'],
									'name'=>$item['name'],
									'spec'=>$item['name'],
									'unit'=>'件',
									'unitPrice'=>$item['price'],
									'num'=>$item['sendnum']-$item['return_num'],
									'price'=>$item['price']*($item['sendnum']-$item['return_num']),
									'discount'=>$item['ax_pmt_price']/$item['nums']*($item['sendnum']-$item['return_num']),
								);
						}
					}
				}
			}
			
		}
		
		if(empty($invoice_items)){
			return false;
		}
		$bcmoney = 0;
		if($reship_info){
			foreach($reship_info as $value){
				$bcmoney += $value['bcmoney'];
			}
		}
		if($bcmoney<=0){
			if($order_data['cost_freight']>0&&($order_data['cost_freight']-$order_data['pmt_order']>0)){
				$invoice_items[] = array(
						'code'=>'S0002',
						'name'=>'国内货物运输代理服务',
						'spec'=>'国内货物运输代理服务',
						'unit'=>'件',
						'unitPrice'=>$order_data['cost_freight']-$order_data['pmt_order'],
						'num'=>1,
						'price'=>$order_data['cost_freight']-$order_data['pmt_order'],
						'discount'=>0,
					);
			}
		}
		$requst_data['items'] = $invoice_items;
	//echo "<pre>";print_r(http_build_query($requst_data));exit;
	//	echo "<pre>";print_r(json_encode($requst_data));exit;
		return $requst_data;
	}


	public function getQueryInvoiceData($order_id){
		$objInvoice = $this->app->model('invoice');
		$objOrders = app::get('ome')->model('orders');

		$invoice_info = $objInvoice->getList('*',array('order_id'=>$order_id));
		$order_data = $objOrders->getList('order_bn',array('order_id'=>$order_id));

		$requst_data = array(
				'id'=>$invoice_info[0]['invoice_id'],
				'tranNo'=>$order_data[0]['order_bn'],
			);
		//echo "<pre>";print_r($requst_data);exit;
		return $requst_data;
		
		
	}
	
	public function getCancelInvoiceData($order_id){
		$objInvoice = $this->app->model('invoice');
		$invoice_info = $objInvoice->getList('*',array('order_id'=>$order_id,'invoice_type'=>'active'),0,1,'id desc');
		if(empty($invoice_info)){
			return false;
		}
		$requst_data = array(
				'id'=>$invoice_info[0]['invoice_id'],
				'invoiceCode'=>$invoice_info[0]['invoiceCode'],
				'invoiceNo'=>$invoice_info[0]['invoiceNo'],
				'tranNo'=>$invoice_info[0]['order_bn'],//非接口参数(写日志需要),url请求前会删掉
			);

		return $requst_data;
	}

	public function wrriteLog($data,$log_id=null){
		if(!$log_id){
			$log_id = $this->log_mdl->insert($data);
		}else{
			$this->log_mdl->update($data,array('log_id'=>$log_id));
		}
		return $log_id;
	}
}