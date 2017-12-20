<?php
class giftcard_queue_statement
{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function run($queue){
		$objMath = kernel::single('eccommon_math');
		$objCard=kernel::single("giftcard_mdl_cards");
		$objStatement=app::get("ome")->model("statement");
		
		$order_bn=$queue['order_bn'];
		$arrCard_code=array();
		$arrCard_code=$objCard->getList("price,customer_code,p_order_id,p_order_bn",array('order_bn'=>$order_bn,'card_status'=>'redeem'));
		$arrCard_code=$arrCard_code[0];
		
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
		$card_setting    = app::get('giftcard')->getConf('giftcard_setting');
		
		$fee_rate=$card_setting['fee_rate']?$card_setting['fee_rate']:"0.006";
		$pay_fee=0;
		$pay_fee=$objMath->number_plus(array(round($fee_rate*$arrCard_code['price'],2),0));
		$paytime=date('d/m/Y',$queue['createtime']);
		$ax_h_customer_account=$ax_setting['ax_h_customer_account']?$ax_setting['ax_h_customer_account']:"C4010P1";
		
		$row=array();
		$row[]=$paytime.",".$ax_h_customer_account.",-".$objMath->number_plus(array($arrCard_code['price'],0)).",-".$objMath->number_plus(array($arrCard_code['price'],0)).",".$p_order_bn.",,wechatcard,PG4A,-".$pay_fee.",wechatcard reverse ".$p_order_bn." in ".$paytime;
		
		$row[]=$paytime.",".$arrCard_code['customer_code'].",".$objMath->number_plus(array($arrCard_code['price'],0)).",".$objMath->number_plus(array($arrCard_code['price'],0)).",".$p_order_bn.",,wechatcard,PG4A,".$pay_fee.",wechatcard redeem ".$p_order_bn." in ".$paytime;
		
		$content = implode("\n",$row);
		
		$file_brand = $ax_setting['ax_file_brand'];
		$file_prefix = $ax_setting['ax_file_prefix'];
		$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));
		$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		
		while(file_exists($file_name)){
			sleep(1);
			$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));
			$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		}
		$file = fopen($file_name,"w");
		$res = fwrite($file,$content);
		fclose($file);
		
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
			return true;
		}else{
			$objLog->update_log(array('status'=>'fail','memo'=>$msg),$ftp_log_id,'ftp');
			return false;
		}
	}
}
