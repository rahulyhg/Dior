<?php
class ome_auto_statement{
	

	public function insertStatement(){
		
		$paymentObj = app::get('ome')->model('payments');
		$statementObj = app::get('ome')->model('statement');
		$refundObj = app::get('ome')->model('refunds');
		$orderObj= app::get('ome')->model('orders');

		$payments = $paymentObj->getList('*',array('status'=>'succ','statement_status'=>'false'));

		foreach($payments as $row){
            //查询该支付流水是否先存在对账表  如果存在则直接对账
            $ifExist = $statementObj->getList('*',array('original_bn'=>$row['trade_no'],'original_type'=>'payments'));
            if(!empty($ifExist)){
                $this->paymentsUpdate($row,$ifExist['0'],'payments');
            }else{
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

		}

		$refunds = $refundObj->getList('*',array('status'=>'succ','statement_status'=>'false'));
		foreach($refunds as $row){
            //查询该支付流水是否先存在对账表  如果存在则直接对账
            $ifExist = $statementObj->getList('*',array('original_bn'=>$row['trade_no'],'original_type'=>'refunds'));
            if(!empty($ifExist)){
                $this->paymentsUpdate($row,$ifExist['0'],'refunds');
				$updateRefundSql = "UPDATE sdb_ome_refunds SET statement_status='true' WHERE refund_id=".$row['refund_id'];
				$refundObj->db->exec($updateRefundSql);
            }else{
                $data = array();
                $data['original_bn'] = $row['refund_bn'];
                $data['order_id'] = $row['order_id'];

                $order =$orderObj->getList("wx_order_bn,createtime,pay_bn",array('order_id'=>$row['order_id']));
                $order = $order[0];
                $data['wx_order_bn']=$order['wx_order_bn'];
                $data['paymethod'] = $order['pay_bn'];
                $data['createtime'] = $order['createtime'];
                //退货退款的AX文件整合编号
                $refundApplyInfo = app::get('ome')->model('refund_apply')->getList('*',array('refund_apply_bn'=>$row['refund_bn']));
				
                if($refundApplyInfo['0']['reship_id']){
                    $reshipInfo = app::get('ome')->model('reship')->getList('*',array('reship_id'=>$refundApplyInfo['0']['reship_id']));
                    $data['so_bn'] = $reshipInfo['0']['so_order_num'];
                }


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
		
	}
	
	public function auto_sync(){
		$paymentObj = app::get('ome')->model('statement');
		$orderMdl = app::get('ome')->model('orders');
		//普通订单合并同步AX
		$normal_payments = $paymentObj->getList('*',array('balance_status'=>'running','shop_id|notin'=>array('4395c5a0b113b9d11cb4ba53c48b4d88','c7c44eade93b87b69062c76dc27c8ae7')),0,500);

		if(!empty($normal_payments)){
            do{
                $normal_payments_new = $normal_payments_old = array();
                foreach($normal_payments as $key=>$payInfo){
                    $orderInfo = $orderMdl->getList('*',array('order_id'=>$payInfo['order_id']));
                    if($orderInfo['0']['so_type']=='1'){
                        $normal_payments_new[] = $payInfo;
                    }else{
                        $normal_payments_old[] = $payInfo;
                    }
                }
                //echo '<pre>d';print_r($normal_payments_old);print_r($normal_payments_new);exit;
    			if(!empty($normal_payments_old)){
    				$this->sync_payments($normal_payments_old);
    			}
    			if(!empty($normal_payments_new)){
    				$this->sync_payments2($normal_payments_new);
    			}
                $normal_payments = $paymentObj->getList('*',array('balance_status'=>'running','shop_id|notin'=>array('4395c5a0b113b9d11cb4ba53c48b4d88','c7c44eade93b87b69062c76dc27c8ae7')),0,500);
    			if(empty($normal_payments)){
    				break;
    			}
    		}while(true);
		}

		//购卡订单、兑礼订单发送给AX走原有不合并的逻辑
		$giftCardE_payments = $paymentObj->getList('*',array('balance_status'=>'running','shop_id|in'=>array('4395c5a0b113b9d11cb4ba53c48b4d88','c7c44eade93b87b69062c76dc27c8ae7')),0,500);
        if(!empty($giftCardE_payments)){
            do{
                $this->sync_payments($giftCardE_payments);
                //$this->sync_payments2($normal_payments);
                $giftCardE_payments = $paymentObj->getList('*',array('balance_status'=>'running','shop_id|in'=>array('4395c5a0b113b9d11cb4ba53c48b4d88','c7c44eade93b87b69062c76dc27c8ae7')),0,500);
                if(empty($giftCardE_payments)){
                    break;
                }
            }while(true);
        }
        
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
		$paymentObj->update(array('balance_status'=>'sync','sync_time'=>time()),array('statement_id'=>$payment_ids));
	}

    public function sync_payments2($payments){
        $paymentObj = app::get('ome')->model('statement');

        $ax_info = array();

        $objMath = kernel::single('eccommon_math');
        $objOrder = app::get('ome')->model('orders');
        $ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
        $payment_ids = array();
        $paymentList = $this->merge_payment($payments);
        if(empty($paymentList)){
            return false;
        }
		foreach($payments as $row){
			$payment_ids[] = $row['statement_id'];
		}
        foreach($paymentList as $row){
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

            //$order_bn = $objOrder->dump($row['order_id'],'order_bn');
            //$arow[] = $order_bn['order_bn'];
            $arow[] = $row['order_bn'];//支付和退款都直接取大订单号
            $arow[] = $row['difference_reason'];
            //wechatcard数据整合中已经做过判断

            if($row['paymethod']=='wxpayjsapi'){
                $row['paymethod'] = 'WeChat';
            }
            if($row['paymethod']=='alipay'){
                $row['paymethod'] = 'Alipay';
            }

            $arow[] = $row['paymethod'];
            $arow[] = 'PG4A';
            if($row['original_type']=='refunds'){
                $arow[] =sprintf("%1\$.2f",-abs($objMath->number_plus(array($row['pay_fee'],0))));
            }else{
                $arow[] =sprintf("%1\$.2f",abs($objMath->number_plus(array($row['pay_fee'],0))));
            }
            if($row['original_type']=='payments'){
                $arow[] = $row['paymethod'].' payment '.$row['order_bn'].' in '.$arow[0];
            }else{
                $arow[] = $row['paymethod'].' return '.$row['order_bn'].' in '.$arow[0];
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
        $paymentObj->update(array('balance_status'=>'sync','sync_time'=>time(),'ax_file_name'=>$file_name),array('statement_id'=>$payment_ids));
    }
	//整理合并数据
    function merge_payment($payments){
	    if(!empty($payments)){
	        $row = array();
	        $orderMdl = app::get('ome')->model('orders');
	        $rowKey = $giftcard_key = 0;
	        foreach($payments as $payment) {
                //生成大订单号
                if($payment['paymethod']=='alipay'){
                    $S = 'A';
                }
                if($payment['paymethod']=='wxpayjsapi'){
                    $S = 'W';
                }
                
                //$payDate = date("Ymd", ($payment['paytime']?$payment['paytime']:time()));
                $orderInfo = $orderMdl->getList('*',array('order_id'=>$payment['order_id']));
				if($payment['original_type']=='payments'){
				    $key_time = $orderInfo['0']['paytime']?$orderInfo['0']['paytime']:$orderInfo['0']['createtime'];
                    $payDate = $S.date('Ymd',$key_time);//支付账单使用订单的支付时间
                    $key = 'p'.$payDate;
					$row[$key]['order_bn'] = $payDate;
					$row[$key]['paymethod'] = $payment['paymethod'];
					$row[$key]['original_type'] = 'payments';
					$row[$key]['pay_fee'] += $payment['pay_fee'];
					$row[$key]['money'] += $payment['money'];
					$row[$key]['tatal_amount'] += $payment['tatal_amount'];
					$row[$key]['pay_time'] = (!empty($payment['pay_time'])) ? $payment['pay_time'] : '';
				}
				if($payment['original_type']=='refunds'){
				    $key_time = $orderInfo['0']['paytime']?$orderInfo['0']['paytime']:$orderInfo['0']['createtime'];
				    $payDate = $S.date('Ymd',$key_time);//退款账单使用订单的支付时间合并
					$key = 'r'.$payDate;
					$row[$payDate]['order_bn'] = $payDate;
					$row[$payDate]['paymethod'] = $payment['paymethod'];
					$row[$payDate]['original_type'] = 'refunds';
					$row[$payDate]['pay_fee'] += $payment['pay_fee'];
					$row[$payDate]['money'] += $payment['money'];
					$row[$payDate]['tatal_amount'] += $payment['tatal_amount'];
                    //退款账单直接使用导入的账单退款时间
					$row[$payDate]['pay_time'] = (!empty($payment['pay_time'])) ? $payment['pay_time'] : '';
				}
                //普通订单的大订单号可以区分出支付类型和是否属于退款账单
                
                //echo '<pre>drr';print_r($row);exit;

                //礼品卡店铺没有SO文件单独使用原有的逻辑

            }
            /*$resArr  =array();
	        //整理数组
            foreach ($row as $key=>$value){
	            foreach ($value as $k=>$prow){
                    $resArr[$rowKey]['order_bn'] = $prow['order_bn'];
                    $resArr[$rowKey]['paymethod'] = $prow['paymethod'];
                    $resArr[$rowKey]['original_type'] = $prow['original_type'];
                    $resArr[$rowKey]['pay_fee'] = $prow['pay_fee'];
                    $resArr[$rowKey]['money'] = $prow['money'];
                    $resArr[$rowKey]['tatal_amount'] = $prow['tatal_amount'];
                    $resArr[$rowKey]['pay_time'] = $prow['pay_time'];
                    $rowKey++;
                }
            }*/
            return $row;
        }else{
	        return null;
        }
    }
    //账单先导入再对账
    function paymentsUpdate($dataRow,$statementRow,$paymentType='payments'){
        if(empty($dataRow)||empty($statementRow)){
            return false;
        }

        $statementObj = app::get('ome')->model('statement');
        $refundObj = app::get('ome')->model('refunds');
        $orderObj= app::get('ome')->model('orders');
        //对账
        if($paymentType=='payments'){
            //echo '<pre>d';print_r($statementRow['tatal_amount']);print_r($dataRow['money']);exit;
            $data['order_id'] = $dataRow['order_id'];
            $order =$orderObj->getList("wx_order_bn,createtime,pay_bn",array('order_id'=>$dataRow['order_id']));
            $order = $order[0];
            $data['wx_order_bn']=$order['wx_order_bn']?$order['wx_order_bn']:'';
            $data['paymethod'] = $order['pay_bn'];
            $data['createtime'] = $order['createtime'];
            $data['shop_id'] = $dataRow['shop_id'];
            $data['money'] = $dataRow['money'];
            $data['paycost'] = $dataRow['paycost'];
            $data['cur_money'] = $dataRow['cur_money'];
            $data['payment'] = $dataRow['payment'];
            $data['memo'] = $dataRow['memo']?($dataRow['memo'].$statementRow['memo']):$statementRow['memo'];
            $data['trade_no'] = $dataRow['trade_no'];
            $data['difference_money']= abs($statementRow['tatal_amount']-$dataRow['money']);
            $data['balance_status']= ($statementRow['tatal_amount']==$dataRow['money'])?'auto':'require';
            $data['statement_id']= $statementRow['statement_id'];
			$data['so_bn']= $order['so_order_num']?$order['so_order_num']:'';
			//更新支付单的对账状态字段
            if($statementObj->save($data))
            {
                $paymentObj = app::get('ome')->model('payments');
                $paymentObj->update(array('statement_status'=>'true'),array('payment_id'=>$dataRow['payment_id']));
            }
        }
        if($paymentType=='refunds'){
            $data = array(
                'difference_money'=>abs(abs($statementRow['tatal_amount'])+$dataRow['money']),
                'balance_status'=>(abs($statementRow['tatal_amount']+$dataRow['money'])=='0')?'auto':'require',
                'memo'=>$dataRow['memo']?($dataRow['memo'].$statementRow['memo']):$statementRow['memo'],
                'shop_id' => $dataRow['shop_id'],
                'money' => $dataRow['money'],
                'paycost'=> $dataRow['paycost'],
                'cur_money' => $dataRow['cur_money'],
                'payment'=> $dataRow['payment'],
                'trade_no' => $dataRow['trade_no'],
				'statement_id'=> $statementRow['statement_id'],
				'order_id'=> $dataRow['order_id'],
            );
			//查询是否是退货退款  如果是退货则更新退货单的SOBN
			$refundMdl = app::get('ome')->model('refunds');
			$sql = "SELECT s.* FROM sdb_ome_refunds r LEFT JOIN sdb_ome_refund_apply a ON r.refund_bn = a.refund_apply_bn LEFT JOIN sdb_ome_reship s ON a.reship_id = s.reship_id WHERE r.refund_id='".$dataRow['refund_id']."'";
			$reshipInfo = $refundMdl->db->select($sql);
			//echo '<pre>q';print_r($reshipInfo);exit;
			$data['so_bn']= $reshipInfo['0']['so_order_num']?$reshipInfo['0']['so_order_num']:'';
            $statementObj->save($data);
        }
    }
}