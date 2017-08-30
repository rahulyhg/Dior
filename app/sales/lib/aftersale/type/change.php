<?php
class sales_aftersale_type_change{

	function generate_aftersale($reship_id = null){
		if(empty($reship_id)) return false;

		$Omembers = &app::get('ome')->model('members');
		$Oorder = &app::get('ome')->model('orders');
		$Oshop = &app::get('ome')->model('shop');
		$Oreship = &app::get('ome')->model('reship');
		$Orefunds = &app::get('ome')->model('refunds');
		$Orefund_apply = &app::get('ome')->model('refund_apply');
		$Oreturn_product = &app::get('ome')->model('return_product');
		$Opam = &app::get('pam')->model('account');
        $Obranch = &app::get('ome')->model('branch');
        $Oprocess = &app::get('ome')->model('return_process_items');
		$reshipData = $Oreship->getList('*',array('reship_id'=>$reship_id));
        $Oorder_items = &app::get('ome')->model('order_items');
		$shopData = $Oshop->getList('name,shop_bn',array('shop_id'=>$reshipData[0]['shop_id']));
		$orderData = $Oorder->getList('member_id,order_bn',array('order_id'=>$reshipData[0]['order_id']));
        //判断是否来源为归档
        $is_archive = kernel::single('archive_order')->is_archive($reshipData[0]['source']);

        if ($is_archive) {
            $Oorder = &app::get('archive')->model('orders');
            $Oorder_items = &app::get('archive')->model('order_items');
            $orderData = $Oorder->getList('member_id,order_bn',array('order_id'=>$reshipData[0]['order_id'],'flag'=>'1'));
        }
		$shopData = $Oshop->getList('name,shop_bn',array('shop_id'=>$reshipData[0]['shop_id']));
		$memberData = $Omembers->getList('uname',array('member_id'=>$orderData[0]['member_id']));
		$pamDatas = $Opam->getList('login_name,account_id');

		foreach($pamDatas as $v){
		    $pam_data[$v['account_id']] = $v['login_name'];
		}

        unset($pamDatas);

        $branch_datas = $Obranch->getList('name,branch_id');

		foreach($branch_datas as $v){
		    $branch_data[$v['branch_id']] = $v['name'];
		}

        unset($branch_datas);

		if($reshipData[0]['return_id']){
			$returnData = $Oreturn_product->getList('return_bn,add_time',array('return_id'=>$reshipData[0]['return_id']));
		}
        
        $applymoney = 0;
        if($reshipData[0]['return_type'] == 'return'){
            $applymoney = $reshipData[0]['totalmoney'];
        } elseif($reshipData[0]['return_type'] == 'change'){
            $applymoney = $reshipData[0]['totalmoney'] + $reshipData[0]['cost_freight_money'] + $reshipData[0]['change_amount'];
        }

        //error_log(var_export($applymoney,1),3,'d:/test_log/applymoney.txt');
        $apply_detail = $Orefund_apply->db->select("select * from sdb_ome_refund_apply where memo like '%".$reshipData[0]['reship_bn']."%' and refund_refer='1' group by order_id ");


        if(bcsub($apply_detail[0]['money'],$applymoney,2)>0){
            return false;
        }

        if(!in_array($apply_detail[0]['status'],array('3','4'))){

            return false;
        }

		if($apply_detail[0]['payment']){
		  $payment_cfgObj = &app::get('ome')->model('payment_cfg');
		  $payment_cfg = $payment_cfgObj->dump(array('id'=>$apply_detail[0]['payment']), 'custom_name');
		  $paymethod = $payment_cfg['custom_name'];//支付方式  varchar
		}else{
		  $refund_detail = $Orefunds->getList('paymethod',array('refund_bn'=>$apply_detail[0]['refund_apply_bn']));
		  $paymethod = $refund_detail[0]['paymethod'];
		}

		$problem_name = '';

	    if($reshipData[0]['problem_id']){
			$problemObj = app::get('ome')->model('return_product_problem');
			$problemdata = $problemObj->dump(array('problem_id'=>$reshipData[0]['problem_id']), 'problem_name');
			$problem_name = $problemdata['problem_name'];
        }
        

		$rprocessData = $Oprocess->getList('op_id,acttime',array('reship_id'=>$reship_id));

		$data['shop_id'] = $reshipData[0]['shop_id'];
		$data['shop_bn'] = $shopData[0]['shop_bn'];
		$data['shop_name'] = $shopData[0]['name'];
		$data['order_id'] = $reshipData[0]['order_id'];
		$data['order_bn'] = $orderData[0]['order_bn'];
		$data['return_id'] = $reshipData[0]['return_id'];
		$data['return_bn'] = $returnData[0]['return_bn'];
		$data['reship_id'] = $reship_id;
		$data['reship_bn'] = $reshipData[0]['reship_bn'];
		$data['return_apply_id'] = $apply_detail[0]['apply_id'];
		$data['return_apply_bn'] = $apply_detail[0]['refund_apply_bn'];
		$data['return_type'] = $reshipData[0]['return_type'];
		$data['refundmoney'] = $apply_detail[0]['refunded'];
		$data['refund_apply_money'] = $reshipData[0]['totalmoney'];
		$data['paymethod'] = $paymethod;
		$data['member_id'] = $orderData[0]['member_id'];
		$data['member_uname'] = $memberData[0]['uname'];
		$data['ship_mobile'] = $reshipData[0]['ship_mobile'];
		$data['add_time'] = $returnData[0]['add_time'];
		$data['check_time'] = $reshipData[0]['t_begin'];
		$data['acttime'] = $rprocessData[0]['acttime'];
		$data['refundtime'] = $apply_detail[0]['last_modified'];
		
		$data['check_op_id'] = $reshipData[0]['op_id'];
		$data['check_op_name'] = $pam_data[$reshipData[0]['op_id']];

		$data['op_id'] = $rprocessData[0]['op_id'];
		$data['op_name'] = $pam_data[$rprocessData[0]['op_id']];

		$data['refund_op_id'] = $apply_detail[0]['verify_op_id'];
		$data['refund_op_name'] = $pam_data[$apply_detail[0]['verify_op_id']];

		$data['aftersale_time'] = $apply_detail[0]['last_modified']?$apply_detail[0]['last_modified']:$reshipData[0]['t_end'];
		$data['diff_order_bn'] = $reshipData[0]['diff_order_bn'];
		$data['change_order_bn'] = $reshipData[0]['change_order_bn'];
		$data['pay_type'] = $apply_detail[0]['pay_type'];
		$data['account'] = $apply_detail[0]['account'];
		$data['bank'] = $apply_detail[0]['bank'];
		$data['pay_account'] = $apply_detail[0]['pay_account'];
		$data['refund_apply_time'] = $apply_detail[0]['create_time'];
        $data['problem_name'] = $problem_name;
        if ($is_archive) {
            $data['archive'] = '1';
        }
		$Oreship_items = &app::get('ome')->model('reship_items');
        //获取退货单明细
        $reshipitemData = $Oreship_items->db->select("SELECT * FROM sdb_ome_reship_items WHERE reship_id=".$reship_id." AND (defective_num>0 OR normal_num>0)");
		//$reshipitemData = $Oreship_items->getList('*',array('reship_id'=>$reship_id));
        
        
		$orderitems = $Oorder_items->getList('pmt_price,price,bn',array('order_id'=>$reshipData[0]['order_id']));
        foreach($orderitems as $v){
		    $itemsdata[$v['bn']] = $v;
		}
        
		unset($orderitems);
        
		#计算实际 退款申请金额 = (退入商品和折旧费)分权平摊后的价格
        /*
		10 折旧费 

		退2件货 一件 100块 A
		一件 50块 B 
		分摊的时候 A 就是 10 * (100/（100+50）)
		B 就是 10- 10 * (100/（100+50）)
		*/

        if($reshipData[0]['bmoney'] > 0){

			$_apply_money = array();

			$reshipitemsdata = $Oreship_items->db->select('select sum(price*num) as return_money,price,num,bn from sdb_ome_reship_items where reship_id = '.$reship_id.' and return_type = "return" group by item_id');

            $reshipcount = $Oreship_items->db->select('select sum(price*num) as total_return_money,count(*) as count from sdb_ome_reship_items where reship_id = '.$reship_id.' and return_type = "return"');

			$tmp_money = 0.00;
			$loop = 1;
			foreach($reshipitemsdata as $k=>$v){
				  if($reshipcount[0]['count'] == $loop){
					  $_apply_money[$v['bn']]['apply_money'] = $reshipData[0]['bmoney'] - $tmp_money;
					  $tmp_money = 0;
				  }else{
					  $_apply_money[$v['bn']]['apply_money'] = $reshipData[0]['bmoney']*($v['return_money']/($reshipcount[0]['total_return_money']));
					  $tmp_money += $_apply_money[$v['bn']]['apply_money'];
				  }

				  $loop++;
			}
			unset($reshipitemsdata);
		}

		foreach($reshipitemData as $k=>$v){
            $nums = $v['normal_num']+$v['defective_num'];
			$data['aftersale_items'][$k]['bn'] = $v['bn'];
			$data['aftersale_items'][$k]['product_name'] = $v['product_name'];
			$data['aftersale_items'][$k]['num'] = $nums;
			$data['aftersale_items'][$k]['price'] = $v['price'];
			$data['aftersale_items'][$k]['branch_name'] = $branch_data[$v['branch_id']];
			$data['aftersale_items'][$k]['branch_id'] = $v['branch_id'];
			$data['aftersale_items'][$k]['product_id'] = $v['product_id'];
			$data['aftersale_items'][$k]['return_type'] = $v['return_type'];
			$data['aftersale_items'][$k]['pay_type'] = $data['pay_type'];
			$data['aftersale_items'][$k]['account'] = $data['account'];
			$data['aftersale_items'][$k]['bank'] = $data['bank'];
			$data['aftersale_items'][$k]['pay_account'] = $data['pay_account'];
			$data['aftersale_items'][$k]['payment'] = $apply_detail[0]['payment'];
			$data['aftersale_items'][$k]['create_time'] = $data['refund_apply_time'];
			$data['aftersale_items'][$k]['last_modified'] = $data['refundtime'];
			if($v['return_type'] == 'return'){
				if($reshipData[0]['bmoney'] > 0){
				     $refundapply_money = ($v['price']*$v['num']) - $_apply_money[$v['bn']]['apply_money'];
				}else{
                     $refundapply_money = $v['price']*$v['num'];
				}
				$data['aftersale_items'][$k]['money'] = $refundapply_money; //申请退款金额
				$data['aftersale_items'][$k]['saleprice'] = ($itemsdata[$v['bn']]['price'] * $v['num']);
				$data['aftersale_items'][$k]['refunded'] = ($data['refundmoney'] > 0)?$refundapply_money:0;
			}else{
				$data['aftersale_items'][$k]['money'] = 0;
				$data['aftersale_items'][$k]['saleprice'] = $v['price']*$v['num'];//销售价
				$data['aftersale_items'][$k]['refunded'] = 0;
			}
		}

/*
售后申请金额 money
已退款金额 refunded
退货金额 money
*/

		return $data;
	}
}