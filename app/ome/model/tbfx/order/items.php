<?php
class ome_mdl_tbfx_order_items extends dbeav_model{

	public function getOrderByOrderId($data){
		$filter = array('item_id'=>$data['item_id'],'obj_id'=>$data['obj_id']);
		return $this->getList('buyer_payment',$filter);
	}

	public function getCostitemByOrderId($order_id){
		$filter = array('order_id'=>$order_id);
		return $this->getList('SUM(buyer_payment) as cost_items',$filter);		
	}
}