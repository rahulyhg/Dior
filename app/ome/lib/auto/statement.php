<?php
class ome_auto_statement{
	

	public function insertStatement(){
		
		$paymentObj = app::get('ome')->model('payments');
		$statementObj = app::get('ome')->model('statement');
		$refundObj = app::get('ome')->model('refunds');
		$orderObj= app::get('ome')->model('orders');

		$payments = $paymentObj->getList('*',array('status'=>'succ','statement_status'=>'false'));
	
		foreach($payments as $row){
			$data = $order = array();
			$data['original_bn'] = $row['payment_bn'];
			$data['order_id'] = $row['order_id'];
			
            $order =$orderObj->getList("wx_order_bn,createtime,pay_bn",array('order_id'=>$row['order_id']));
            $order = $order[0];
            $data['wx_order_bn']=$order['wx_order_bn'];
            $data['paymethod'] = $order['pay_bn'];
            $data['createtime'] = $order['createtime'];

			$data['shop_id'] = $row['shop_id'];
			$data['money'] = $row['money'];
			$data['paycost'] = $row['paycost'];
			$data['cur_money'] = $row['cur_money'];
			$data['payment'] = $row['payment'];
			$data['memo'] = $row['memo'];
			$data['trade_no'] = $row['trade_no'];
			$statementObj->save($data);
			$paymentObj->update(array('statement_status'=>'true'),array('payment_id'=>$row['payment_id']));
		}

		$refunds = $refundObj->getList('*',array('status'=>'succ','statement_status'=>'false'));
		foreach($refunds as $row){
			$data = array();
			$data['original_bn'] = $row['refund_bn'];
			$data['order_id'] = $row['order_id'];
			
            $order =$orderObj->getList("wx_order_bn,createtime,pay_bn",array('order_id'=>$row['order_id']));
            $order = $order[0];
            $data['wx_order_bn']=$order['wx_order_bn'];
            $data['paymethod'] = $order['pay_bn'];
            $data['createtime'] = $order['createtime'];
            
			$data['shop_id'] = $row['shop_id'];
			$data['money'] = $row['money'];
			$data['paycost'] = $row['paycost'];
			$data['cur_money'] = $row['cur_money'];
			$data['payment'] = $row['payment'];
			$data['memo'] = $row['memo'];
			$data['trade_no'] = $row['trade_no'];
			$data['original_type'] = 'refunds';
			$statementObj->save($data);
			$refundObj->update(array('statement_status'=>'true'),array('refund_id'=>$row['refund_id']));
		}
		
	}
	
	public function auto_sync(){
		$paymentObj = app::get('ome')->model('statement');

		$payments = $paymentObj->getList('*',array('balance_status'=>'running'),0,100);
		if(empty($payments)){
			return true;
		}
		do{
			$this->sync_payments($payments);
			$payments = $paymentObj->getList('*',array('balance_status'=>'running'),0,100);
			if(empty($payments)){
				break;
			}
		}while(true);
	}

	public function sync_payments($payments){
		$paymentObj = app::get('ome')->model('statement');

		$ax_info = array();
	
		$objMath = kernel::single('eccommon_math');
		$objOrder = app::get('ome')->model('orders');
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
		$payment_ids = array();
		foreach($payments as $row){
			$payment_ids[] = $row['statement_id'];
			$arow = array();
			$arow[] = date('d/m/Y',$row['pay_time']);
			$arow[] = $ax_setting['ax_h_customer_account']?$ax_setting['ax_h_customer_account']:'C4010P1';
			if($row['original_type']=='refunds'){
				$arow[] = sprintf("%1\$.2f",-$objMath->number_plus(array(abs($row['tatal_amount']),0)));
				$arow[] = sprintf("%1\$.2f",-abs($objMath->number_plus(array($row['money'],0))));
			}else{
				$arow[] = $objMath->number_plus(array($row['tatal_amount'],0));
				$arow[] = $objMath->number_plus(array($row['money'],0));
			}

			$order_bn = $objOrder->dump($row['order_id'],'order_bn');
			if($row['original_type']=='refunds'){
				$objRefundApply = app::get('ome')->model('refund_apply');
				$refundInfo = $objRefundApply->getList('reship_id',array('refund_apply_bn'=>$row['original_bn']));
				
				$reship_id = $refundInfo[0]['reship_id'];
				if($reship_id){
					$objReship = app::get('ome')->model('reship');
					$allReship = $objReship->getList('reship_id',array('order_id'=>$row['order_id']));
					$reships = array_reverse($allReship);

					foreach($reships as $key=>$value){
						if($reship_id==$value['reship_id']){
							$R = $key;
							break;
						}
					}
					$order_bn['order_bn'] = $order_bn['order_bn'].'-R'.($R+1);
				}else{
					$deliveryInfo = app::get('ome')->model('delivery_order')->getList('*',array('order_id'=>$row['order_id']));
					if($deliveryInfo){
						$order_bn['order_bn'] = $order_bn['order_bn'].'-R1';
					}
					$order_bn['order_bn'] = $order_bn['order_bn'].'-R1';
				}
			}
			$arow[] = $order_bn['order_bn'];
			$arow[] = $row['difference_reason'];
			
			if($row['shop_id']=="c7c44eade93b87b69062c76dc27c8ae7"){
				$row['paymethod'] = 'wechatcard';
			}else{
				if($row['paymethod']=='wxpayjsapi'){
					$row['paymethod'] = 'WeChat';
				}
                if($row['paymethod']=='alipay'){
					$row['paymethod'] = 'Alipay';
				}
			}
			
			$arow[] = $row['paymethod'];
			$arow[] = 'PG4A';
			if($row['original_type']=='refunds'){
				$arow[] =sprintf("%1\$.2f",-abs($objMath->number_plus(array($row['pay_fee'],0))));
			}else{
				$arow[] =sprintf("%1\$.2f",abs($objMath->number_plus(array($row['pay_fee'],0))));
			}
			if($row['original_type']=='payments'){
				$arow[] = $row['paymethod'].' payment '.$order_bn['order_bn'].' in '.$arow[0];
			}else{
				$arow[] = $row['paymethod'].' return '.$order_bn['order_bn'].' in '.$arow[0];
			}
			
			$ax_info[] = implode(',',$arow);
		}
		$content = implode("\n",$ax_info);
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
		$file_brand = $ax_setting['ax_file_brand'];
		$file_prefix = $ax_setting['ax_file_prefix'];

		$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));

		$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		while(file_exists($file_name)){
			sleep(1);
			$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));
			$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		}
		
		//echo "<pre>";print_r($file_name);exit;
		$file = fopen($file_name,"w");
		$res = fwrite($file,$content);
		fclose($file);
		//同步AX

		if(!$res){
			return true;
		}
		$params['remote'] = basename($file_name);
		$params['local'] = $file_name;
		$params['resume'] = 0;

		$ftp_log_data = array(
				'io_type'=>'out',
				'work_type'=>'payments',
				'createtime'=>time(),
				'status'=>'prepare',
				'file_local_route'=>$params['local'],
				'file_ftp_route'=>$params['remote'],
			);
		$objLog = kernel::single('omeftp_log');
		$ftp_log_id = $objLog->write_log($ftp_log_data,'ftp');

		$ftp_flag = kernel::single('omeftp_ftp_operate')->push($params,$msg);
		if($ftp_flag){
			$objLog->update_log(array('status'=>'succ','lastmodify'=>time(),'memo'=>'上传成功！'),$ftp_log_id,'ftp');
		}else{
			$objLog->update_log(array('status'=>'fail','memo'=>$msg),$ftp_log_id,'ftp');
		}
		$paymentObj->update(array('balance_status'=>'sync'),array('statement_id'=>$payment_ids));
	}
}