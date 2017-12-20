<?php
class giftcard_magento_response_search
{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function search($data){
		$open_id=$data['open_id'];
		$order_bn=$data['order_bn'];
		$order_type=$data['type'];
		
		$sql='';
		if(empty($order_bn)){
			if(empty($open_id)){
				return array('status'=>'fail','msg'=>'请传入open_id');exit();
			}
			if(!empty($order_type)){
				switch($order_type){
					case 'duili':
						$sql="o.wechat_openid='$open_id' AND o.shop_type='minishop'";
						break;
					default:
						return array('status'=>'fail','msg'=>'参数不正确');exit();
				}
			}else{
				$sql="o.wechat_openid='$open_id'";
			}
		}else{
			$sql="o.order_bn='$order_bn'";
		}
	
		$arrRetrun=array();
		$objOrder=kernel::single("ome_mdl_orders");
		
		$arrOrders=$objOrder->db->select("SELECT o.order_id,o.order_bn,o.logi_no,o.ship_addr,o.ship_name,o.ship_area,o.ship_mobile,o.wechat_openid,o.total_amount,o.ship_status,o.pay_status,o.route_status,o.process_status,o.createtime,o.paytime FROM sdb_ome_orders o WHERE $sql ORDER BY o.createtime DESC");
		
		if(empty($arrOrders))return array('status'=>'succ','msg'=>'succ','data'=>$arrRetrun);
		
		foreach($arrOrders as $k=>$order){
			$order_status=$magento_status=$refund_status=$delivery_time='';
			$process_status=$order['process_status'];
			$order_id=$order['order_id'];
			
			if($order['route_status']=="1"){
				$order_status='已签收';
			}elseif($order['ship_status']=="1"){
				$order_status='已发货';
			}elseif($order['pay_status']=="1"){
				$order_status='未发货';
			}else{
				$order_status='未支付';
			}
			if(!empty($order_bn)){
				//转化成magento需要的状态
				if($process_status=="splited"&&$order['ship_status']=="0"){
					$magento_status='sent_to_ax';
				}else if($process_status=="unconfirmed"&&$order['pay_status']=="1"){
					$magento_status='processing';
				}else if($order['route_status']=="1"){
					$magento_status='complete';
				}else if($process_status=="splited"&&$order['ship_status']=="1"){
					$sql='';
					$arrDelivery=array();
					$arrDelivery=$objOrder->db->select("SELECT delivery.delivery_time FROM sdb_ome_delivery_order o LEFT JOIN sdb_ome_delivery delivery ON o.delivery_id=delivery.delivery_id WHERE delivery.status='succ' AND o.order_id='$order_id' LIMIT 0,1");
					$delivery_time=$arrDelivery[0]['delivery_time'];
					$magento_status='shipped';
				}else if(($process_status=="unconfirmed"||$process_status=="confirmed")&&$order['pay_status']=="6"){
					$magento_status='refunding';
					$refund_status='0';
				}else if($order['pay_status']=="5"){
					$magento_status='refund_complete';
					$refund_status='1';
				}
			}
			
			$arrOrders[$k]['logi_name']='顺丰速递';
			$arrOrders[$k]['logi_no']=$order['logi_no'];
			$arrOrders[$k]['order_status']=$order_status;
			$arrOrders[$k]['magento_status']=$magento_status;
			$arrOrders[$k]['refund_status']=$refund_status;
			$arrOrders[$k]['delivery_time']=$delivery_time;
			$arrOrders[$k]['createtime']=date("Y-m-d H:i:s",$order['createtime']);
			$arrOrders[$k]['paytime']=date("Y-m-d H:i:s",$order['paytime']);
			$arrOrders[$k]['pay_bn']='微信支付';
			
			list($city1, $city2, $city3) = explode('/',$order['ship_area']);
			$arrOrders[$k]['ship_area']=substr($city1,9).'-'.$city2.'-'.substr($city3,0,strpos($city3,":"));
			
		//	unset($arrOrders[$k]['route_status']);
		//	unset($arrOrders[$k]['ship_status']);
		//	unset($arrOrders[$k]['pay_status']);
			unset($arrOrders[$k]['order_id']);
			
			$arrOrderObjects=$arrItems=$arrPkg=array();
			$arrOrderObjects=$objOrder->db->select("SELECT bn,price,quantity,name,pkg_bn,pkg_id,pkg_name,pkg_price,pkg_num FROM sdb_ome_order_objects WHERE order_id='$order_id'");
			$i=0;
			foreach($arrOrderObjects as $key=>$value){
				if(!empty($value['pkg_id'])){
					if(!isset($arrPkg[$value['pkg_id']])){
						$arrPkg[$value['pkg_id']]=1;
						$arrItems[$i]['bn']=$value['pkg_bn'];
						$arrItems[$i]['price']=$value['pkg_price'];
						$arrItems[$i]['nums']=$value['pkg_num'];
						$arrItems[$i]['name']=$value['pkg_name'];
					}else{
						continue;
					}
				}else{
					$arrItems[$i]['bn']=$value['bn'];
					$arrItems[$i]['price']=$value['price'];
					$arrItems[$i]['nums']=$value['quantity'];
					$arrItems[$i]['name']=$value['name'];
				}
				$i++;
			}
			
			$arrOrders[$k]['items']=$arrItems;
			//$arrOrders[$k]['items']=$objOrder->db->select("SELECT bn,price,nums,name,pkg_bn,pkg_id,pkg_name,pkg_price,pkg_num FROM sdb_ome_order_objects WHERE order_id='$order_id'");
		}
		return array('status'=>'succ','msg'=>'succ','data'=>$arrOrders);
	}
}
