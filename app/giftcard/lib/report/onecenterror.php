<?php
define('PHPEXCEL_ROOT', ROOT_DIR.'/app/omecsv/lib/static');
class giftcard_report_onecenterror{
	
	public function run(){
		$objGift=kernel::single("giftcard_mdl_cards");
		$arrFirstReport=$arrSecondReport=array();
		$urlFirstReport=$urlSecondReport='';
		
		$sql="SELECT
			FROM_UNIXTIME(c.redeemtime) AS redeemtime,
			c.wx_order_bn,
			c.order_bn,
			oo.total_amount,
			m.uname,
			c.card_code,
			1 AS nums,
			c.price,
			g.bn
		FROM
			`sdb_giftcard_cards` c
		LEFT JOIN sdb_ome_orders o ON o.order_id = c.p_order_id
		LEFT JOIN sdb_ome_orders oo ON oo.order_id = c.order_id
		LEFT JOIN sdb_ome_members m ON o.member_id = m.member_id
		LEFT JOIN sdb_ome_payments p ON p.order_id = o.order_id
		LEFT JOIN sdb_ome_goods g ON g.card_id = c.card_id
		WHERE
			c.price < 1
		AND c.`status` IN ('redeem')";
		$arrFirstReport=$objGift->db->select($sql);
		
		$urlFirstReport=$this->createFirstReportExcel($arrFirstReport);
		
		$sql="SELECT
			c.wx_order_bn,
			c.p_order_bn,
			c.card_code,
			c.`status`,
			FROM_UNIXTIME(c.createtime),
			m.uname,
			g.bn
		FROM
			`sdb_giftcard_cards` c
		LEFT JOIN sdb_ome_orders o ON o.order_id = c.p_order_id
		LEFT JOIN sdb_ome_members m ON o.member_id = m.member_id
		LEFT JOIN sdb_ome_goods g ON g.card_id = c.card_id
		WHERE
			c.price < 1";
		$arrSecondReport=$objGift->db->select($sql);
		
		$urlSecondReport=$this->createSecondReportExcel($arrSecondReport);
		
		$arrEmail=array();
		$arrEmail['FirstReport-'.date("Ymd").'.xls']=$urlFirstReport;
		$arrEmail['SecondReport-'.date("Ymd").'.xls']=$urlSecondReport;
		kernel::single("giftcard_email_sendemail")->sendEmail($arrEmail);
	}
	
	function createFirstReportExcel($arrFirstReport){
		require_once PHPEXCEL_ROOT.'/PHPExcel.php';
		require_once PHPEXCEL_ROOT.'/PHPExcel/Writer/Excel5.php';
		
		$reportDir=realpath(dirname(__FILE__)).'/FirstReport/';
		if(!is_dir($reportDir)){ 
			mkdir($reportDir,0777,true);
			chmod($reportDir,0777);
		}
		
		$objExcel = new PHPExcel();
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		$objProps = $objExcel->getProperties();   
		
		$objProps->setCreator("zjr");
		
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle(date("Ymd")); 
		$objActSheet->setCellValue('A1', '兑换日期');
		$objActSheet->setCellValue('B1', '购卡订单微信订单号');
		$objActSheet->setCellValue('C1', '兑换订单号');
		$objActSheet->setCellValue('D1', 'SKU');
		$objActSheet->setCellValue('E1', '购卡人昵称');
		$objActSheet->setCellValue('F1', 'Card Code');
		$objActSheet->setCellValue('G1', '兑换数量');
		$objActSheet->setCellValue('H1', '兑换金额');
		
		$i = 1;
		foreach($arrFirstReport as $data){
			$i++;
			$objActSheet->setCellValue('A'.$i, $data["redeemtime"]);
			$objActSheet->setCellValue('B'.$i, $data["wx_order_bn"]);
			$objActSheet->setCellValue('C'.$i, $data["order_bn"]);
			$objActSheet->setCellValue('D'.$i, $data["bn"]);
			$objActSheet->setCellValue('E'.$i, $data["uname"]);
			$objActSheet->setCellValue('F'.$i, $data["card_code"]);
			$objActSheet->setCellValue('G'.$i, 1);
			$objActSheet->setCellValue('H'.$i, $data["total_amount"]);
		}
		
		$filename=$reportDir.'FirstReport-'.date("Ymd").'.xls';
		$objWriter->save($filename);
		
		return $filename;
	}
	
	public function createSecondReportExcel($arrSecondReport){
		require_once PHPEXCEL_ROOT.'/PHPExcel.php';
		require_once PHPEXCEL_ROOT.'/PHPExcel/Writer/Excel5.php';
		
		$reportDir=realpath(dirname(__FILE__)).'/SecondReport/';
		if(!is_dir($reportDir)){ 
			mkdir($reportDir,0777,true);
			chmod($reportDir,0777);
		}
		
		$objExcel = new PHPExcel();
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		$objProps = $objExcel->getProperties();   
		
		$objProps->setCreator("zjr");
		
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objActSheet->setTitle(date("Ymd")); 
		$objActSheet->setCellValue('A1', '微信订单号');
		$objActSheet->setCellValue('B1', '购卡订单号');
		$objActSheet->setCellValue('C1', 'Card Code');
		$objActSheet->setCellValue('D1', '卡劵状态');
		$objActSheet->setCellValue('E1', '购卡人昵称');
		$objActSheet->setCellValue('F1', '购卡时间');
		$objActSheet->setCellValue('G1', 'SKU');
		$i = 1;
		foreach($arrSecondReport as $data){
			$i++;
			$objActSheet->setCellValue('A'.$i, $data["wx_order_bn"]);
			$objActSheet->setCellValue('B'.$i, $data["p_order_bn"]);
			$objActSheet->setCellValue('C'.$i, $data["card_code"]);
			$objActSheet->setCellValue('D'.$i, $data["status"]);
			$objActSheet->setCellValue('E'.$i,$data["uname"]);
			$objActSheet->setCellValue('G'.$i,$data["bn"]);
		}
		
		$filename=$reportDir.'SecondReport-'.date("Ymd").'.xls';
		$objWriter->save($filename);
		
		return $filename;
	}
	
}
?>