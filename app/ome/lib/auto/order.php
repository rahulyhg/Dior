<?php
class ome_auto_order{
	
	public function getOrders(){

		$orderAuto = new omeauto_auto_combine();
        $orderGroup = $orderAuto->getBufferGroup();

		$params = $this->getParams($orderGroup);

		//订单预处理
        $preProcessLib = new ome_preprocess_entrance();
        $preProcessLib->process($params,$msg);

        //开始处理
        $result = $orderAuto->process($params);

		//echo "<pre>";print_r($orderGroup);exit;
	}

	public function getParams($data){
		$result = array();
		foreach($data as $key=>$val){
			$tmp = explode('||', $key);
			$result[] = array('idx'=>$tmp[1],'hash' => $tmp[0], 'orders' => explode(',', $val['orders']));
		}

		return $result;
	}
}