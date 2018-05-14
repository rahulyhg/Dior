<?php
/**
* by www.phpddt.com
*/
//header("content-type:text/html;charset=utf-8");
ini_set("magic_quotes_runtime",0);
class emailsetting_email_sendemail{
	function sendEmail($smtpSetting,$senders,$subject,$body,$files=array()){
		try {
			include_once 'class.phpmailer.php';
			
			$mail = new PHPMailer(true); 
			$mail->IsSMTP();
			$mail->CharSet='UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
          //  $mail->SMTPSecure='ssl';
			$mail->SMTPAuth   = true;                  //开启认证
			$mail->Port       = $smtpSetting['smtpport'];                    
			$mail->Host       = $smtpSetting['smtpserver']; 
			$mail->Username   = $smtpSetting['smtpuname'];    
			$mail->Password   = $smtpSetting['smtppasswd'];       
			//$mail->IsSendmail(); //如果没有sendmail组件就注释掉，否则出现“Could  not execute: /var/qmail/bin/sendmail ”的错误提示
			$mail->AddReplyTo($smtpSetting['usermail'],$smtpSetting['smtpuname']);//回复地址
			$mail->From       = $smtpSetting['usermail'];
			$mail->FromName   = $smtpSetting['usermail'];
//echo "<pre>";print_r($senders);exit;
			foreach($senders as $sender){
			    $mail->AddAddress($sender);
            }
			$mail->Subject  = $subject;
			$mail->Body = $body;
            
			$mail->WordWrap   = 80; // 设置每行字符串的长度
			//$mail->AddAttachment("f:/test.png");  //可以添加附件

			$mail->IsHTML(true); 
 //echo "<pre>";print_r($files);exit;
            if(!empty($files)){
                foreach($files as $file){
                    $mail->AddAttachment($file,basename($file));
                }
            }
           
			if($mail->Send()){
                if(!empty($files)){
                    foreach($files as $file){
                        @unlink($file);
                    }
                }
            }
		} catch (phpmailerException $e) {
            echo "邮件发送失败：".$e->errorMessage();
		}
	}
}
?>