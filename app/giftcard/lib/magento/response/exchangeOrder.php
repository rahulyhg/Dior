<?php
class giftcard_magento_response_exchangeOrder
{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function exchangeOrder($order){
		$ojbOrder=kernel::single("ome_mdl_orders");
		$objCard=kernel::single("giftcard_mdl_cards");
		//兼容版本
		$order['card_code']=empty($order['card_code'])?$order['trade_no']:$order['card_code'];
		$order['wechat_openid']=empty($order['wechat_openid'])?$order['account']['open_id']:$order['wechat_openid'];
		
		$arrCard_code=array();
		$arrCard_code=$objCard->getList("p_order_id,order_bn,wx_order_bn,card_code,card_id,status,price",array('card_code'=>$order['card_code'],'card_id'=>$order['card_id']),0,1);
		$arrCard_code=$arrCard_code[0];
		
		if($arrCard_code['price']<1){
			return array('status'=>'fail','msg'=>'系统维护，请稍后再试');
		}
		
		//第一张购卡订单不存在的两种情况
		//1.jinsocal延迟 或者微信超时 第一张订单还未拉取进来
		//2.转赠事件 未接受成功  导致传过来的code 跟原始code不同 
		if(empty($arrCard_code)){
			//抓取订单id 再次查看系统是否存在 如果存在 则是因为code发生了变化
			if($wx_order_bn=kernel::single("giftcard_wechat_request_order")->getExistOrderId($order)){
				$arrCard_code=$objCard->getList("p_order_id,wx_order_bn,order_bn,status",array('wx_order_bn'=>$wx_order_bn),0,1);
				$arrCard_code=$arrCard_code[0];
				
				if(empty($arrCard_code)){//如果为空那么确实是因为延迟还未拉取或丢失(微信订单号唯一)
					return array('status'=>'fail','msg'=>'5050:服务器忙，请休息5分钟，稍后再尝试。');
				}else{//不为空，则是因为卡劵code发生了变化 转赠事件未抓取成功
					//分单卡处理，多卡处理
					//单卡 直接update
					if(!$objCard->update(array("card_code"=>$order['card_code'],"card_id"=>$order['card_id']),array("wx_order_bn"=>$wx_order_bn))){
						return array('status'=>'fail','msg'=>'5050:服务器忙，请休息5分钟，稍后再尝试。');
					}
				}
			}else{
				return array('status'=>'fail','msg'=>'5010:卡劵异常，请联系客服。');
			}
		}
		
		if($arrCard_code['status']=="redeem"){
			return array('status'=>'fail','msg'=>'订单已存在','data'=>$arrCard_code['order_bn']);
		}
		
		//购卡订单是否申请了退款
		$arrOrder=$ojbOrder->getList('order_id,pay_status',array('order_id'=>$arrCard_code['p_order_id'],'shop_type'=>'cardshop'));
		$arrOrder=$arrOrder[0];
		if($arrOrder['pay_status']!="1"){
			return array('status'=>'fail','msg'=>'5070:此卡劵已在退款处理中，无法兑换。');exit();
		}
		
		$order['order_bn']=$arrCard_code['order_bn'];
		$order['wx_order_bn']=$arrCard_code['wx_order_bn'];
		return $this->add($order);
		exit();
	}
	
	public function add($order){	
		$objCard=kernel::single("giftcard_mdl_cards");
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
		$lettering='';
		
		foreach($order['products'] as $item){
			$amount=$pmt_price=$true_price=0;
			$goods_type=$product_type='';
			
			if($item['type']=="sales"){
				$goods_type="goods";
				$product_type="product";
			}else{
				$goods_type=$product_type='pkg';
			}
			
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
			
			//刻字处理
			if(!empty($item['lettering'])){
				$lettering.=str_replace(array("\r\n", "\r", "\n"),'\n',$item['lettering']);
			}
			
			$amount=$price*$item['num'];
			$pmt_price=$amount-$sale_price;
			
			$iorder['order_objects'][] = array(
					'obj_type' =>$goods_type,
					'obj_alias' =>$goods_type,
					'goods_id' => $arrProduct['goods_id'],
					'bn' => $item['bn'],
					'name' =>$item['name'],
					'price' =>$price,
					'pmt_price'=>$pmt_price,
					'sale_price'=>$sale_price,
					'amount' => $amount,
					'quantity' => $item['num'],
					'pkg_name'=>$item['pkg_name'],
					'pkg_id'=>$item['pkg_id'],
					'pkg_bn'=>$item['pkg_bn'],
					'pkg_price'=>$item['pkg_price'],
					'pkg_num'=>$item['pkg_num'],
					'order_items' => array(
						array(
							'product_id' => $arrProduct['product_id'],
							'bn' => $item['bn'],
							'name' => $item['name'],
							'price' =>$price,
							'true_price' =>$price,
							'pmt_price'=>$pmt_price,
							'sale_price'=>$sale_price,
							'amount' => $amount,
							'quantity' => $item['num'],
							'item_type' =>$product_type,
							'message1' =>empty($item['lettering'])?'':str_replace(array("\r\n", "\r", "\n"),'\n',urldecode($item['lettering'])),
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
		$arrOrders['createtime']=$order['createtime'];
		$arrOrders['itemnum']=$totalNums;
		$arrOrders['order_bn']=$order['order_bn'];
		$arrOrders['wx_order_bn']=$order['wx_order_bn'];
		//店铺
		$arrShop=$sObj->getList("shop_id",array('shop_type'=>'minishop'));
		$arrOrders['shop_id']=$arrShop[0]['shop_id'];
		$arrOrders['shop_type']='minishop';
		$arrOrders['order_refer_source']='minishop';
		
		$arrOrders['golden_box']=$order['golden_box']=="1"?true:false;//金色礼盒
		$arrOrders['ribbon_sku']=empty($order['ribbon_sku'])?'':$order['ribbon_sku'];//丝带
		
		if (!empty($lettering)){//标记为刻字订单
			$arrOrders['is_lettering']=true;
            $c_memo = array('op_name'=>'系统', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'刻字订单');
            $tmp[]  = $c_memo;
            $arrOrders['custom_mark']  = serialize($tmp);
            $tmp = null;
        }
		
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
		$arrOrders['wechat_openid']=$order['wechat_openid'];
	//	echo "<pre>222";print_r($order);print_r($arrOrders);exit(); 
		$transaction = $oObj->db->beginTransaction();
		if(!$oObj->create_order($arrOrders)){
			$oObj->db->rollBack();
			return array('status'=>'fail','msg'=>'5097: 订单兑换失败');exit();
		}
		
		if(!$this->do_payorder($arrOrders)){
			$oObj->db->rollBack();
			return array('status'=>'fail','msg'=>'5090: 订单兑换失败');exit();
		}
		
		//创建成功后 判断是否能核销此订单
		$arrOrders['card_code']=$order['card_code'];
		$arrOrders['card_id']=$order['card_id'];
		if(!kernel::single("giftcard_wechat_request_order")->getCardCodeInfo($arrOrders,$msg,$card_begin_time,$card_end_time)){
			$oObj->db->rollBack();
	        return array('status'=>'fail','msg'=>$msg);exit();
		}
		
		//将卡劵状态改为 核销
		$arrUpdateCard=array();
		$arrUpdateCard['status']='redeem';
		$arrUpdateCard['redeemtime']=time();
		$arrUpdateCard['form_id']=$order['form_id'];
		$arrUpdateCard['wechat_openid']=$order['wechat_openid'];
		$arrUpdateCard['order_id']=$arrOrders['order_id'];
		$arrUpdateCard['begin_time']=$card_begin_time;
		$arrUpdateCard['end_time']=$card_end_time;
		if(!$objCard->update($arrUpdateCard,array("card_code"=>$order['card_code']))){
			$oObj->db->rollBack();
			return false;
		}
		
		if(!kernel::single("giftcard_wechat_request_order")->consume($arrOrders,$msg)){
			$oObj->db->rollBack();
	        return array('status'=>'fail','msg'=>$msg);exit();
		}
		
		$oObj->db->commit($transaction);
		//模板消息
		$arrOrders['address_id']=$order['address_id'];
		$arrOrders['form_id']=$order['form_id'];
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
