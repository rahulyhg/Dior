<?php
class giftcard_magento_response_exchangeOrder
{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function exchangeOrder($order){
		$ojbOrder=kernel::single("ome_mdl_orders");
		$arrCard_code=$ojbOrder->getList("order_bn,wx_order_bn,card_code,card_id",array('card_code'=>$order['trade_no'],'card_id'=>$order['card_id'],'shop_type'=>'cardshop','pay_status'=>'1'));
		
		if(empty($arrCard_code)){
			//如果没有 通过接口查询 是否为再次赠送的情况
			$apiOrderId=kernel::single("giftcard_wechat_request_order")->getExistOrderId($order);
			$arrCard_code=$ojbOrder->getList("order_bn,wx_order_bn,card_id,pay_status",array('wx_order_bn'=>$apiOrderId,'shop_type'=>'cardshop'));
			
			if(empty($arrCard_code['0']['wx_order_bn'])){
				return array('status'=>'fail','msg'=>'服务器忙，请休息5分钟，稍后再尝试。');exit();
			}
			
			if($arrCard_code['0']['pay_status']!="1"){
				return array('status'=>'fail','msg'=>'此卡卷已在退款处理中，无法兑换。');exit();
			}
			
			$arrCard_code[0]['card_code']=$order['trade_no'];
			
		}
		$order_bn=$arrCard_code[0]['order_bn'];
		
		//再判断第二种是否存在 是否已支付
		$arrCheckMiniOrder=$ojbOrder->getList('order_id,order_bn,wx_order_bn,pay_status,member_id,createtime,total_amount,shop_id,card_code,card_id',array('order_bn'=>$order_bn,'shop_type'=>'minishop'));
		$arrCheckMiniOrder=$arrCheckMiniOrder[0];
		
		if(empty($arrCheckMiniOrder)){//如果不存在 直接新增
			$order['order_bn']=$order_bn;
			$order['card_code']=$arrCard_code[0]['card_code'];
			$order['card_id']=$arrCard_code[0]['card_id'];
			$order['wx_order_bn']=$arrCard_code[0]['wx_order_bn'];
			return $this->add($order);
		}else{
			if($arrCheckMiniOrder['pay_status']=="1"){//如果已支付 已存在 直接返回 ?
				return array('status'=>'fail','msg'=>'订单已存在','data'=>$order_bn);exit();
			}else{//已存在 未支付  先更新订单信息 再去查询是否能核销 
				$order['order_id']=$arrCheckMiniOrder['order_id'];
				$order['member_id']=$arrCheckMiniOrder['member_id'];
				if(!$this->needToUpdate($order,$msg)){
					return array('status'=>'fail','msg'=>$msg);exit();
				}
				
				if(!kernel::single("giftcard_wechat_request_order")->getCardCodeInfo($arrCheckMiniOrder,$msg)){
					return array('status'=>'fail','msg'=>$msg);exit();
				}
				
				if(!kernel::single("giftcard_wechat_request_order")->consume($arrCheckMiniOrder,$msg)){
					return array('status'=>'fail','msg'=>$msg);exit();
				}
		
				$arrPay=array();
				$arrPay['payment']='微信支付';
				$arrPay['pay_bn']='wxpayjsapi';
				$arrPay['trade_no']=time();
				$arrPay['order_id']=$order['order_id'];
				$arrPay['shop_id']=$arrCheckMiniOrder['shop_id'];
				$arrPay['total_amount']=$arrCheckMiniOrder['total_amount'];
				if(!$this->do_payorder($arrPay)){
					return array('status'=>'fail','msg'=>'5090: 订单兑换失败');exit();
				}
				//将第一张订单标记为已完成,并发送模板消息
				$this->updateCardOrderToFinish($order_bn);
				
				$order['wechat_openid']=$order['account']['open_id'];
				$order['order_bn']=$order_bn;
				$order['order_objects'][0]['name']=$order['products']['0']['name'];
				kernel::single("giftcard_wechat_request_message")->send($order);
				
				return array('status'=>'succ','msg'=>'订单兑换成功','data'=>$order_bn);exit();
				//echo "<pre>";print_r($arrCheckMiniOrder);print_r($order);exit();
			}
		}
	}
	
	public function updateCardOrderToFinish($order_bn){
		$oObj = kernel::single("ome_mdl_orders");
		$oObj->db->exec("UPDATE sdb_ome_orders SET process_status='splited',status='finish',confirm='Y',archive='1' WHERE order_bn='$order_bn' AND shop_type='cardshop'");
	}
	
	public function needToUpdate($order,&$msg){
		$oObj = kernel::single("ome_mdl_orders");
		$pObj = kernel::single("ome_mdl_products");
		if(!$address_id=$this->checkArea($order['address_id'])){
			$msg='5091: 订单兑换失败';
			return false;
		}
		$order_id=$order['order_id'];
		$member_id=$order['member_id'];
		
		if(count($order['products'])!="1"){
			$msg='5088: 订单兑换失败';
			return false;
		}
		
		//检测库存
		foreach($order['products'] as $item){
			$arrProduct=array();
			$arrProduct=$pObj->getList('goods_id,product_id,name,bn',array('bn'=>$item['bn']));
			$arrProduct=$arrProduct[0];
			//检测库存
			if(!$this->checkProductStore($arrProduct)){
				$msg='5089: 商品库存不足，请重新选择商品';
				return false;
			}
		}
		
		//修改商品 先针对单个商品做修改
		$need_update_name=$order['products'][0]['name'];
		$need_update_goods_id=$arrProduct['goods_id'];
		$need_update_product_id=$arrProduct['product_id'];
		$need_update_bn=$arrProduct['bn'];
		
		if(!$oObj->db->exec("UPDATE sdb_ome_order_objects SET goods_id='$need_update_goods_id',bn='$need_update_bn',name='$need_update_name' WHERE order_id='$order_id'")){
			$msg='5092: 订单兑换失败';
			return false;
		}
		
		if(!$oObj->db->exec("UPDATE sdb_ome_order_items SET product_id='$need_update_product_id',bn='$need_update_bn',name='$need_update_name' WHERE order_id='$order_id'")){
			$msg='5092: 订单兑换失败';
			return false;
		}
		
		
		$addr=$order['consignee']['addr'];
		$account_name=$order['account']['name'];
		$mobile=$order['account']['mobile'];
		$wechat_openid=$order['account']['open_id'];
		$zip=$order['consignee']['zip'];
		if($oObj->db->exec("UPDATE sdb_ome_orders SET ship_name='$account_name',ship_area='$address_id',ship_addr='$addr',ship_mobile='$mobile',wechat_openid='$wechat_openid',ship_zip='$zip' WHERE order_id='$order_id'")){
			if($oObj->db->exec("UPDATE sdb_ome_members SET name='$account_name',uname='$mobile',mobile='$mobile',area='$address_id' WHERE member_id='$member_id'")){
				return true;
			}
		}
		$msg='5092: 订单兑换失败';
		return false;
		
	}
	
	public function add($order){	
		
		$oObj = kernel::single("ome_mdl_orders");
		$mObj = kernel::single("ome_mdl_members");
		$pObj = kernel::single("ome_mdl_products");
		$sObj=  kernel::single("ome_mdl_shop");
		$ojbGiftOrder=kernel::single("giftcard_mdl_orders");
		$objPayment = kernel::single("ome_mdl_payment_cfg");
		$arrOrders=array();
		
		//check地区
		if(!$address_id=$this->checkArea($order['address_id'])){
			return array('status'=>'fail','msg'=>'5091: 订单兑换失败');exit();
		}
		
		//shipping
		$arrOrders['shipping']['shipping_name']='快递';
		$arrOrders['shipping']['is_protect']='false';
		$arrOrders['shipping']['cost_protect']='0';
		$arrOrders['shipping']['is_cod']='false';
		$arrOrders['shipping']['cost_shipping']=$order['cost_shipping'];
		
		//创建会员
		$member_uname=$order['account']['mobile'];
		$member = $mObj->dump(array('uname'=>$member_uname),'*');
		if (!$member){
			$member['account']['uname']=$member_uname;
			$member['contact']['phone']['mobile']=$order['account']['mobile'];
			$member['contact']['name']=$order['account']['name'];
			$member['contact']['area']=$address_id;
			if (!$mObj->save($member)){
				return array('status'=>'fail','msg'=>'5093: 订单兑换失败');exit();
			}
		}
		
		//consignee
		$arrOrders['member_id']=$member['member_id'];
		$arrOrders['consignee']=$order['consignee'];
		$arrOrders['consignee']['r_time']='任意日期 任意时间段';
		$arrOrders['consignee']['area']=$address_id;
		//$arrOrders['consignee']['mobile']=$order['consignee']['telephone'];
		
		$iorder=array();
		$totalNums=0;
		$cost_item=0;
		$total_goods_pmt=0;
		$total_amount=0;
		$is_price_abnormal='false';
	 	
		if(count($order['products'])!="1"){
			return array('status'=>'fail','msg'=>'5088: 订单兑换失败');exit();
		}
		
		foreach($order['products'] as $item){
			$amount=0;
			$pmt_price=0;
			$sale_price=round($item['sale_price'],2);
			$price=round($item['price'],2);
			
			$arrProduct=$pObj->getList('goods_id,product_id,price',array('bn'=>$item['bn']));
			$arrProduct=$arrProduct[0];
			
			if(empty($arrProduct['product_id'])){
				return array('status'=>'fail','msg'=>'5094: 商品不存在');exit();
			}
			//检测库存
			if(!$this->checkProductStore($arrProduct)){
				return array('status'=>'fail','msg'=>'5089: 商品库存不足，请重新选择商品');exit();
			}
			
			$amount=$price*$item['num'];
			$pmt_price=$amount-$sale_price;
			
			$iorder['order_objects'][] = array(
					'obj_type' =>'goods',
					'obj_alias' =>'goods',
					'goods_id' => $arrProduct['goods_id'],
					'bn' => $item['bn'],
					'name' =>$item['name'],
					'price' =>$price,
					'pmt_price'=>$pmt_price,
					'sale_price'=>$sale_price,
					'amount' => $amount,
					'quantity' => $item['num'],
					'order_items' => array(
						array(
							'product_id' => $arrProduct['product_id'],
							'bn' => $item['bn'],
							'name' => $item['name'],
							'price' =>$price,
							'pmt_price'=>$pmt_price,
							'sale_price'=>$sale_price,
							'amount' => $amount,
							'quantity' => $item['num'],
							'item_type' =>'product',
							)
					)
			);
			$totalNums=$totalNums+$item['num'];
			$cost_item=$cost_item+$amount;
			$total_goods_pmt=$total_goods_pmt+$pmt_price;//商品总优惠
		}
		//判断下金额
		$pay=round($order['pay'],2);
		if(round($cost_item-$order['pmt_order']+$order['cost_shipping'],2)!=round($pay,2)){//商品总金额+运费-免减的运费
			return array('status'=>'fail','msg'=>'5095: 订单兑换失败');exit();
		}
		$arrOrders['order_objects']=$iorder['order_objects'];
		$arrOrders['cost_item']=$cost_item;
		$arrOrders['total_amount']=$pay;
		$arrOrders['pmt_goods']=$total_goods_pmt;
		$arrOrders['pmt_order']=$order['pmt_order']-$total_goods_pmt;
		$arrOrders['wechat_openid']=$order['account']['open_id'];
		
		//店铺
		$arrShop=$sObj->getList("shop_id",array('shop_type'=>'minishop'));
		$arrOrders['shop_id']=$arrShop[0]['shop_id'];
		$arrOrders['shop_type']='minishop';
		
		$arrOrders['createtime']=$order['createtime'];
		$arrOrders['itemnum']=$totalNums;
		$arrOrders['order_bn']=$order['order_bn'];
		$arrOrders['wx_order_bn']=$order['wx_order_bn'];
		$arrOrders['card_code']=$order['card_code'];
		$arrOrders['card_id']=$order['card_id'];
		$arrOrders['form_id']=$order['form_id'];
		
		//记录备注 禁止自动分派
		/*$c_memo = array('op_name'=>'系统', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'卡劵:'.$order['trade_no'].'所兑换订单');
        $tmp[]  = $c_memo;
        $arrOrders['mark_text']  = serialize($tmp);*/
		
		//payment		
		$pay_bn=$objPayment->getList('id,pay_bn,custom_name',array('pay_bn'=>'wxpayjsapi'));//支付方式
		if(empty($pay_bn)){
			return array('status'=>'fail','msg'=>'5096: 订单兑换失败');exit();
		}else{
			$arrOrders['pay_bn']='wxpayjsapi';
			$arrOrders['payment']=$pay_bn['0']['custom_name'];
			$arrOrders['pay_id']=$pay_bn['0']['id'];
		}
		$arrOrders['paytime']=$order['createtime'];
		
		$transaction = $oObj->db->beginTransaction();
		if(!$oObj->create_order($arrOrders)){
			$oObj->db->rollBack();
			return array('status'=>'fail','msg'=>'5097: 订单兑换失败');exit();
		}
		$oObj->db->commit($transaction);
		//创建成功后 判断是否能核销此订单
		if(!kernel::single("giftcard_wechat_request_order")->getCardCodeInfo($arrOrders,$msg)){
	        return array('status'=>'fail','msg'=>$msg);exit();
		}
		if(!kernel::single("giftcard_wechat_request_order")->consume($arrOrders,$msg)){
	        return array('status'=>'fail','msg'=>$msg);exit();
		}
		
		if(!$this->do_payorder($arrOrders)){
			return array('status'=>'fail','msg'=>'5090: 订单兑换失败');exit();
		}
		//将第一张订单标记为已完成并发送模板消息
		$this->updateCardOrderToFinish($order['order_bn']);
		
		$arrOrders['address_id']=$order['address_id'];
		kernel::single("giftcard_wechat_request_message")->send($arrOrders);
		
		return array('status'=>'succ','msg'=>'订单兑换成功','data'=>$arrOrders['order_bn']);exit();
		//echo "<pre>";print_r($order);print_r($arrOrders);exit();
	}
	
	function do_payorder($iorder){
		$paymentCfgObj = kernel::single("ome_mdl_payment_cfg");
		$objOrder = kernel::single("ome_mdl_orders");
		$objMath = kernel::single('eccommon_math');
		$oPayment = kernel::single("ome_mdl_payments");
		
		$pay_money=$iorder['total_amount'];
		$orderdata = array();
		$paytime=time();
		
		$orderdata['pay_status']='1';
			
		$orderdata['order_id'] = $iorder['order_id'];
		$orderdata['pay_bn'] = $iorder['pay_bn'];
		$orderdata['payed'] = $objMath->number_plus(array(0,$pay_money));
		$orderdata['payed'] = floatval($orderdata['payed']);
		$orderdata['paytime'] = $paytime;
		$orderdata['payment'] = $iorder['payment'];
		$pay_id=$iorder['pay_id'];

		$filter = array('order_id'=>$iorder['order_id']);
		if(!$objOrder->update($orderdata,$filter)){
			return false;
		}
	 	
		
		$payment_bn = $oPayment->gen_id();
		$paymentdata = array();
		$paymentdata['payment_bn'] = $payment_bn;
		$paymentdata['order_id'] = $iorder['order_id'];
		$paymentdata['shop_id'] =$iorder['shop_id'];//'295605e1914b3e33b650a9b9bd36c8ae';
		$paymentdata['currency'] ='CNY';
		$paymentdata['money'] = $pay_money;
		$paymentdata['paycost'] = 0;
		$paymentdata['t_begin'] = $paytime;//支付开始时间
		$paymentdata['t_end'] = $paytime;//支付结束时间
		$paymentdata['trade_no'] = $payment_bn;//支付网关的内部交易单号，默认为空
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
		$paymentdata['statement_status'] = 'true';//默认已对账
		if(!$oPayment->create_payments($paymentdata)){
			return false;
		}
		
		return true;
	}
	
	public function checkProductStore($arrProduct){
		$objBProduct=app::get("ome")->model("branch_product");
		$arrStore=$objBProduct->getList("store,store_freeze",array('product_id'=>$arrProduct['product_id'],'branch_id'=>'1'));
		$arrStore=$arrStore[0];
		if(empty($arrStore))return false;
		if($arrStore['store']-$arrStore['store_freeze']>=0){
			$num=$arrStore['store']-$arrStore['store_freeze'];
			$i_num=$this->getItemsStock($arrProduct['product_id']);
			if($num-$i_num-1>0){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
		
	}
	
	public function getItemsStock($product_id){
		$sql="SELECT sum(i.nums) as nums from sdb_ome_order_items i LEFT JOIN sdb_ome_orders o ON o.order_id=i.order_id WHERE i.product_id='".$product_id."' and o.process_status IN ('confirmed','unconfirmed')";
		$nums=app::get('ome')->model('orders')->db->select($sql);
		if(empty($nums[0]['nums'])||$nums[0]['nums']==""){
			return 0;
		}else{
			return $nums[0]['nums'];
		}
	}
	
	public function checkArea($address_id){
		//地区处理
		$mObj = kernel::single("ome_mdl_members");
		list($city1, $city2, $city3) = explode('-',$address_id);
		$isCity2=$mObj->db->select("SELECT region_id FROM sdb_eccommon_regions WHERE local_name='$city2' AND region_grade='2'");
		if(empty($isCity2['0']['region_id'])){
			return false;	
		}
		$isCity2=$isCity2['0']['region_id'];
		if(!empty($city3)){
			$isCity3=$mObj->db->select("SELECT local_name,region_id FROM sdb_eccommon_regions WHERE p_region_id='$isCity2' AND region_grade='3' AND local_name='$city3'");
			if(empty($isCity3['0']['region_id'])){
				return false;	
			}
			return 'mainland:'.$city1.'/'.$city2.'/'.$city3.':'.$isCity3['0']['region_id'];
		}else{
			return 'mainland:'.$city1.'/'.$city2.':'.$isCity2;
		}
	 
		
	}
}
