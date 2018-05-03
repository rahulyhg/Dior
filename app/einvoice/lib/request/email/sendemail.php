<?php
/**
* by www.phpddt.com
*/
//header("content-type:text/html;charset=utf-8");
ini_set("magic_quotes_runtime",0);
class einvoice_request_email_sendemail{
	function sendEmail($content=''){
		try {
			require 'class.phpmailer.php';
			if(empty($content)){
				$content='发票接口问题报警';
			}
			$mail = new PHPMailer(true); 
			$mail->IsSMTP();
			$mail->CharSet='UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
			$mail->SMTPAuth   = true;                  //开启认证
			$mail->Port       = 25;                    
			$mail->Host       = "smtp.sina.com"; 
			$mail->Username   = "lvmh_einvoice@sina.com";    
			$mail->Password   = "d1M123456";            
			//$mail->IsSendmail(); //如果没有sendmail组件就注释掉，否则出现“Could  not execute: /var/qmail/bin/sendmail ”的错误提示
			$mail->AddReplyTo("lvmh_einvoice@sina.com","GUE");//回复地址
			$mail->From       = "lvmh_einvoice@sina.com";
			$mail->FromName   = "lvmh_einvoice@sina.com";
			$to = "jinrong.zhang@d1m.cn";//"jasmine.yu@d1m.cn";
			$mail->AddAddress($to);
			$mail->Subject  = '电子发票接口预警';
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