<?php
class giftcard_wechat_request_order extends giftcard_wechat_request
{	
	public function getCardCodeInfo($order,&$msg='',&$begin_time='',&$end_time=''){
		$post['code']=$order['card_code'];
		$post['card_id']=$order['card_id'];
		//时间判断区间 如果是设置领取时间开始算则time() 如果设置固定则createtime
		if($result=$this->post(2,'/card/code/get',json_encode($post),'code_get',$order['order_bn'],$msg)){//查询成功 比较时间 金额
			if($result['card']['begin_time']<=time()&&time()<=$result['card']['end_time']){
				$begin_time=$result['card']['begin_time'];
				$end_time=$result['card']['end_time'];
				return true;//能够核销
			}
			
		}
		if($msg!="timeout"){
			$msg='礼品卡过期或超额';
		}else{
			$msg='请求超时, 请重试';
		}
		return false;
		
	}
	
	public function getExistOrderId($order){
		$post['code']=$order['card_code'];
		$post['card_id']=$order['card_id'];
		$result=$this->post(2,'/card/code/get',json_encode($post),'code_get',$order['card_code'],$msg);
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
		$ojbCard=kernel::single("giftcard_mdl_cards");
		$arrOrders=array();
		//Sku+卡劵Code拼接成OMS订单号
		if(!$order_bn=$objLibCardOrder->getOrderBn($order))return true;
			
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
		
		$transaction = $oObj->db->beginTransaction();//事物开始
		
		$iorder=array();
		$card_code='';
		$card_id='';
		$i=1;
		$card_setting    = app::get('giftcard')->getConf('giftcard_setting');
		$arrRefuseCode=explode(',',$card_setting['refuse_code']);
		
	 	foreach($order['card_list'] as $item){
			$card_code=$card_id='';
			$price=0;
			
			$arrProduct=$pObj->db->select("SELECT g.deadline,g.convert_type,p.goods_id,p.product_id,p.price,p.bn,p.name FROM sdb_ome_products p LEFT JOIN sdb_ome_goods g ON g.goods_id=p.goods_id WHERE g.card_id='".$item['card_id']."'");
			$arrProduct=$arrProduct[0];
			
			if (empty($arrProduct['product_id'])){
				$oObj->db->rollBack();
				return true;
			}
			
			$card_code=$item['code'];
			
			if(in_array($card_code,$arrRefuseCode)){//测试code不进生产环境
				$oObj->db->rollBack();
				return true;
			}
		
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
			//多卡存入cards表
			$arrCards=array();
			//$arrCards['order_bn']=$order_bn."-".$i;//多卡
			$arrCards['p_order_bn']=$order_bn;
			$arrCards['order_bn']=$order_bn;//单卡
			$arrCards['wx_order_bn']=$order['order_id'];
			$arrCards['status']='normal';
			$arrCards['card_type']='online';
			$arrCards['convert_type']=$arrProduct['convert_type'];
			$arrCards['card_code']=$card_code;
			$arrCards['old_card_code']=$card_code;
			$arrCards['price']=$price;
			$arrCards['card_id']=$card_id;
			$arrCards['createtime']=time();
			if(!$ojbCard->save($arrCards)){
				$oObj->db->rollBack();
				return false;
			}
			$i++;
		}

		$arrOrders['order_objects']=$iorder['order_objects'];
		$arrOrders['cost_item']=round($order['total_price']/100,2);
		$arrOrders['total_amount']=round($order['total_price']/100,2);
		$arrOrders['createtime']=$order['create_time'];
		$arrOrders['itemnum']=1;
		$arrOrders['order_bn']=$order_bn;
		//订单表需要保留的三个字段
		$arrOrders['wx_order_bn']=$order['order_id'];
		$arrOrders['wx_source']=$order['outer_str'];
		$arrOrders['wechat_openid']=$order['open_id'];
		//店铺
		$arrShop=$sObj->getList("shop_id",array('shop_type'=>'cardshop'));
		if(empty($arrShop)){
			$oObj->db->rollBack();
			return false;
		}
		$arrOrders['shop_id']=$arrShop[0]['shop_id'];
		$arrOrders['shop_type']='cardshop';
		$arrOrders['order_refer_source']='minishop';
		
		//记录备注 禁止自动分派
		/*$c_memo = array('op_name'=>'系统', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'卡劵:'.$card_code);
        $tmp[]  = $c_memo;
        $arrOrders['custom_mark']  = serialize($tmp);*/
		//$arrOrders['mark_type']    = 'b1';
		
		//payment		
		$pay_bn=$objPayment->getList('id,pay_bn,custom_name',array('pay_bn'=>'wxpayjsapi'));//支付方式
		if(empty($pay_bn)){
			$oObj->db->rollBack();
			return false;
		}else{
			$arrOrders['pay_bn']='wxpayjsapi';
			$arrOrders['payment']=$pay_bn['0']['custom_name'];
			$arrOrders['pay_id']=$pay_bn['0']['id'];
		}
		$arrOrders['trade_no']=$order['trans_id'];
		$arrOrders['paytime']=$order['pay_finish_time'];
		
		if(!$oObj->create_order($arrOrders)){
			$oObj->db->rollBack();
			return false;
		}
		if(!$this->do_payorder($arrOrders)){
			$oObj->db->rollBack();//保存失败
			return false;
		}
		
		if(!$ojbCard->update(array("p_order_id"=>$arrOrders['order_id']),array("wx_order_bn"=>$order['order_id']))){
			$oObj->db->rollBack();
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
		$orderdata['status']='finish';
		$orderdata['archive']='1';
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
