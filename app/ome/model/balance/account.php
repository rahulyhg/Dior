<?php
class ome_mdl_balance_account extends ome_mdl_payments {

	
	 public function table_name($real=false){
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.'payments';
        }else{
            return 'payments';
        }
    }

	public function get_schema(){
		$schema = parent::get_schema();
		$schema['in_list'][] = 'pay_fee';
		$schema['in_list'][] = 'fee_rate';
		$schema['in_list'][] = 'difference_money';
		$schema['in_list'][] = 'balance_status';
		$schema['in_list'][] = 'tatal_amount';

		$schema['default_in_list'][] = 'pay_fee';
		$schema['default_in_list'][] = 'fee_rate';
		$schema['default_in_list'][] = 'difference_money';
		$schema['default_in_list'][] = 'balance_status';
		$schema['default_in_list'][] = 'tatal_amount';
		return $schema;
	}

	public function balanceOfAccount($sdf){

		switch($sdf['paymethod']){
			case 'alipay':
				$payments = $this->getList('payment_id,money,tatal_amount',array('payment_bn'=>$sdf['seller_order_bn']));
				if($payments){
					error_log(var_export($sdf,true),3,'f:/alipay.txt');
					if($sdf['import_money']>0){
						$updateData = array(
								'tatal_amount'=>$sdf['import_money'],
								'difference_money'=>abs($sdf['import_money']-$payments[0]['money']),
								'balance_status'=>($sdf['import_money']==$payments[0]['money'])?'auto':'require',
								'payment_id'=>$payments[0]['payment_id']
							);
					}else{
						$updateData = array(
								'pay_fee'=>abs($sdf['explode_money']),
								'fee_rate'=>round(abs($sdf['explode_money'])/$payments[0]['tatal_amount'],2),
								'payment_id'=>$payments[0]['payment_id']
							);
					}
					$this->save($updateData);
				}
				break;
			case 'weixin':
				$payments = $this->getList('payment_id,money',array('payment_bn'=>$sdf['seller_order_bn']));
				if($payments){

					$updateData = array(
							'tatal_amount'=>$sdf['total_amount'],
							'pay_fee'=>$sdf['fee_amount'],
							'fee_rate'=>$sdf['fee_rate'],
							'difference_money'=>abs($sdf['total_amount']-$payments[0]['money']),
							'balance_status'=>($sdf['total_amount']==$payments[0]['money'])?'auto':'require',
							'payment_id'=>$payments[0]['payment_id']
						);
					$this->save($updateData);
				}
				break;
			case 'cod':
				break;
			default:
				break;
		}
	}

	public function _filter($filter,$tableAlias=null,$baseWhere=null){
		//echo "<pre>";print_r($filter);exit;
        if (isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|has'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $archiveObj = &app::get('archive')->model('orders');
            $archives = $archiveObj->getList('order_id',array('order_bn|has'=>$filter['order_bn']));
            foreach ($archives  as $archive ) {
                $orderId[] = $archive['order_id'];
            }
            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_bn']);
        }//echo "<pre>";print_r(parent::_filter($filter,$tableAlias,$baseWhere).$where);exit;
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

	
}