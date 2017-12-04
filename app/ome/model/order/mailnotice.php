<?php

class ome_mdl_order_mailnotice extends dbeav_model{
   
	public function get_schema(){
        $schema = array (
            'columns' => array (
                'id' => array (
                    'type' => 'varchar(50)',
                    'pkey' => true,
                    'label' => 'id',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
					//	'searchtype' => 'has',
                    'default_in_list' => true,
                    'order'=>1,
                    'orderby' => true,
                    'realtype' => 'varchar(50)',
                ),
            ),
        );
        return $schema;
    }
	
	function sendMail($mailtitle,$mailcontent){
			$root_dir = realpath(dirname(__FILE__).'/../../');
			require_once($root_dir."/crontab/email.class.php");

			//$smtpserver = "smtp-n.global-mail.cn";
			//$smtpserverport = 25;
			//$smtpusermail = "service@shpca.org.cn";
			//$sender = "service@shpca.org.cn";
			$smtpserver = "smtp.sina.com";
			$smtpserverport = 25;
			$smtpusermail = "d1m_notice@sina.com";
			$sender = "d1m_notice@sina.com";
			$smtpemailto = "jasmine.yu@d1m.cn";
			
			
			//$mailcontent=nl2br(str_replace(" ","&nbsp;",htmlspecialchars_decode($mailcontent)));
			$mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
			//************************ 配置信息 ****************************
			$smtp = new smtp($smtpserver,$smtpserverport,true, "d1m_notice", "d1m123456");//这里面的一个true是表示使用身份
			$smtp->debug = false;//是否显示发送的调试信息
			$state = $smtp->sendmail($smtpemailto, $smtpusermail,$sender, $mailtitle, $mailcontent, $mailtype);

			if($state==""){
				return "对不起，邮件发送失败！请检查邮箱填写是否有误。";
				
			}
			return 1;
	}

	function sendFtpErrNotice(){
		$log_filename=realpath(dirname(__FILE__).'/../../').'/crontab/debug_ftp.log';
		$query_time = 15;	//minute
		$arrErrorLogList=$this->db->select("select ftp_log_id,work_type,status,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i:%s') as createtime,memo,file_local_route from sdb_omeftp_ftplog where (status!='succ' or memo!='上传成功！') and status!='prepare' and TIMESTAMPDIFF(MINUTE,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i:%s'), now() )<".$query_time." or status='prepare' and TIMESTAMPDIFF(MINUTE,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i:%s'), now() )>5 and TIMESTAMPDIFF(MINUTE,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i:%s'), now() )<60");
		//error_log(date("Y-m-d H:i:s")." sql结果：".sizeof($arrErrorOrderList)."\r\n",3,$log_filename);
		if (sizeof($arrErrorLogList)>0){
			$mail_detail = "<table style='border: 1px solid #ccc;border-collapse: collapse;'>".
								"<tr style='background-color: #b2e0ef;'>".
									"<td width='10%' style='border:solid 1px #ccc;font-size:12px;align:center;'>log_id</td>".
									"<td width='10%' style='border:solid 1px #ccc;font-size:12px;align:center;'>work_type</td>".
									"<td width='10%' style='border:solid 1px #ccc;font-size:12px;align:center;'>status</td>".
									"<td width='20%' style='border:solid 1px #ccc;font-size:12px;align:center;'>memo</td>".
									"<td width='20%' style='border:solid 1px #ccc;font-size:12px;align:center;'>createtime</td>".
									"<td width='30%' style='border:solid 1px #ccc;font-size:12px;align:center;'>file_local_route</td>".
								"</tr>";
			foreach($arrErrorLogList as $errLog){
				$mail_detail.="<tr>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errLog['ftp_log_id']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errLog['work_type']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errLog['status']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errLog['memo']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errLog['createtime']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;word-break:break-all'>".$errLog['file_local_route']."</td>";
				$mail_detail.="</tr>";
			}
			$mail_detail.="</table>";
			$mailtitle = "【Dior】LVMH ftp upload error!";
			$ret=$this->sendMail($mailtitle,$mail_detail);
		}
		
		//error_log(date("Y-m-d H:i:s")." 邮件发送结果：".$ret."\r\n",3,$log_filename);
	}

	function sendOrderSyncErrNotice(){
		$log_filename=realpath(dirname(__FILE__).'/../../').'/crontab/debug.log';
		$query_time = 10;	//minute
		$arrErrorOrderList=$this->db->select("select err_id,order_bn,err_msg,FROM_UNIXTIME(apitime,'%Y-%m-%d %H:%i:%s') as apitime,params from sdb_magentoapi_errororders where TIMESTAMPDIFF(MINUTE,FROM_UNIXTIME(apitime,'%Y-%m-%d %H:%i:%s'), now() )<".$query_time);
		//error_log(date("Y-m-d H:i:s")." sql结果：".sizeof($arrErrorOrderList)."\r\n",3,$log_filename);
		if (sizeof($arrErrorOrderList)>0){
			$mail_detail = "<table style='border: 1px solid #ccc;border-collapse: collapse;'><tr style='background-color: #b2e0ef;'><td width='10%' style='border:solid 1px #ccc;font-size:12px;align:center;'>订单号</td><td width='15%' style='border:solid 1px #ccc;font-size:12px;align:center;'>错误信息</td><td width='15%' style='border:solid 1px #ccc;font-size:12px;align:center;'>调用时间</td><td width='60%' style='border:solid 1px #ccc;font-size:12px;align:center;'>接口参数</td></tr>";
			foreach($arrErrorOrderList as $errOrder){
				$mail_detail.="<tr>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errOrder['order_bn']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errOrder['err_msg']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;'>".$errOrder['apitime']."</td>";
				$mail_detail.="<td style='border:solid 1px #ccc;font-size:12px;word-break:break-all'>".$errOrder['params']."</td>";
				$mail_detail.="</tr>";
			}
			$mail_detail.="</table>";
			$mailtitle = "【Dior】有新的接口错误订单";
			$ret=$this->sendMail($mailtitle,$mail_detail);
		}
		
		//error_log(date("Y-m-d H:i:s")." 邮件发送结果：".$ret."\r\n",3,$log_filename);
	}	
}
?>