<?php
/**
* by www.phpddt.com
*/
header("content-type:text/html;charset=utf-8");
ini_set("magic_quotes_runtime",0);
class erpapi_oms_email_sendemail{
	function sendEmail($content=''){
		try {
			require 'class.phpmailer.php';
			if(empty($content)){
				$content='dior订单错误,请查看';
			}
			$mail = new PHPMailer(true); 
			$mail->IsSMTP();
			$mail->CharSet='UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
			$mail->SMTPAuth   = true;                  //开启认证
			$mail->Port       = 25;                    
			$mail->Host       = "smtp.163.com"; 
			$mail->Username   = "zjrlxp@163.com";    
			$mail->Password   = "admin123";            
			//$mail->IsSendmail(); //如果没有sendmail组件就注释掉，否则出现“Could  not execute: /var/qmail/bin/sendmail ”的错误提示
			$mail->AddReplyTo("zjrlxp@163.com","zjr");//回复地址
			$mail->From       = "zjrlxp@163.com";
			$mail->FromName   = "zjrlxp@163.com";
			$to = "jasmine.yu@d1m.cn";//"jasmine.yu@d1m.cn";
			$mail->AddAddress($to);
			$mail->Subject  = $content;
			$mail->Body = $content;
			$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; //当邮件不支持html时备用显示，可以省略
			$mail->WordWrap   = 80; // 设置每行字符串的长度
			//$mail->AddAttachment("f:/test.png");  //可以添加附件
			$mail->IsHTML(true); 
			$mail->Send();
			//echo '邮件已发送';
		} catch (phpmailerException $e) {
			// echo "邮件发送失败：".$e->errorMessage();
		}
	}
}
?>