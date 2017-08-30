<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */
header("Content-type: text/html; charset=utf-8");

class ome_alipay_notify{
 	function __construct($app){
        $this->app = $app;
    }
	

	function doalipaynotify(){
		error_log("进入退款callback",3,DATA_DIR.'/alipayrefund/'.date("Ymd").'refund.txt');
		require_once("alipay.config.php");
		require_once("lib/alipay_notify.class.php");
		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		
		if($verify_result) {//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代
		
			
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			
			//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
			
			//批次号
		//echo "<pre>";print_r($_POST);
			$batch_no = $_POST['batch_no'];
		
			//批量退款数据中转账成功的笔数
		
			$success_num = $_POST['success_num'];
		
			//批量退款数据中的详细信息
			$result_details = $_POST['result_details'];
		
		
		 
			$arrDetails=explode('#',$result_details);
			if(!empty($arrDetails['0'])){
				foreach($arrDetails as $details){
					list($trade_no,$money,$result)=explode("^",$details);
					if($result=="SUCCESS"){//退款成功
						error_log("支付宝退款成功,流水号:".$trade_no."批次号:".$batch_no." ",3,DATA_DIR.'/alipayrefund/'.date("Ymd").'refund.txt');
						app::get('ome')->model('refund_apply')->updateAlipayRefund($batch_no,$trade_no,$money);
					}else{//退款失败
						error_log("支付宝退款失败,流水号:".$trade_no."批次号:".$batch_no."原因:".$result." ",3,DATA_DIR.'/alipayrefund/'.date("Ymd").'refund.txt');	
						app::get('ome')->model('refund_apply')->updateAlipayRefundFail($batch_no,$trade_no,$money);
					}
				}
			}
		 	echo "success";exit();
			//判断是否在商户网站中已经做过了这次通知返回的处理
				//如果没有做过处理，那么执行商户的业务程序
				//如果有做过处理，那么不执行商户的业务程序
				 
			
		
			//调试用，写文本函数记录程序运行情况是否正常
			//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		
			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
			
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}
		else {
			//验证失败
		 
			echo "fail";exit();
		
			//调试用，写文本函数记录程序运行情况是否正常
			//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}
	}
}
?>