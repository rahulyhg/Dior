 
<?php
/* *
 * 功能：即时到账批量退款有密接口接入页
 * 版本：3.3
 * 修改日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 *************************注意*************************
 * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
 * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
 * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
 * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
 * 如果不想使用扩展功能请把扩展功能参数赋空值。
 */
header("Content-type: text/html; charset=utf-8");

class ome_alipay_alipayapi{
	
	function __construct($app){
        $this->app = $app;
    }
	
	function checkbatchno(){
		$arrNum=array(1,2,3,4,5,6,7,8,9,0);
		$batch_no="000".$arrNum[rand(0,9)].$arrNum[rand(0,9)].$arrNum[rand(0,9)].$arrNum[rand(0,9)].$arrNum[rand(0,9)].$arrNum[rand(0,9)];
		
		$result=$this->app->model('refund_apply')->getList('alipaybatchno',array('alipaybatchno'=>$batch_no),0,1);
		
		if(!empty($result['0']['alipaybatchno'])){
			$this->checkbatchno();//exit();
		}
		return $batch_no;
		
	}
	
	function doRefund($data){
		//echo ALIPAYNOTIFYURL;exit();
		require_once("alipay.config.php");
		require_once("lib/alipay_submit.class.php");
/**************************请求参数**************************/
		$batch_no=$this->checkbatchno();
		
		$notify_url = app::get('ome')->getConf('ome.alipay.url');
		
        //服务器异步通知页面路径
		if(empty($notify_url)){
			$notify_url='http://ec-oms.dior.cn/app/ome/alipay/doNotify.php';
		}
        //卖家支付宝帐户
        $seller_email ='zfbdior@cn.lvmh-pc.com';// 'allen.yao@d1miao.com';$seller_email ='zfbguerlain@cn.lvmh-pc.com';// 'allen.yao@d1miao.com';
        //必填
        //退款当天日期
        $refund_date = date("Y-m-d H:i:s");
        //必填，格式：年[4位]-月[2位]-日[2位] 小时[2位 24小时制]:分[2位]:秒[2位]，如：2007-10-01 13:13:13

        //批次号
       // $batch_no = date("Ymd").time();
		$batch_no = date("Ymd").$batch_no;
        //必填，格式：当天日期[8位]+序列号[3至24位]，如：201008010000001

        //退款笔数
        $batch_num = count($data);
        //必填，参数detail_data的值中，“#”字符出现的数量加1，最大支持1000笔（即“#”字符出现的数量999个）

        //退款详细数据
        //$detail_data = '2015090821001004420082624017^0.10^不好看#2015040200001000420053375801^0.10^坏的';
		foreach($data as $k=>$v){
			foreach($v as $b){
				if(empty($b['memo'])){
					$detail_data.=$b['trade_no']."^".bcmul($b['money'],1,2)."^#";
				}else{
					$detail_data.=$b['trade_no']."^".bcmul($b['money'],1,2)."^".$b['memo']."#";
				}
			}
			
		}
		$detail_data=substr($detail_data,0,-1);
		
		//$detail_data = '2016011121001004250029487822^0.01^';
			//必填，具体格式请参见接口技术文档
	
	
		/************************************************************/
		
		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service" => "refund_fastpay_by_platform_pwd",
				"partner" => trim($alipay_config['partner']),
				"notify_url"	=> $notify_url,
				"seller_email"	=> $seller_email,
				"refund_date"	=> $refund_date,
				"batch_no"	=> $batch_no,
				"batch_num"	=> $batch_num,
				"detail_data"	=> $detail_data,
				"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
		);
		// echo "<pre>";print_r($parameter);print_r($detail_data);print_r($alipay_config);exit();
		//建立请求
		$alipaySubmit = new AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get","确认");
		$oRefaccept = &$this->app->model('refund_apply');
		foreach($data as $k=>$v){
			foreach($v as $b){
				$apply_id=$b['apply_id'];
				if(!$oRefaccept->db->exec("UPDATE sdb_ome_refund_apply SET status='5',alipaybatchno='$batch_no' WHERE apply_id='$apply_id'")){
					echo "请求出错请重试";exit();
				}
				$arr_bn.=$b['order_bn'].",";
				//echo "UPDATE sdb_ome_refund_apply SET stats='5',alipaybatchno='$batch_no' WHERE apply_id='$apply_id'";
			}
		}
		error_log("支付宝退款申请,订单号:".$arr_bn."批次号:".$batch_no." ",3,DATA_DIR.'/alipayrefund/'.date("Ymd").'refund.txt');
	//	echo "<pre>";print_r($data);exit();
		echo $html_text;
	}
}
?>
