<?php
/**
 *对账到处处理
 *@author lijun
 */
class ome_balance_to_import{
	public $pay_type; 


	public function do_paymens_bill($data,&$msg){
		switch($this->pay_type){
			case 'alipay':
				$this->do_alipay_bill($data);
				break;
			case 'weixin':
				$this->do_weixin_bill($data);
				break;
			case 'cod':
				$this->do_cod_bill($data);
				break;
			case 'second_cod':
				$this->do_second_cod_bill($data);
				break;
			case 'allAlipay':
				$this->do_all_alipay_bill($data);
				break;
			default:
				$msg = '请标注收款单类型';
				return false;

		}
		return true;
	}

	public function do_all_alipay_bill($data){
		$pay_bill = array();
		$bill_title = $this->get_all_alipy_title();
		$bill_title_arr = array();
		foreach($data as $key=>$info){

			if(strpos($info[0],'#')===0){
				continue;
			}
			if($key==2){
				foreach($info as $tk=>$tv){
					if($bill_title[$tv])
						$bill_title_arr[$tk] = $bill_title[$tv];
				}
				//error_log(var_export($bill_title_arr,true),3,'f:/alipay.txt');
				continue;
			}
			
			$item = array();
			foreach($info as $k=> $v){
				if($bill_title_arr[$k])
					$item[$bill_title_arr[$k]] = trim(str_replace('`','',$v));
			}
			if($item['pay_type']=='交易'){
				$item['pay_type']='在线支付';
			}
			if($item['pay_type']=='服务费'){
				$item['pay_type']='收费';
			}
			$item['paymethod'] = 'alipay';
			$pay_bill[] = $item;
		}
//error_log(var_export($pay_bill,true),3,__FILE__.'alipay.txt');
		 $oQueue = app::get('base')->model('queue');
		 $queueData = array(
                'queue_title'=>' ome balance_account'.app::get('desktop')->_('导入'),
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$pay_bill,
                    'app' => 'ome',
                    'mdl' => 'statement',
					//'pay_type'=>'alipay',
                   // 'file_name' => $params['file_name']
                ),
                'worker'=>'ome_balance_to_run_import.run',
                //'worker'=>'desktop_finder_builder_to_run_import.run',
            );
          $re = $oQueue->save($queueData);
		//error_log(var_export($re,true),3,'f:/alipay.txt');
		//error_log(var_export($pay_bill,true),3,'f:/alipay.txt');
	}

	public function do_alipay_bill($data){
		$pay_bill = array();
		$bill_title = $this->get_alipy_title();
		$bill_title_arr = array();
		foreach($data as $key=>$info){

			if(strpos($info[0],'#')===0){
				continue;
			}
			if($key==4){
				foreach($info as $tk=>$tv){
					if($bill_title[$tv])
						$bill_title_arr[$tk] = $bill_title[$tv];
				}
				//error_log(var_export($bill_title_arr,true),3,'f:/alipay.txt');
				continue;
			}
			
			$item = array();
			foreach($info as $k=> $v){
				if($bill_title_arr[$k])
					$item[$bill_title_arr[$k]] = trim(str_replace('`','',$v));
			}
			$item['paymethod'] = 'alipay';
			$pay_bill[] = $item;
		}
		$oQueue = app::get('base')->model('queue');
		$index=1;
		$pay_bill_arr = array();
		foreach($pay_bill as $val){
			$pay_bill_arr[] = $val;
			if($index%100 == 0){
				 $queueData = array(
					'queue_title'=>' ome balance_account'.app::get('desktop')->_('导入'),
					'start_time'=>time(),
					'params'=>array(
						'sdfdata'=>$pay_bill_arr,
						'app' => 'ome',
						'mdl' => 'statement',
						//'pay_type'=>'alipay',
					   // 'file_name' => $params['file_name']
					),
					'worker'=>'ome_balance_to_run_import.run',
					//'worker'=>'desktop_finder_builder_to_run_import.run',
				);
				$re = $oQueue->save($queueData);
				$pay_bill_arr = array();
			}
			$index++;
		}
		
		$queueData = array(
			'queue_title'=>' ome balance_account'.app::get('desktop')->_('导入'),
			'start_time'=>time(),
			'params'=>array(
				'sdfdata'=>$pay_bill_arr,
				'app' => 'ome',
				'mdl' => 'statement',
				//'pay_type'=>'alipay',
			   // 'file_name' => $params['file_name']
			),
			'worker'=>'ome_balance_to_run_import.run',
			//'worker'=>'desktop_finder_builder_to_run_import.run',
		);
		$re = $oQueue->save($queueData);
		
		//error_log(var_export($re,true),3,'f:/alipay.txt');
		//error_log(var_export($pay_bill,true),3,'f:/alipay.txt');
	}

	public function do_weixin_bill($data){
		//error_log(var_export($data,true),3,__FILE__.'cc.txt');
		$pay_bill = array();
		$bill_title = $this->get_weixin_title();
		$bill_title_arr = array();
		foreach($data as $key=>$info){
			if($key==0){
				foreach($info as $tk=>$tv){
					if($bill_title[$tv])
						$bill_title_arr[$tk] = $bill_title[$tv];
				}
				//error_log(var_export($bill_title_arr,true),3,'f:/dd.txt');
				continue;
			}
			if(strpos($info[1],'wx')===false){
				continue;
			}

			$item = array();
			foreach($info as $k=> $v){
				if($bill_title_arr[$k])
					$item[$bill_title_arr[$k]] = trim(str_replace('`','',$v));
			}
			$item['paymethod'] = 'weixin';
			if($item['wx_order_bn']){
				$pay_bill[] = $item;
			}
		}

		$oQueue = app::get('base')->model('queue');
		$index=1;
		$pay_bill_arr = array();
		foreach($pay_bill as $val){
			$pay_bill_arr[] = $val;
			if($index%100 == 0){
				 $queueData = array(
					'queue_title'=>' ome balance_account'.app::get('desktop')->_('导入'),
					'start_time'=>time(),
					'params'=>array(
						'sdfdata'=>$pay_bill_arr,
						'app' => 'ome',
						'mdl' => 'statement',
						//'pay_type'=>'alipay',
					   // 'file_name' => $params['file_name']
					),
					'worker'=>'ome_balance_to_run_import.run',
					//'worker'=>'desktop_finder_builder_to_run_import.run',
				);
				$re = $oQueue->save($queueData);
				$pay_bill_arr = array();
			}
			$index++;
		}
		
		$queueData = array(
			'queue_title'=>' ome balance_account'.app::get('desktop')->_('导入'),
			'start_time'=>time(),
			'params'=>array(
				'sdfdata'=>$pay_bill_arr,
				'app' => 'ome',
				'mdl' => 'statement',
				//'pay_type'=>'alipay',
			   // 'file_name' => $params['file_name']
			),
			'worker'=>'ome_balance_to_run_import.run',
			//'worker'=>'desktop_finder_builder_to_run_import.run',
		);
		$re = $oQueue->save($queueData);
		//error_log(var_export($pay_bill,true),3,'f:/dd.txt');

	}

	public function do_cod_bill($data){
		$pay_bill = array();
		foreach($data as $key=>$val){
			if($key<=15){
				continue;
			}
			if(!$val[3]){
				break;
			}
			$item = array(
					'log_no'=>$val[3],
					'order_no'=>$val[5],
					'total_amount'=>$val[10],
					'fee_amount'=>$val[11],
					'memo'=>$val[18],
					'pay_time'=>$pay_time,
				);
			$item['paymethod'] = 'cod';
			$pay_bill[] = $item;
		}
		$oQueue = app::get('base')->model('queue');
		 $queueData = array(
                'queue_title'=>' ome balance_account'.app::get('desktop')->_('导入'),
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$pay_bill,
                    'app' => 'ome',
                    'mdl' => 'statement',
					//'pay_type'=>'weixin',
                   // 'file_name' => $params['file_name']
                ),
                'worker'=>'ome_balance_to_run_import.run',
                //'worker'=>'desktop_finder_builder_to_run_import.run',
            );
          $oQueue->save($queueData );
	}


	public function do_second_cod_bill($data){
		$pay_time = $data[12][10];
		$pay_bill = array();
		foreach($data as $key=>$val){
			if($key<=15){
				continue;
			}
			if(!$val[3]){
				break;
			}
			$item = array(
					'log_no'=>$val[3],
					'order_no'=>$val[5],
					'total_amount'=>$val[10],
					'fee_amount'=>$val[11],
					'memo'=>$val[18],
					'pay_time'=>$pay_time,
				);
			$item['paymethod'] = 'cod';
			$pay_bill[] = $item;
		}
		$oQueue = app::get('base')->model('queue');
		 $queueData = array(
                'queue_title'=>' ome balance_account'.app::get('desktop')->_('导入'),
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$pay_bill,
                    'app' => 'ome',
                    'mdl' => 'statement',
					'method'=>'second_cod'
					//'pay_type'=>'weixin',
                   // 'file_name' => $params['file_name']
                ),
                'worker'=>'ome_balance_to_run_import.run',
                //'worker'=>'desktop_finder_builder_to_run_import.run',
            );
          $oQueue->save($queueData );
	}


	public function get_weixin_title(){
		return array(
				'公众账号ID'=>'gzh_id',
				'商户号'=>'shh',
				'特约商户号'=>'tyshh',
				'微信订单号'=>'wx_order_bn',
				'商户订单号'=>'seller_order_bn',
				'用户标识'=>'member_sku',
				'交易类型'=>'pay_type',
				'交易状态'=>'pay_status',
				'总金额'=>'total_amount',
				'订单金额'=>'total_amount',
				'手续费'=>'fee_amount',
				'返还手续费'=>'back_fee_amount',
				'费率'=>'fee_rate',
				'交易时间'=>'pay_time',
				'退款金额'=>'return_amount',
				'退款成功时间'=>'return_time',
			);

	}

	public function get_alipy_title(){
		return array(
				'账务流水号'=>'zwlsh',
				'业务流水号'=>'ywlsh',
				'商户订单号'=>'seller_order_bn',
				'商品名称'=>'goods_name',
				'收入金额（+元）'=>'import_money',
				'支出金额（-元）'=>'explode_money',
				'业务类型'=>'pay_type',
				'备注'=>'memo',
				'发生时间'=>'pay_time',
			);
	}

	public function get_all_alipy_title(){
		return array(
				'支付宝交易号'=>'ywlsh',
				'支付宝流水号'=>'zwlsh',
				'商户订单号'=>'seller_order_bn',
				'商品名称'=>'goods_name',
				'收入（+元）'=>'import_money',
				'支出（-元）'=>'explode_money',
				'账务类型'=>'pay_type',
				'备注'=>'memo',
				'入账时间'=>'pay_time',
			);
	}

	public function get_cod_title(){

	}

	
}