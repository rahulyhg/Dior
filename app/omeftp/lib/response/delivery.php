<?php
class omeftp_response_delivery{
	
	 public function __construct(&$app)
    {
        $this->app = $app;

        $this->file_obj = kernel::single('omeftp_type_txt');
		$this->ftp_operate = kernel::single('omeftp_ftp_operate');

		$this->operate_log = kernel::single('omeftp_log');
    }

	public function getFtpFile($file_prefix='RDER_REG_DIOR',$dir='/FROM_AX'){
		$list = $this->ftp_operate->get_file_list($dir);
		$str = $file_prefix;
		$file_arr = array();

		$file_list = array();
		foreach($list as $key=>$value){
			if(strpos($value,'bal')){
				$file_list[] = $value;
				$file_list[] = str_replace('bal','dat',$value);
			}
		}

		$ftpLogObj = app::get('omeftp')->model('ftplog');

		//echo $str;
		foreach($file_list as $filename){
			$params = array();
			//var_dump(strpos($filename,$str));
			if(strpos($filename,$str)){

				$params['remote'] = '/FROM_AX/'.$filename;
				if(!file_exists(ROOT_DIR.'/ftp/Testing/out/')){
					mkdir(ROOT_DIR.'/ftp/Testing/out/',0777,true);
				}
				if(!file_exists(ROOT_DIR.'/ftp/Testing/out/'.date('Ymd',time()))){
					mkdir(ROOT_DIR.'/ftp/Testing/out/'.date('Ymd',time()),0777,true);
				}
				$local = ROOT_DIR.'/ftp/Testing/out/'.date('Ymd',time()).'/'.$filename;
				$params['local'] = $local;
				$params['resume'] = 0;
				
				$ftp_log_data = array();
				$ftp_log_data = array(
							'io_type'=>'in',
							'work_type'=>'delivery',
							'createtime'=>time(),
							'file_local_route'=>$local,
							'file_ftp_route'=>$filename,
						);

				$sign = $this->ftp_operate->pull($params,$msg);
				if($sign){
					$file_arr[] = $local;
					$this->ftp_operate->delete_ftp($params['remote']);
					$ftp_log_data['status']='succ';
				}else{
					$ftp_log_data['status']='fail';
				}

				$ftpLogObj->insert($ftp_log_data);
			}

		}
		return $file_arr;
	}


	public function down_load(){
		$list = $this->getFtpFile('RDER_REG_DIOR');
		//echo "<pre>";print_r($list);exit;
		foreach($list as $filename){
			if(strpos($filename,'dat')){
				$this->read_order($filename);
			}
		}
	}
	
	public function read_order($file_name){
		$params['file'] = $file_name;
		$info = $this->file_obj->toRead($params,$msg);
		$orders = array();
		$index = -1;
		$arr = explode("\n",$info);
		//echo "<pre>";print_r($arr);exit;
		foreach($arr as $v){
			$line = array();
			$charset[1] = substr($v, 0, 1);
			$charset[2] = substr($v, 1, 1);
			$charset[3] = substr($v, 2, 1);
			if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
				$v = substr($v, 3);
			}
			$line = explode('|',$v);
			if($line[0]=='HEADER'){
				continue;
			}
			if($line[0]=='H'){
				$index++;
			}
			$orders[$index][$line[0]][] = $line;
		}
		//echo "<pre>";print_r($orders);exit;
		foreach($orders as $order){
			$this->do_delivery($order);
		}
	}

	public function do_delivery($data){	//
		$mdl_order = app::get('ome')->model('orders');
		$mdl_reship = app::get('ome')->model('reship');
		$mdl_order_delivery = app::get('ome')->model('delivery_order');
		$mdl_delivery = app::get('ome')->model('delivery');
		
		$order_bn = $data['H'][0][5];
		$order_info = $mdl_order->getList('order_id,ship_status,shop_type,is_mcd,createway,relate_order_bn',array('order_bn'=>$order_bn));
		if($order_info[0]['ship_status']!='0'){
			return true;
		}
		
		$delivery_id = $mdl_order_delivery->getList('order_id,delivery_id',array('order_id'=>$order_info[0]['order_id']));
		$delivery_id = array_reverse($delivery_id);
		$delivery_info = $mdl_delivery->dump($delivery_id[0]['delivery_id']);

		//$status = $data['H'][0][9];
		$status = 'delivery';

		//如果有发票，把发票号写入订单
		$invoice_number = $data['I'][0][4];
		$ax_order_bn = $data['H'][0][6];
		$sql = 'update sdb_ome_orders set tax_no="'.$invoice_number.'",ax_order_bn="'.$ax_order_bn.'" where order_bn="'.$order_bn.'"';
		kernel::database()->exec($sql);



		$query_params = array (
			"method" => "wms.delivery.status_update",
			"date" => "",
			"format" => "json",
			"node_id" => "selfwms",
			"app_id" => "ecos.ome",
			"task" => md5(time().$ax_order_bn),
			'delivery_bn'=>$delivery_info['delivery_bn'],
			'invoice_number'=>$data['I'][0][4],
			'logi_id'=>'SF',
			'logi_no'=>$data['D'][0][11],
			'warehouse'=>'001',//$data['warehouse'],
			'status'=>$status,
			'volume'=>'156',
			'weight'=>$data['D'][0][19],
			'remark'=>'发货回传',
			'operate_time'=>time(),
				);
		$items = array();
		foreach($data['L'] as $item){
			$items[] = array(
					'product_bn'=>$item['4'],
					'num'=>$item['16'],
				);
		}

		$cancel = $data['H'][0][9];
		if($cancel=='Canceled'){
			$objDelOrder = app::get('ome')->model('delivery_order');
			$objOrder = app::get('ome')->model('orders');
			$objShop = app::get('ome')->model('shop');
			$delivery_id = $delivery_id[0]['delivery_id'];
			$order_id = $objDelOrder->getList('*',array('delivery_id'=>$delivery_id));
			$order_id = $order_id[0]['order_id'];
			$orderInfo = $objOrder->dump($order_id);

			if($orderInfo['shipping']['is_cod']=='true'){
				define('FRST_TRIGGER_OBJECT_TYPE','订单：订单通过AX取消');
				define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：do_cancel');
				$memo = "订单：订单通过AX取消";
				$mod = 'async';
				$sync_rs = $objOrder->cancel($order_id,$memo,true,$mod);
				$megentoInstance = kernel::service('service.magento_order');
				$megentoInstance->update_status($orderInfo['order_bn'],'canceled');
			}else{
				$objpayemntCfg = app::get('ome')->model('payment_cfg');
				$payment_id = $objpayemntCfg->getList('id',array('pay_bn'=>$orderInfo['pay_bn']));
				$shop_id = $objShop->getList('shop_id');
				$data = array(
						'order_id'=>$order_id,
						'shop_id'=>$shop[0]['shop_id'],
						'order_bn'=>$orderInfo['order_bn'],
						'pay_type'=>'online',
						'refund_money' => $orderInfo['payed'],
						'payment'=>$payment_id[0]['id'],
					);
				$return = kernel::single('ome_refund_apply')->refund_apply_add($data);
				kernel::single('ome_order_func')->update_order_pay_status($_POST['order_id']);

			}
			return true;
		}


		$query_params['item'] = json_encode($items);
		$query_params['sign'] = $this->_gen_sign($query_params,'');
error_log(var_export($query_params,true),3,__FILE__.'params.txt');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/index.php/api');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// post数据
		curl_setopt($ch, CURLOPT_POST, 1);
		// post的变量
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query_params);
		$output = curl_exec($ch);
		$info = json_decode($output,1);
		error_log(var_export($info,true),3,__FILE__.'params.txt');

		if ($info['rsp'] == 'succ') {
			//MCD换货走其他接口
			if($order_info[0]['is_mcd']=="true"&&$order_info[0]['createway']=="after"){
				$post=$arrReship=$arrOrders=array();
				$arrReship=$mdl_reship->getList("m_reship_bn,order_id",array('p_order_id'=>$order_info[0]['order_id']));
				
				$post['order_bn']=$order_info[0]['relate_order_bn'];//老的订单号
				$post['exchange_no']=$arrReship[0]['m_reship_bn'];
				$post['status']='shipped';
				$post['tracking_code']=$data['D'][0][11];
				$post['shipped_at']=date('Y-m-d H:i:s',time());
				kernel::single('omemagento_service_change')->updateStatus($post);
				//换出来的MCD订单判断是否还有其余未发货，如果有等到最后一笔发货后再开
				$arrOrders=$mdl_order->getList("order_id",array('relate_order_bn'=>$order_info[0]['relate_order_bn'],'ship_status'=>'0','is_mcd'=>'true','createway'=>'after'));
				if(empty($arrOrders[0]['order_id'])){
					kernel::single('einvoice_request_invoice')->invoice_request($arrReship[0]['order_id'],'getApplyInvoiceData');
				}
			}else{
				kernel::single('omemagento_service_order')->update_status($order_bn,'shipped',$data['D'][0][11]);
				kernel::single('einvoice_request_invoice')->invoice_request($order_info[0]['order_id'],'getApplyInvoiceData');
			}
			return true;
		}else{
			error_log(var_export($order_bn.',',true),3,__FILE__.'fail.txt');
			return false;
		}
	}

	private function _assemble($params){
        if(!is_array($params))  return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params as $key=>$val){
            if(is_null($val))   continue;
            if(is_bool($val))   $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? self::_assemble($val) : $val);
        }
        return $sign;
    }
    private function _gen_sign($params,$token){
        return strtoupper(md5(strtoupper(md5($this->_assemble($params))).$token));
    }


	
}