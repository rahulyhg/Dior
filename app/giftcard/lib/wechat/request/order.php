<?php
class giftcard_wechat_request_order extends giftcard_wechat_request
{	
	public function getCardCodeInfo($order,&$msg=''){
		$post['code']=$order['card_code'];
		$post['card_id']=$order['card_id'];
		if($result=$this->post(2,'/card/code/get',json_encode($post),'code_get',$order['order_bn'],$msg)){//查询成功 比较时间 金额
			if($result['card']['begin_time']<=$order['createtime']&&$order['createtime']<=$result['card']['end_time']&&$result['can_consume']=="1"){
				return true;//能够核销
			}
			
		}
		
		if($msg!="timeout"){
			$msg='礼品卡过期';
		}else{
			$msg='请求超时, 请重试';
		}
		return false;
		
	}
	
	public function getExistOrderId($order){
		$post['code']=$order['trade_no'];
		$post['card_id']=$order['card_id'];
		$result=$this->post(2,'/card/code/get',json_encode($post),'code_get',$order['trade_no'],$msg);
		if(!empty($result['order_id'])){
			return $result['order_id'];
		}
		return false;
	}
	
	public function consume($order,&$msg=''){
		$post['code']=$order['card_code'];
		$post['card_id']=$order['card_id'];
		if($result=$this->post(2,'/card/code/consume',json_encode($post),'conusme',$order['order_bn'],$msg)){
			return true;
		}
		if($msg!="timeout"){
			$msg='礼品卡核销失败';
		}else{
			$msg='请求超时, 请重试';
		}
		return false;
	}
	
	public function getOrders($order_id=''){
		$objGiftOrder=$this->app->model('orders');
		
		if(!empty($order_id)){
			$post=array();
			$post['order_id']=$order_id;
			if($result=$this->post(2,'/card/giftcard/order/get',json_encode($post),'get',$post['order_id'])){
				if($this->addOrder($result['order'])){//update 1
					$objGiftOrder->db->exec("UPDATE sdb_giftcard_orders SET status='1' WHERE OrderId='".$order_id."'");
				}
			}
		}else{
			$arrOrders=$objGiftOrder->getList("OrderId",array('status'=>'0'));
			if(empty($arrOrders))return true;
			
			foreach($arrOrders as $order_id){
				$post=array();
				$post['order_id']=$order_id['OrderId'];
				if($result=$this->post(2,'/card/giftcard/order/get',json_encode($post),'get',$post['order_id'])){
					if($this->addOrder($result['order'])){//update 1
						$objGiftOrder->db->exec("UPDATE sdb_giftcard_orders SET status='1' WHERE OrderId='".$order_id['OrderId']."'");
					}
				}
			}
		}
	}
	
	public function addOrder($order){
		$oObj = kernel::single("ome_mdl_orders");
		$mObj = kernel::single("ome_mdl_members");
		$pObj = kernel::single("ome_mdl_products");
		$sObj=  kernel::single("ome_mdl_shop");
		$objPayment = kernel::single("ome_mdl_payment_cfg");
		$objLibCardOrder=kernel::single("giftcard_order");
		$arrOrders=array();
		//Sku+卡劵Code拼接成OMS订单号
		if(!$order_bn=$objLibCardOrder->getOrderBn($order))return false;
		
		$nickname=trim($objLibCardOrder->filterNickName($order['nickname']));
		if(empty($nickname))$nickname='WX_'.time();
		
		//检测是否已经建过
		$arrIsExisitOrder=$oObj->getList("order_bn",array('order_bn'=>$order_bn,'shop_type'=>'cardshop'));
		$arrIsExisitOrder=$arrIsExisitOrder[0];
		if(!empty($arrIsExisitOrder))return true;
		
		//创建会员
		$member = $mObj->dump(array('uname'=>$nickname),'*');
		if (!$member){
			$member['account']['uname']=$nickname;
			if (!$mObj->save($member)){
				return false;
			}
		}
		$arrOrders['member_id']=$member['member_id'];
		$arrOrders['wechat_openid']=$order['open_id'];
		
		$iorder=array();
		$card_code='';
		$card_id='';
	 	foreach($order['card_list'] as $item){
			
			$arrProduct=$pObj->db->select("SELECT p.goods_id,p.product_id,p.price,p.bn,p.name FROM sdb_ome_products p LEFT JOIN sdb_ome_goods g ON g.goods_id=p.goods_id WHERE g.card_id='".$item['card_id']."'");
			$arrProduct=$arrProduct[0];
			
			if (empty($arrProduct['product_id'])){
				return false;
			}
			$card_code=$item['code'];
			$card_id=$item['card_id'];
			$price=round($item['price']/100,2);
			$iorder['order_objects'][] = array(
					'obj_type' =>'goods',
					'obj_alias' =>'goods',
					'goods_id' => $arrProduct['goods_id'],
					'bn' => $arrProduct['bn'],
					'name' =>$arrProduct['name'],
					'price' =>$price,
					'sale_price'=>$price,
					'amount' => $price,
					'quantity' => 1,
					'order_items' => array(
						array(
							'product_id' => $arrProduct['product_id'],
							'bn' =>$arrProduct['bn'],
							'name' => $arrProduct['name'],
							'price' =>$price,
							'sale_price'=>$price,
							'amount' =>$price,
							'quantity' =>1,
							'item_type' =>'product',
							)
					)
			);
		}

		$arrOrders['order_objects']=$iorder['order_objects'];
		$arrOrders['cost_item']=round($order['total_price']/100,2);
		$arrOrders['total_amount']=round($order['total_price']/100,2);
		$arrOrders['createtime']=$order['create_time'];
		$arrOrders['itemnum']=1;
		$arrOrders['order_bn']=$order_bn;
		$arrOrders['wx_order_bn']=$order['order_id'];
		$arrOrders['wx_source']=$order['outer_str'];
		$arrOrders['card_code']=$card_code;
		$arrOrders['card_id']=$card_id;
		//店铺
		$arrShop=$sObj->getList("shop_id",array('shop_type'=>'cardshop'));
		if(empty($arrShop)){
			return false;
		}
		$arrOrders['shop_id']=$arrShop[0]['shop_id'];
		$arrOrders['shop_type']='cardshop';
		
		//记录备注 禁止自动分派
		/*$c_memo = array('op_name'=>'系统', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'卡劵:'.$card_code);
        $tmp[]  = $c_memo;
        $arrOrders['custom_mark']  = serialize($tmp);*/
		//$arrOrders['mark_type']    = 'b1';
		
		//payment		
		$pay_bn=$objPayment->getList('id,pay_bn,custom_name',array('pay_bn'=>'wxpayjsapi'));//支付方式
		if(empty($pay_bn)){
			return false;
		}else{
			$arrOrders['pay_bn']='wxpayjsapi';
			$arrOrders['payment']=$pay_bn['0']['custom_name'];
			$arrOrders['pay_id']=$pay_bn['0']['id'];
		}
		$arrOrders['trade_no']=$order['trans_id'];
		$arrOrders['paytime']=$order['pay_finish_time'];
		
		$transaction = $oObj->db->beginTransaction();
		if(!$oObj->create_order($arrOrders)){
			$oObj->db->rollBack();
			return false;
		}
		if(!$this->do_payorder($arrOrders)){
			$oObj->db->rollBack();//保存失败
			return false;
		}
	
		$oObj->db->commit($transaction);
		return true;		
	}
	
	function do_payorder($iorder){
		$paymentCfgObj = kernel::single("ome_mdl_payment_cfg");
		$objOrder = kernel::single("ome_mdl_orders");
		$objMath = kernel::single('eccommon_math');
		$oPayment = kernel::single("ome_mdl_payments");
		
		$pay_money=$iorder['total_amount'];
		$orderdata = array();
		$orderdata['pay_status']='1';
		//直接放到 已处理订单中
		$orderdata['group_id']=1;
		$orderdata['op_id']=1;
		$orderdata['process_status']='splited';
		$orderdata['confirm']='Y';
		//$orderdata['status']='finish';
		$orderdata['order_id'] = $iorder['order_id'];
		$orderdata['pay_bn'] = $iorder['pay_bn'];
		$orderdata['payed'] = $objMath->number_plus(array(0,$pay_money));
		$orderdata['payed'] = floatval($orderdata['payed']);
		$orderdata['paytime'] = $iorder['paytime'];
		$orderdata['payment'] = $iorder['payment'];
		$pay_id=$iorder['pay_id'];

		$filter = array('order_id'=>$iorder['order_id']);
		if(!$objOrder->update($orderdata,$filter)){
			return false;
		}
	 	
		$payment_bn = $iorder['trade_no'];
		$paymentdata = array();
		$paymentdata['payment_bn'] = $payment_bn;
		$paymentdata['order_id'] = $iorder['order_id'];
		$paymentdata['shop_id'] =$iorder['shop_id'];
		$paymentdata['currency'] ='CNY';
		$paymentdata['money'] = $pay_money;
		$paymentdata['paycost'] = 0;
		$paymentdata['t_begin'] = $iorder['paytime'];
		$paymentdata['t_end'] = $iorder['paytime'];
		$paymentdata['trade_no'] = $iorder['trade_no'];
		$paymentdata['cur_money'] = $pay_money;
		if($pay_id=="3"){
			$paymentdata['pay_type'] = 'offline';
		}else{
			$paymentdata['pay_type'] = 'online';
		}
		$paymentdata['payment'] = $pay_id;
		$paymentdata['paymethod'] = $iorder['payment'];
		$paymentdata['ip'] = kernel::single("base_request")->get_remote_addr();
		$paymentdata['status'] = 'succ';
		$paymentdata['memo'] = '';
		$paymentdata['is_orderupdate'] = 'false';
		if(!$oPayment->create_payments($paymentdata)){
			return false;
		}
		
		return true;
	}
}