<?php
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);
header("Content-type: text/html; charset=utf-8");

class ome_wxpay_refund{
	
	function __construct($app){
        $this->app = $app;
    }
	
	function doRefund($data){
		require_once("lib/WxPay.Api.php");
		require_once("log.php");
		//echo "<pre>2222";
		//print_r($data);print_r(WxPayConfig::SSLCERT_PATH);exit();
		// $data[0][0]['trade_no']='1001480956201602183360636253';
		//$data[0][0]['money']='0.01';
		//$data[0][0]['refund_fee']='0.001';
		/*
		$a[1]['transaction_id']='1009580644201510151204953435';
		$a[1]['total_fee']='1';
		$a[1]['refund_fee']='1';*/
	 	
		
		$oRefaccept = &$this->app->model('refund_apply');
		foreach($data as $refunds){
			foreach($refunds as $v){
				$r_id=$v['apply_id'];
				$Out_refund_no=substr($v['refund_apply_bn'],0,15).$v['apply_id'];
				
				if(!$oRefaccept->db->exec("UPDATE sdb_ome_refund_apply SET wxpaybatchno='$Out_refund_no' WHERE apply_id='$r_id'")){
					echo "微信退款申请:".$v['order_bn']."申请失败 ";
					continue;
				}
				$transaction_id = $v['trade_no'];
				$total_fee =bcmul($v['p_money'],100,0);
				$refund_fee =bcmul($v['money'],100,0);
				
				$input = new WxPayRefund();
				$input->SetTransaction_id($transaction_id);
				$input->SetTotal_fee($total_fee);
				$input->SetRefund_fee($refund_fee);
				$input->SetOut_refund_no($Out_refund_no);
				$input->SetOp_user_id(WxPayConfig::MCHID);
				$return=WxPayApi::refund($input);
				if($return['return_code']=="SUCCESS"){
					if($return['result_code']=="SUCCESS"){
						
						echo "微信退款申请:".$v['order_bn']."申请成功 ";
						error_log("微信退款申请:".$v['order_bn']."申请成功 ",3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
						$oRefaccept->db->exec("UPDATE sdb_ome_refund_apply SET wxstatus='true',status='5' WHERE apply_id='$r_id'");
					}else{
						error_log("微信退款申请:".$v['order_bn']."申请失败,原因:".$return['err_code_des'],3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
						echo "订单号:".$v['order_bn']."申请退款失败.原因:".$return['err_code_des']."<br>";
					}
				}else{
					error_log("微信退款申请:".$v['order_bn']."申2请失败,原因:".$return['return_msg'],3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
					echo "订单号:".$v['order_bn']."申请退款失败.原因:".$return['return_msg']."<br>";
				}
				//echo "<pre>111";print_r($return);
				//$this->printf_info(WxPayApi::refund($input));
			}
		}
		
		exit();
	}
	
	function checkRefund($data){//return true;
		require_once("lib/WxPay.Api.php");
		require_once("log.php");
		
		$oRefaccept = &$this->app->model('refund_apply');
		
		$transaction_id = $data["trade_no"];
		$apply_id=$data["apply_id"];
		$Out_refund_no=$data["wxpaybatchno"];
		
		$input = new WxPayRefundQuery();
		//$input->SetTransaction_id($transaction_id);//SetOut_refund_no
	/*	if($apply_id>14){
			$input->SetOut_refund_no($Out_refund_no);//SetOut_refund_no
		}else{
			$input->SetTransaction_id($transaction_id);
		}*/
		$input->SetOut_refund_no($Out_refund_no);
		$return=WxPayApi::refundQuery($input);
		error_log("微信退款返回:".json_encode($return),3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
		if($return['return_code']=="SUCCESS"){
			if($return['result_code']=="SUCCESS"){
			    switch ($return['refund_status_0']){
				    case 'SUCCESS'://退款成功
					    error_log("微信退款流水号:".$transaction_id."退款成功 ",3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
						return true;
              	    break;
					case 'FAIL'://退款失败
					    error_log("微信退款流水号:".$transaction_id."退款失败FAIL".$return['err_code'].$return['err_code_des']." ",3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
						$oRefaccept->db->exec('UPDATE sdb_ome_refund_apply SET apimsg="退款失败:'.$return['err_code'].$return['err_code_des'].'",stats="2",wxstatus="false" WHERE apply_id=$apply_id');
						return false;
              	    break;
					case 'NOTSURE'://退款失败
						error_log("微信退款流水号:".$transaction_id."退款失败NOTSURE".$return['err_code'].$return['err_code_des']." ",3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
						$oRefaccept->db->exec('UPDATE sdb_ome_refund_apply SET apimsg="退款失败:'.$return['err_code'].$return['err_code_des'].'",stats="2",wxstatus="false" WHERE apply_id=$apply_id');
						return false;
              	    break;
					case 'CHANGE'://退款失败
					    error_log("微信退款流水号:".$transaction_id."退款失败CHANGE".$return['err_code'].$return['err_code_des']." ",3,DATA_DIR.'/wxrefund/'.date("Ymd").'refund.txt');
						$oRefaccept->db->exec('UPDATE sdb_ome_refund_apply SET apimsg="退款失败:'.$return['err_code'].$return['err_code_des'].'",stats="2",wxstatus="false" WHERE apply_id=$apply_id');
						return false;
              	    break;
				}
			}else{
				$oRefaccept->db->exec('UPDATE sdb_ome_refund_apply SET apimsg="退款失败:'.json_endoce($return).'",stats="2",wxstatus="false" WHERE apply_id=$apply_id');
				return false;
			}
		}else{
			$oRefaccept->db->exec('UPDATE sdb_ome_refund_apply SET apimsg="退款失败:'.json_endoce($return).'",stats="2",wxstatus="false" WHERE apply_id=$apply_id');
			return false;
		}
		//echo "<pre>2";print_r($return);exit();
	}

	function printf_info($data)
	{
		foreach($data as $key=>$value){
			echo "<font color='#f00;'>$key</font> : $value <br/>";
		}
	}
}
?>
 