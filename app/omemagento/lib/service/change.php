<?php
class omemagento_service_change{
	
	public function __construct(&$app){
        $this->app = $app;
		$this->request = kernel::single('omemagento_service_request');
	}
	//更新换货单状态
	public function updateStatus($params){
		$this->request->do_request('exchangeOrder',$params);
	}
	
	//获取可换货商品
	public function getChangeSku($bn){
		$params=array();
		$params['sku']=$bn;//$bn;
		if($response=$this->request->do_request('getAllExchangeSku',$params)){
			return $response;
		}
		return false;
	}

	//新增换货单
	public function sendChangeOrder($data){
		$objOrder=app::get("ome")->model("orders");
		$arrOrder=array();
		$arrOrder=$objOrder->getList("order_bn",array('order_id'=>$data['order_id']));
		$params['order_bn']=$arrOrder[0]['order_bn'];
		$params['exchange_no']=$data['exchange_no'];
		$params['items']=$data['items'];
		$params['reason']='';
		$this->request->do_request('exchange',$params);
	}

}