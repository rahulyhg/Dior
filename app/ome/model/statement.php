<?php
class ome_mdl_statement extends dbeav_model {

    //是否有导出配置
    var $has_export_cnf = true;

	public function balanceOfAccount($sdf){
		$saveDate = array();
		//error_log(var_export($sdf,true),3,__FILE__.'cc.txt');
		switch($sdf['paymethod']){
			
			case 'alipay':
				if($sdf['pay_type']=='在线支付'||$sdf['pay_type']=='红包保证金支付'||$sdf['pay_type']=='收费'){
					$payments = $this->getList('statement_id,money,tatal_amount,balance_status,memo',array('original_bn'=>$sdf['ywlsh'],'original_type'=>'payments','disabled'=>'false'));
				}
				if($sdf['pay_type']=='交易退款'||$sdf['pay_type']=='红包退回'){
					$payments = $this->getList('statement_id,money,tatal_amount,balance_status,memo',array('original_bn|head'=>$sdf['ywlsh'],'original_type'=>'refunds','disabled'=>'false'));
				}
				if($payments){
					if($payments[0]['balance_status']=='not_has'){
						return true;
					}
					if($payments[0]['balance_status']=='sync'){
						return true;
					}
					if($payments[0]['balance_status']=='hand'){
						//return true;
					}
					
					if($sdf['import_money']>0){
						if($payments[0]['balance_status']=='auto'){
							return true;
						}
						if(strpos($payments[0]['memo'],$sdf['zwlsh'])){
							return true;
						}
						if($payments[0]['balance_status']=='require'&&!strpos($payments[0]['memo'],$sdf['zwlsh'])){
							$sdf['import_money'] += $payments[0]['tatal_amount'];
						}
						$updateData = array(
								'tatal_amount'=>$sdf['import_money'],
								'difference_money'=>abs($sdf['import_money']-$payments[0]['money']),
								'balance_status'=>($sdf['import_money']==$payments[0]['money'])?'auto':'require',
								'statement_id'=>$payments[0]['statement_id'],
								'importer_time'=>time(),
								'pay_time'=>strtotime($sdf['pay_time']),
								'paymethod'=>'alipay',
								'memo'=>$payments[0]['memo'].','.$sdf['zwlsh'],
							);
					}else{
						if($payments[0]['balance_status']=='require'&&!strpos($payments[0]['memo'],$sdf['zwlsh'])){
							$sdf['explode_money'] += $payments[0]['tatal_amount'];
						}
					//	error_log(var_export($sdf,true),3,__FILE__.'cc.txt');
						if($sdf['pay_type']=='交易退款'||$sdf['pay_type']=='红包退回'){
							$updateData = array(
								'tatal_amount'=>$sdf['explode_money'],
								'difference_money'=>abs(abs($sdf['explode_money'])-$payments[0]['money']),
								'balance_status'=>(abs($sdf['explode_money'])==$payments[0]['money'])?'auto':'require',
								'statement_id'=>$payments[0]['statement_id'],
								'importer_time'=>time(),
								'pay_time'=>strtotime($sdf['pay_time']),
								'paymethod'=>'alipay',
								'memo'=>$payments[0]['memo'].','.$sdf['zwlsh'],
							);
						}else{
							$updateData = array(
									'pay_fee'=>abs($sdf['explode_money']),
									'fee_rate'=>round(abs($sdf['explode_money'])/$payments[0]['tatal_amount'],4),
									'statement_id'=>$payments[0]['statement_id']
								);
						}
					}
					$this->save($updateData);
				}else{
					if($sdf['pay_type']=='交易退款'){
						$saveDate =  array(
								'original_bn'=>$sdf['ywlsh'],
								'tatal_amount'=>$sdf['explode_money'],
								'balance_status'=>'not_has',
								'original_type'=>'refunds',
								'importer_time'=>time(),
								'pay_time'=>strtotime($sdf['pay_time']),
								'paymethod'=>'alipay',
							);
					}else{
						$saveDate =  array(
								'original_bn'=>$sdf['ywlsh'],
								'tatal_amount'=>$sdf['import_money'],
								'balance_status'=>'not_has',
								'importer_time'=>time(),
								'pay_time'=>strtotime($sdf['pay_time']),
								'paymethod'=>'alipay',
							);
					}
					//$this->save($saveDate);
				}
				break;
			case 'weixin':
				if($sdf['return_amount']>0){
					$sdf['total_amount'] = $sdf['return_amount'];
					$original_type = 'refunds';
					$payments = $this->getList('statement_id,money,balance_status',array('original_bn|head'=>$sdf['wx_order_bn'],'original_type'=>'refunds','disabled'=>'false'));
					$sdf['pay_time'] = $sdf['return_time']?$sdf['return_time']:$sdf['pay_time'];
				}else{
					$original_type = 'payments';
					$payments = $this->getList('statement_id,money,balance_status',array('original_bn'=>$sdf['wx_order_bn'],'original_type'=>'payments','balance_status'=>'none','disabled'=>'false'));
				}
				if($payments){
					if($payments[0]['balance_status']=='not_has'){
						return true;
					}
					if($payments[0]['balance_status']=='sync'){
						return true;
					}
					if($payments[0]['balance_status']=='hand'){
						return true;
					}
					if($payments[0]['balance_status']=='auto'){
						return true;
					}
					$updateData = array(
							'tatal_amount'=>$sdf['total_amount'],
							'pay_fee'=>$sdf['fee_amount'],
							'fee_rate'=>round($sdf['fee_amount']/$sdf['total_amount'],4),
							'difference_money'=>abs($sdf['total_amount']-$payments[0]['money']),
							'balance_status'=>($sdf['total_amount']==$payments[0]['money'])?'auto':'require',
							'statement_id'=>$payments[0]['statement_id'],
							'importer_time'=>time(),
							'pay_time'=>strtotime($sdf['pay_time']),
							'paymethod'=>'wxpayjsapi',
						);
					$this->save($updateData);
				}else{
					$saveDate = array(
							'tatal_amount'=>$sdf['total_amount'],
							'pay_fee'=>$sdf['fee_amount'],
							'fee_rate'=>round($sdf['fee_amount']/$sdf['total_amount'],4),
							'difference_money'=>0,
							'balance_status'=>'not_has',
							'original_bn'=>$sdf['wx_order_bn'],
							'importer_time'=>time(),
							'pay_time'=>strtotime($sdf['pay_time']),
							'paymethod'=>'wxpayjsapi',
							'original_type'=>$original_type,
						);
					//$flag = $this->insert($saveDate);
				}
				break;
			case 'cod':
				$payments = $this->getList('statement_id,money,balance_status',array('original_bn'=>$sdf['log_no'],'original_type'=>'payments','disabled'=>'false'));
				if($payments){
					if($payments[0]['balance_status']=='not_has'){
						return true;
					}
					if($payments[0]['balance_status']=='sync'){
						return true;
					}
					if($payments[0]['balance_status']=='hand'){
						return true;
					}
					if($payments[0]['balance_status']=='auto'){
						return true;
					}
					$updateData = array(
							'tatal_amount'=>$sdf['total_amount'],
							'pay_fee'=>$sdf['fee_amount'],
							'fee_rate'=>round($sdf['fee_amount']/$sdf['total_amount'],4),
							'difference_money'=>abs($sdf['total_amount']-$payments[0]['money']),
							'balance_status'=>($sdf['total_amount']==$payments[0]['money'])?'auto':'require',
							'statement_id'=>$payments[0]['statement_id'],
							'importer_time'=>time(),
							'cod_time'=>'first',
							'paymethod'=>'cod',
						);
					$this->save($updateData);
				}else{
					$saveDate = array(
							'tatal_amount'=>$sdf['total_amount'],
							'pay_fee'=>$sdf['fee_amount'],
							'fee_rate'=>round($sdf['fee_amount']/$sdf['total_amount'],4),
							'difference_money'=>0,
							'balance_status'=>'not_has',
							'original_bn'=>$sdf['log_no'],
							'importer_time'=>time(),
							'cod_time'=>'first',
							'paymethod'=>'cod',
						);
					//$flag = $this->insert($saveDate);
				}
				break;
			default:
				break;
		}
	}


	public function second_cod($sdf){
		$updateData = array();
		$payments = $this->getList('statement_id,money',array('original_bn'=>$sdf['log_no'],'original_type'=>'payments'));
		if($payments){
			$updateData = array(
					'cod_time'=>'second',
					'pay_time'=>strtotime($sdf['pay_time']),
					'statement_id'=>$payments[0]['statement_id'],
				);
			$this->save($updateData);
		}
	}

	public function _filter($filter,$tableAlias=null,$baseWhere=null){
        if (isset($filter['order_id'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|has'=>$filter['order_id']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $archiveObj = &app::get('archive')->model('orders');
            $archives = $archiveObj->getList('order_id',array('order_bn|has'=>$filter['order_id']));
            foreach ($archives  as $archive ) {
                $orderId[] = $archive['order_id'];
            }
            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_id']);
        }
		 if (isset($filter['cod_time'])){
          
            $where .= '  AND cod_time = "'.$filter['cod_time'].'"';
            unset($filter['cod_time']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    } 

	public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
		 if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        if($this->use_meta){
             $meta_info = $this->prepare_select($cols);
        }
        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` WHERE '.$this->_filter($filter);
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        $data = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($data, $cols);
        if($this->use_meta && count($meta_info['metacols']) && $data){
            foreach($meta_info['metacols'] as $col){
                $obj_meta = new dbeav_meta($this->table_name(true),$col,$meta_info['has_pk']);
                $obj_meta->select($data);
            }
        }
		foreach($data as $key=>$value){
			if($value['original_type']=='refunds'){
				$data[$key]['tatal_amount'] = -abs($data[$key]['tatal_amount']);
				$data[$key]['money'] = -abs($data[$key]['money']);
			}
		}
        return $data;
	}
}