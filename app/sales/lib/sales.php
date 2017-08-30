<?php
class sales_sales{

	/**
	 * 根据发货单上的物流费用重新计算销售单的物流费用
	 *
	 * @return void
	 * @author 
	 **/
	public function update_deliverycost( $delivery_id ){

		$salesdata = array();
        $deliveryObj = &app::get('ome')->model('delivery');
        $salesObj = &app::get('ome')->model('sales');
        
        $orderIds = $deliveryObj->getOrderIdsByDeliveryIds( array($delivery_id) );

        foreach ($orderIds as $key => $orderId) {

        	$salesdata[$orderId] = $this->getSaledata( $orderId , $delivery_id );
        	
        	if( !$salesdata[$orderId] ){
 				return false;
        	}else{
        		kernel::single('ome_sales_logistics_fee')->calculate( $orderids , $salesdata );
        		$salesObj->save( $salesdata[$orderId] );
        	}
        }

		return true;
	}

	private function getSaledata( $order_id , $delivery_id ){
        
        $delivery_billObj = &app::get('ome')->model('delivery_bill');

        $deliveryObj = &app::get('ome')->model('delivery');

        $orderObj = &app::get('ome')->model('orders');

        $salesObj = &app::get('ome')->model('sales');

        $delivery_detail = $deliveryObj->dump(array('delivery_id'=>$delivery_id),'delivery_cost_actual');

		$sales = $salesObj->dump( array( 'order_id'=>$order_id , 'delivery_id'=>$delivery_id ) , 'sale_id' );

		$salesdata['delivery_cost_actual'] = $delivery_detail['delivery_cost_actual'];
		
		$orginal_data = $orderObj->dump(array('order_id'=>$order_id),'payed');
        
        $delivery_bill_infos = $delivery_billObj->getList('delivery_cost_actual',array('delivery_id'=>$delivery_id));

        //追加多包裹单的物流费用
        if($delivery_bill_infos){
            foreach($delivery_bill_infos as $k=>$delivery_bill_info){
                $delivery_detail['delivery_cost_actual'] += $delivery_bill_info['delivery_cost_actual'];
            }
        }

		$salesdata['payed'] = $orginal_data['payed'];
		$salesdata['sale_id'] = $sales['sale_id'];

		return $salesdata;
	}
}