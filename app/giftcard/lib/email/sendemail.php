<?php
/**
* by www.phpddt.com
*/
header("content-type:text/html;charset=utf-8");
ini_set("magic_quotes_runtime",0);
class giftcard_email_sendemail{
	function sendEmail($attach=array()){
		require_once('class.phpmailer.php');
	
		$mail = new PHPMailer(); //实例化
		$mail->IsSMTP(); // 启用SMTP
		$mail->Host = "smtp.sina.com"; //SMTP服务器 以163邮箱为例子
		$mail->Port = 25;  //邮件发送端口
		$mail->SMTPAuth   = true;  //启用SMTP认证

		$mail->CharSet  = "UTF-8"; //字符集
		$mail->Encoding = "base64"; //编码方式

		$mail->Username = "d1m_notice";  //你的邮箱
		$mail->Password = "d1m123456";  //你的密码
		$mail->Subject = "【Dior】auto sender: GiftCard-".date("Ymd"); //邮件标题

		$mail->From = "d1m_notice@sina.com";  //发件人地址（也就是你的邮箱）
		$mail->FromName = "d1m_notice";  //发件人姓名

	//	$mail->AddAddress("jinrong.zhang@d1m.cn");
		//$mail->AddAddress("joey.chen@d1m.cn");
		$mail->AddAddress("gigi.guo@d1m.cn");
		$mail->AddAddress("kathrine.zhou@d1m.cn");
		$mail->AddAddress("jasmine.yu@d1m.cn");
		$mail->AddAddress("dealer.dai@d1m.cn");
	//	$mail->AddAddress("abii.fan@d1m.cn");
	//	$mail->AddCC("jasmine.yu@d1m.cn");
		
		if(!empty($attach)){
			foreach($attach as $filename=>$filedir){
				$mail->AddAttachment($filedir,$filename);
			}
		}
		$mail->IsHTML(true); //支持html格式内容
		$mail->Body = "<font face='微软雅黑' size=2>Report</font>"; //邮件主体内容
		
		$mail->Send();
	}
}
?>