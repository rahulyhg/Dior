<?php
class sales_aftersale_type_refund{

	function generate_aftersale($refund_id = null){
        
		if(empty($refund_id)) return false;
        
		$Oorder = &app::get('ome')->model('orders');
		$Oshop = &app::get('ome')->model('shop');
		$Omembers = &app::get('ome')->model('members');
		$Orefund_apply = &app::get('ome')->model('refund_apply');
		$payment_cfgObj = &app::get('ome')->model('payment_cfg');
		$Orefunds = &app::get('ome')->model('refunds');
		$Opam = &app::get('pam')->model('account');
        $Oreship = &app::get('ome')->model('reship');

		$apply_detail = $Orefund_apply->getList('*',array('apply_id'=>$refund_id),0,1);
        
		#如果memo有退换货单号 说明是这个退款单是从退换货产生 故走退换货生成售后单流程
        $is_archive = kernel::single('archive_order')->is_archive($apply_detail[0]['source']);
        if ($is_archive) {
            $Oorder = &app::get('archive')->model('orders');
        }
		preg_match_all('/\d{14,18}/',$apply_detail[0]['memo'],$output);

		$reship_bn = (count($output[0])>1)?$output[0][1]:$output[0][0];

		$reshipData = $Oreship->getList('reship_id',array('reship_bn'=>$reship_bn));

		#如果退款申请拒绝,并且不存在退换货单
        if(empty($reshipData[0]['reship_id']) && $apply_detail[0]['status'] == '3') return false;
        if($reshipData[0]['reship_id']){
            //return false;
            unset($apply_detail);
			$data = kernel::single('sales_aftersale_type_change')->generate_aftersale($reshipData[0]['reship_id']);
            return $data;		
		}
		$orderData = $Oorder->getList('member_id,order_bn,ship_mobile',array('order_id'=>$apply_detail[0]['order_id']),0,1);
		$shopData = $Oshop->getList('name,shop_bn',array('shop_id'=>$apply_detail[0]['shop_id']),0,1);
		$memberData = $Omembers->getList('uname',array('member_id'=>$orderData[0]['member_id']),0,1);
		$pamData = $Opam->getList('login_name',array('account_id'=>$apply_detail[0]['verify_op_id']),0,1);

		if($apply_detail[0]['payment']){
		  $payment_cfgObj = &app::get('ome')->model('payment_cfg');
		  $payment_cfg = $payment_cfgObj->dump(array('id'=>$apply_detail[0]['payment']), 'custom_name');
		  $paymethod = $payment_cfg['custom_name'];//支付方式  varchar
		}else{
		  $refund_detail = $Orefunds->getList('paymethod',array('refund_bn'=>$apply_detail[0]['refund_apply_bn']));
		  $paymethod = $refund_detail[0]['paymethod'];
		}
        
		$data['shop_id'] = $apply_detail[0]['shop_id'];
		$data['shop_bn'] = $shopData[0]['shop_bn'];
		$data['shop_name'] = $shopData[0]['name'];
		$data['order_id'] = $apply_detail[0]['order_id'];
		$data['order_bn'] = $orderData[0]['order_bn'];
		$data['return_apply_id'] = $refund_id;  
		$data['return_apply_bn'] = $apply_detail[0]['refund_apply_bn'];  
		$data['return_type'] = 'refund';
		$data['refundmoney'] = $apply_detail[0]['refunded'];
		$data['refund_apply_money'] = $apply_detail[0]['money'];
		$data['paymethod'] = $paymethod;
		$data['member_id'] = $orderData[0]['member_id'];
		$data['member_uname'] = $memberData[0]['uname'];
		$data['ship_mobile'] = $orderData[0]['ship_mobile'];
		$data['refundtime'] = $apply_detail[0]['last_modified'];
		$data['refund_op_id'] = $apply_detail[0]['verify_op_id']; #name
		$data['refund_op_name'] = $pamData[0]['login_name']; #name
		$data['aftersale_time'] = time();

		$data['refund_apply_time'] = $apply_detail[0]['create_time'];
		$data['pay_type'] = $apply_detail[0]['pay_type'];
		$data['account'] = $apply_detail[0]['account'];
		$data['bank'] = $apply_detail[0]['bank'];
		$data['pay_account'] = $apply_detail[0]['pay_account'];
        if ($is_archive) {
            $data['archive'] = '1';
        }
        return $data;
	}

}