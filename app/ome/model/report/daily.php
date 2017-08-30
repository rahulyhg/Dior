<?php
define('PHPEXCEL_ROOT', ROOT_DIR.'/app/omecsv/lib/static');

class ome_mdl_report_daily extends dbeav_model{

	function sendMail($attach1,$attach2,$attach3){
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
		$mail->Subject = "【Dior】auto sender: order report & sample stock ".date("Ymd"); //邮件标题

		$mail->From = "d1m_notice@sina.com";  //发件人地址（也就是你的邮箱）
		$mail->FromName = "d1m_notice";  //发件人姓名

		$mail->AddAddress("joey.chen@d1m.cn");
		$mail->AddAddress("maik.chang@d1m.cn");
		$mail->AddCC("jasmine.yu@d1m.cn");
		
		//$mail->AddAddress("jasmine.yu@d1m.cn");

		$attach_file1 = realpath(dirname(__FILE__)).'/'.$attach1;
		$mail->AddAttachment($attach_file1,$attach1); // 添加附件,并指定名称
		$attach_file2 = realpath(dirname(__FILE__)).'/'.$attach2;
		$mail->AddAttachment($attach_file2,$attach2); // 添加附件,并指定名称
		if ($attach3!=""){
			$attach_file3 = realpath(dirname(__FILE__)).'/'.$attach3;
			$mail->AddAttachment($attach_file3,$attach3); // monthly report
		}


		$mail->IsHTML(true); //支持html格式内容
		if ($attach3==""){
			$mail->Body = "<font face='微软雅黑' size=2>Hi All, <br/><br/>附件是昨天发货和退款完成的order report，以及当前所有小样的库存，请查收。<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>Dior OMS 开发团队<br/>".date("Y-m-d")."</font>"; //邮件主体内容
		}else{
			$mail->Body = "<font face='微软雅黑' size=2>Hi All, <br/><br/>附件是昨天发货和退款完成的order report，以及当前所有小样的库存，请查收。<br/><br/>另外今天是月初，附件也提供了上个月的报表，请注意查收。<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>Dior OMS 开发团队<br/>".date("Y-m-d")."</font>"; 

		}

		//发送
		if(!$mail->Send()) {
			if (file_exists($attach_file1)){
				$result = @unlink($attach_file1);
			}
			if (file_exists($attach_file2)){
				$result = @unlink($attach_file2);
			}
			if (file_exists($attach_file3)){
				$result = @unlink($attach_file3);
			}
			return "发送失败: " . $mail->ErrorInfo;
		} else {
			if (file_exists($attach_file1)){
				$result = @unlink($attach_file1);
			}
			if (file_exists($attach_file2)){
				$result = @unlink($attach_file2);
			}
			if (file_exists($attach_file3)){
				$result = @unlink($attach_file3);
			}
			return "1";
		}
	}

	function sendExportData(){
		//modified by Jasmine 2016-06-03 部分退货修改
		$log_filename=realpath(dirname(__FILE__)).'/debug.log';
		$sql = "select  FROM_UNIXTIME( sdb_ome_orders.createtime, '%Y-%m-%d %H:%i:%s' ) as createtime,
				FROM_UNIXTIME( sdb_ome_delivery.delivery_time, '%Y-%m-%d' ) as delivery_time, 
				'已发货' as ship_status,
				sdb_ome_orders.order_bn as order_bn,
				IFNULL(sdb_ome_orders.payment,sdb_ome_orders.pay_bn) as payment,
				sdb_ome_orders.ship_name as ship_name,left(REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''),locate('/',REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''))-1) as ship_area1,left(substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1),locate('/',substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1))-1) as ship_area2,
				sdb_ome_order_items.bn as bn,
				sdb_ome_order_items.name as name,
				sdb_ome_order_items.nums as nums,
				sdb_ome_order_items.true_price as true_price,
				sdb_ome_order_items.amount as amount   from sdb_ome_order_items
				left join sdb_ome_orders on sdb_ome_order_items.order_id=sdb_ome_orders.order_id 
				left join sdb_ome_delivery_order on sdb_ome_order_items.order_id=sdb_ome_delivery_order.order_id 
				left join sdb_ome_delivery on sdb_ome_delivery.delivery_id=sdb_ome_delivery_order.delivery_id  
				where sdb_ome_delivery.deliv_status=true and  sdb_ome_delivery.delivery_time>=UNIX_TIMESTAMP('".date("Y-m-d",strtotime("-1 day"))." 00:00:00')  and sdb_ome_delivery.delivery_time<UNIX_TIMESTAMP('".date("Y-m-d")." 00:00:00')";
		$sql .= " union ";
		$sql .= "select  FROM_UNIXTIME( sdb_ome_orders.createtime, '%Y-%m-%d %H:%i:%s' ) as createtime,
				FROM_UNIXTIME(sdb_ome_refunds.t_ready, '%Y-%m-%d %H:%i:%s' ) as delivery_time, 
				if(sdb_ome_orders.ship_status='4','已退货','部分退货') as ship_status,
				sdb_ome_orders.order_bn as order_bn,
				IFNULL(sdb_ome_orders.payment,sdb_ome_orders.pay_bn) as payment,
				sdb_ome_orders.ship_name as ship_name,left(REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''),locate('/',REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''))-1) as ship_area1,left(substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1),locate('/',substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1))-1) as ship_area2,
				sdb_ome_reship_items.bn as bn,
				sdb_ome_reship_items.product_name as name,
				sdb_ome_reship_items.num as nums,
				sdb_ome_reship_items.price as true_price ,
				sdb_ome_reship_items.price*sdb_ome_reship_items.num as amount 
				from sdb_ome_reship_items 
				left join sdb_ome_reship on sdb_ome_reship.reship_id=sdb_ome_reship_items.reship_id  
				left join sdb_ome_orders on sdb_ome_orders.order_id=sdb_ome_reship.order_id 
				left join sdb_ome_refunds on sdb_ome_reship.order_id=sdb_ome_refunds.order_id 
				where sdb_ome_refunds.t_ready>=UNIX_TIMESTAMP('".date("Y-m-d",strtotime("-1 day"))." 00:00:00')  and sdb_ome_refunds.t_ready<UNIX_TIMESTAMP('".date("Y-m-d")." 00:00:00') ";
		$sql .= " union ";
		$sql .="select  FROM_UNIXTIME( sdb_ome_orders.createtime, '%Y-%m-%d %H:%i:%s' ) as createtime,
				FROM_UNIXTIME(sdb_ome_reship.t_end, '%Y-%m-%d %H:%i:%s' ) as delivery_time, 
				'COD拒签' as ship_status,
				sdb_ome_orders.order_bn as order_bn,
				IFNULL(sdb_ome_orders.payment,sdb_ome_orders.pay_bn) as payment,
				sdb_ome_orders.ship_name as ship_name,left(REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''),locate('/',REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''))-1) as ship_area1,left(substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1),locate('/',substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1))-1) as ship_area2,
				sdb_ome_reship_items.bn as bn,
				sdb_ome_reship_items.product_name as name,
				sdb_ome_reship_items.num as nums,
				sdb_ome_reship_items.price as true_price ,
				sdb_ome_reship_items.price*sdb_ome_reship_items.num as amount 
				from sdb_ome_reship_items 
				left join sdb_ome_reship on sdb_ome_reship.reship_id=sdb_ome_reship_items.reship_id  
				left join sdb_ome_orders on sdb_ome_orders.order_id=sdb_ome_reship.order_id 
				where sdb_ome_orders.pay_status='0' and sdb_ome_reship.t_end>=UNIX_TIMESTAMP('".date("Y-m-d",strtotime("-1 day"))." 00:00:00')  and sdb_ome_reship.t_end<UNIX_TIMESTAMP('".date("Y-m-d")." 00:00:00') order by createtime, order_bn";
		//error_log(date("Y-m-d H:i:s")." sql>>>>".$sql."\r\n",3,$log_filename);
		$arrOrderList=$this->db->select($sql);
		//error_log(date("Y-m-d H:i:s")." sql结果：".sizeof($arrOrderList)."\r\n",3,$log_filename);

		ini_set('memory_limit','-1');

		//生成Excel文件
		$ExcelFile1 = $this->createExcel($arrOrderList);

		$sql = "select bn,name,store from sdb_ome_products where price=0 order by product_id";
		$arrSampleDataList=$this->db->select($sql);
		$ExcelFile2 = $this->createSampleStockExcel($arrSampleDataList);

				//生成月报
		$ExcelFile3="";
		$sql = $this->getMonthlyReportSQL();
		if ($sql!=""){
			//error_log(date("Y-m-d H:i:s")." sql>>>>".$sql."\r\n",3,$log_filename);
			$arrMonthlyReportDataList=$this->db->select($sql);
			$ExcelFile3 = $this->createMonthlyReportExcel($arrMonthlyReportDataList);
		}

		$ret = $this->sendMail($ExcelFile1,$ExcelFile2,$ExcelFile3);
		
		//error_log(date("Y-m-d H:i:s")." 邮件发送结果：".$ret."\r\n",3,$log_filename);
	}
	
	function createExcel($orders){
		require_once PHPEXCEL_ROOT.'/PHPExcel.php';
		require_once PHPEXCEL_ROOT.'/PHPExcel/Writer/Excel5.php';

		$objExcel = new PHPExcel();
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		$objProps = $objExcel->getProperties();   
		
		//$objProps->setTitle("excel test");   
		//$objProps->setSubject("my excel test");
		$objProps->setCreator("Jasmine");

		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle(date("Ymd",strtotime("-1 day"))); 
		$objActSheet->setCellValue('A1', '打印日期');
		$objActSheet->setCellValue('B1', '发货/退货时间');
		$objActSheet->setCellValue('C1', '订单编号');
		$objActSheet->setCellValue('D1', '付款方式');
		$objActSheet->setCellValue('E1', '收货人手机');
		$objActSheet->setCellValue('F1', '收货人');
		$objActSheet->setCellValue('G1', '省份');
		$objActSheet->setCellValue('H1', '城市');
		$objActSheet->setCellValue('I1', '产品编号');
		$objActSheet->setCellValue('J1', '产品名称');
		$objActSheet->setCellValue('K1', '数量');
		$objActSheet->setCellValue('L1', '单价');
		$objActSheet->setCellValue('M1', '金额');
		$objActSheet->setCellValue('N1', '发货/退货');

		$i = 1;
		foreach($orders as $order){
			$i++;
			$objActSheet->setCellValue('A'.$i, $order["createtime"]);
			$objActSheet->setCellValue('B'.$i, $order["delivery_time"]);
			$objActSheet->setCellValue('C'.$i, $order["order_bn"]);
			$objActSheet->setCellValue('D'.$i, $order["payment"]);
			$objActSheet->setCellValue('E'.$i, $order["ship_mobile"]);
			$objActSheet->setCellValue('F'.$i, $order["ship_name"]);
			$objActSheet->setCellValue('G'.$i, $order["ship_area1"]);
			$objActSheet->setCellValue('H'.$i, $order["ship_area2"]);
			$objActSheet->setCellValue('I'.$i, $order["bn"]);
			$objActSheet->setCellValue('J'.$i, $order["name"]);
			$objActSheet->setCellValue('K'.$i, $order["nums"]);
			$objActSheet->setCellValue('L'.$i, $order["true_price"]);
			$objActSheet->setCellValue('M'.$i, $order["amount"]);
			$objActSheet->setCellValue('N'.$i, $order["ship_status"]);

		}
		$objWriter->save(realpath(dirname(__FILE__)).'/Dior-order report-'.date("Ymd").'.xls');   
		return 'Dior-order report-'.date("Ymd").'.xls';
		//copy excel format
		/*$objReader = PHPExcel_IOFactory::createReader('Excel5');
		$objPHPExcel = $objReader->load('/home/yuanjianjun/20100301.xls');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->_phpExcel->setActiveSheetIndex(0);
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('A1', 'FESDF');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('B1', 'S');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('C1', 'FEFSD');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('D1', 'SDFD');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('E1', '淘宝CPS');
		$objWriter->save('/home/yuanjianjun/copy.xls');*/

	}

	function createSampleStockExcel($data){
		require_once PHPEXCEL_ROOT.'/PHPExcel.php';
		require_once PHPEXCEL_ROOT.'/PHPExcel/Writer/Excel5.php';

		$objExcel = new PHPExcel();
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		$objProps = $objExcel->getProperties();   
		
		//$objProps->setTitle("excel test");   
		//$objProps->setSubject("my excel test");
		$objProps->setCreator("Jasmine");

		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle(date("Ymd")); 
		$objActSheet->setCellValue('A1', 'SKU');
		$objActSheet->setCellValue('B1', '名称');
		$objActSheet->getColumnDimension('B')->setWidth(48);
		$objActSheet->setCellValue('C1', '库存 - '.date("md"));
		$objActSheet->getColumnDimension('C')->setWidth(15);

		$objExcel->getDefaultStyle()->getFont()->setName('Arial');
		$objExcel->getDefaultStyle()->getFont()->setSize(10); 

		$i = 1;
		foreach($data as $item){
			$i++;
			$objActSheet->setCellValue('A'.$i, $item["bn"]);
			$objActSheet->setCellValue('B'.$i, $item["name"]);
			$objActSheet->setCellValue('C'.$i, $item["store"]);
		}
		$objWriter->save(realpath(dirname(__FILE__)).'/Dior-current samples stock-'.date("Ymd").'.xls');   
		return 'Dior-current samples stock-'.date("Ymd").'.xls';
		//copy excel format
		/*$objReader = PHPExcel_IOFactory::createReader('Excel5');
		$objPHPExcel = $objReader->load('/home/yuanjianjun/20100301.xls');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->_phpExcel->setActiveSheetIndex(0);
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('A1', 'FESDF');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('B1', 'S');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('C1', 'FEFSD');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('D1', 'SDFD');
		$objWriter->_phpExcel->getActiveSheet()->setCellValue('E1', '淘宝CPS');
		$objWriter->save('/home/yuanjianjun/copy.xls');*/

	}

	function getMonthlyReportSQL(){
		if (date("d")!="01"){
			return "";
		}
		$sql = "select  FROM_UNIXTIME( sdb_ome_orders.createtime, '%Y-%m-%d %H:%i:%s' ) as createtime,
				FROM_UNIXTIME( sdb_ome_delivery.delivery_time, '%Y-%m-%d' ) as delivery_time, 
				'已发货' as ship_status,
				sdb_ome_orders.order_bn as order_bn,
				IFNULL(sdb_ome_orders.payment,sdb_ome_orders.pay_bn) as payment,sdb_ome_orders.ship_mobile,
				sdb_ome_orders.ship_name as ship_name,left(REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''),locate('/',REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''))-1) as ship_area1,left(substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1),locate('/',substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1))-1) as ship_area2,
				sdb_ome_order_items.bn as bn,
				sdb_ome_order_items.name as name,
				sdb_ome_order_items.nums as nums,
				sdb_ome_order_items.true_price as true_price,
				sdb_ome_order_items.amount as amount from sdb_ome_order_items
				left join sdb_ome_orders on sdb_ome_order_items.order_id=sdb_ome_orders.order_id 
				left join sdb_ome_delivery_order on sdb_ome_order_items.order_id=sdb_ome_delivery_order.order_id 
				left join sdb_ome_delivery on sdb_ome_delivery.delivery_id=sdb_ome_delivery_order.delivery_id 
				where sdb_ome_delivery.deliv_status=true and  sdb_ome_delivery.delivery_time>=UNIX_TIMESTAMP('".date("Y-m-01",strtotime("last month"))." 00:00:00') and sdb_ome_delivery.delivery_time<UNIX_TIMESTAMP('".date('Y-m-01')." 00:00:00')";
		$sql .= " union ";
		$sql .= "select  FROM_UNIXTIME( sdb_ome_orders.createtime, '%Y-%m-%d %H:%i:%s' ) as createtime,
				FROM_UNIXTIME(sdb_ome_reship.t_end, '%Y-%m-%d %H:%i:%s' ) as delivery_time, 
				if(sdb_ome_orders.ship_status='4','已退货','部分退货') as ship_status,
				sdb_ome_orders.order_bn as order_bn,
				IFNULL(sdb_ome_orders.payment,sdb_ome_orders.pay_bn) as payment,sdb_ome_orders.ship_mobile,
				sdb_ome_orders.ship_name as ship_name,left(REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''),locate('/',REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''))-1) as ship_area1,left(substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1),locate('/',substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1))-1) as ship_area2,
				sdb_ome_reship_items.bn as bn,
				sdb_ome_reship_items.product_name as name,
				sdb_ome_reship_items.num as nums,
				sdb_ome_reship_items.price as true_price ,
				sdb_ome_reship_items.price*sdb_ome_reship_items.num as amount 
				from sdb_ome_reship_items 
				left join sdb_ome_reship on sdb_ome_reship.reship_id=sdb_ome_reship_items.reship_id  
				left join sdb_ome_orders on sdb_ome_orders.order_id=sdb_ome_reship.order_id 
				left join sdb_ome_refunds on sdb_ome_reship.order_id=sdb_ome_refunds.order_id 
				where sdb_ome_refunds.t_ready>=UNIX_TIMESTAMP('".date("Y-m-01",strtotime("last month"))." 00:00:00')  and sdb_ome_refunds.t_ready<UNIX_TIMESTAMP('".date("Y-m-01")." 00:00:00') ";
		$sql .= " union ";
		$sql .="select  FROM_UNIXTIME( sdb_ome_orders.createtime, '%Y-%m-%d %H:%i:%s' ) as createtime,
				FROM_UNIXTIME(sdb_ome_reship.t_end, '%Y-%m-%d %H:%i:%s' ) as delivery_time, 
				'COD拒签' as ship_status,
				sdb_ome_orders.order_bn as order_bn,
				IFNULL(sdb_ome_orders.payment,sdb_ome_orders.pay_bn) as payment,sdb_ome_orders.ship_mobile,
				sdb_ome_orders.ship_name as ship_name,left(REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''),locate('/',REPLACE(sdb_ome_orders.ship_area, 'mainland:', ''))-1) as ship_area1,left(substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1),locate('/',substring(sdb_ome_orders.ship_area,locate('/',sdb_ome_orders.ship_area)+1))-1) as ship_area2,
				sdb_ome_reship_items.bn as bn,
				sdb_ome_reship_items.product_name as name,
				sdb_ome_reship_items.num as nums,
				sdb_ome_reship_items.price as true_price ,
				sdb_ome_reship_items.price*sdb_ome_reship_items.num as amount 
				from sdb_ome_reship_items 
				left join sdb_ome_reship on sdb_ome_reship.reship_id=sdb_ome_reship_items.reship_id  
				left join sdb_ome_orders on sdb_ome_orders.order_id=sdb_ome_reship.order_id 
				where sdb_ome_orders.pay_status='1' and sdb_ome_reship.t_end>=UNIX_TIMESTAMP('".date("Y-m-01",strtotime("last month"))." 00:00:00')  and sdb_ome_reship.t_end<UNIX_TIMESTAMP('".date("Y-m-01")." 00:00:00') order by createtime, order_bn";
			return $sql;
	}

}
?>