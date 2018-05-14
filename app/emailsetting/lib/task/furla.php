<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
define('PHPEXCEL_ROOT', ROOT_DIR.'/app/omecsv/lib/static');
class emailsetting_task_furla{
    
    public $sign_name = '【Furla】';
    

    public function sendOrderError($order_bn,$msg){
        $send_bn = 'furla_order_error';

        $objSendList = app::get('emailsetting')->model('sendlist');
        
        $sendInfo = $objSendList->getList('*',array('send_bn'=>$send_bn));
        
        $senders = $sendInfo[0]['senders'];
        $subject = $this->sign_name.$sendInfo[0]['send_name'];

        $body = "<font face='微软雅黑' size=2>Hi All, <br/><br/>订单：$order_bn 无法进入系统<br/><br/>原因：$msg ,请查看原因<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>Furla OMS 开发团队<br/>".date("Y-m-d")."</font>";

        kernel::single('emailsetting_send')->send($senders,$subject,$body);
        
    }

    public function sendStoreInfo(){
        $send_bn = 'furla_store_count';
        $objSendList = app::get('emailsetting')->model('sendlist');
        $sendInfo = $objSendList->getList('*',array('send_bn'=>$send_bn)); 
        $senders = $sendInfo[0]['senders'];
        $subject = $this->sign_name.$sendInfo[0]['send_name'];
        
        $body = "<font face='微软雅黑' size=2>Hi All, <br/><br/>附件是凌晨OMS发给DW的库存报表 以及仓库给的库存为零的商品。<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>Furla OMS 开发团队<br/>".date("Y-m-d")."</font>";
        $files = $this->getStorFiles();
        //$files = array(0=>'F:/WWW/omsmk/data/report/Fular-store-report-20180307.xls');
        kernel::single('emailsetting_send')->send($senders,$subject,$body,$files);
        
    }


    public function getStorFiles(){
        require_once PHPEXCEL_ROOT.'/PHPExcel.php';
		require_once PHPEXCEL_ROOT.'/PHPExcel/Writer/Excel5.php';

		$objExcel = new PHPExcel();
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		$objProps = $objExcel->getProperties();
        
        $objBranchPro = app::get('ome')->model('branch_product');
        $info = $objBranchPro->db->select("SELECT p.bn,p.sbn,bp.store,bp.store_freeze from sdb_ome_branch_product as bp LEFT JOIN sdb_ome_products as p ON p.product_id=bp.product_id WHERE branch_id=5 and p.sbn>0 and p.price>0");
        
        $objProps->setCreator("OMS DEVELOPER");
		$objExcel->setActiveSheetIndex(0);
		$objActSheet = $objExcel->getActiveSheet();
		$objProps->setTitle("DW库存");
		$objActSheet->setCellValue('A1', '货号');
		$objActSheet->setCellValue('B1', '短货号');
		$objActSheet->setCellValue('C1', '库存');
        $objActSheet->setCellValue('D1', '冻结库存');
        $objActSheet->setCellValue('E1', '可售库存');
        
        $index=2;
        foreach($info as $val){
            $objActSheet->setCellValue('A'.$index,$val['bn']);
		    $objActSheet->setCellValue('B'.$index,$val['sbn']);
		    $objActSheet->setCellValue('C'.$index,$val['store']);
            $objActSheet->setCellValue('D'.$index,$val['store_freeze']);
            $objActSheet->setCellValue('E'.$index,$val['store']-$val['store_freeze']);
            $index++;
        }
        
        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(1);
		$objActSheetNone = $objExcel->getActiveSheet();
		$objProps->setTitle("DW库存");
		$objActSheetNone->setCellValue('A1', '货号');
		$objActSheetNone->setCellValue('B1', '仓库');
        $noneProInfo = $this->getNoneProduct();
        $index=2;
        foreach($noneProInfo as $value){
            $objActSheetNone->setCellValue('A'.$index,$value['product_bn']);
		    $objActSheetNone->setCellValue('B'.$index,$value['warehouse']);
            $index++;
        }
        $file_name = DATA_DIR.'/report/Fular-store-report-'.date("Ymd").'.xls';
        $objWriter->save($file_name);
        $files[] = $file_name;
        return $files;
        
    }

 
    public function getNoneProduct(){
		$storeLogMdl = app::get('syncttx')->model('store_log');
		$branchMdl = app::get('ome')->model('branch');
		$branchProductMdl = app::get('ome')->model('branch_product');
		
		$start_time = strtotime(date('Y-m-d',time()));
		$end_time = $start_time+60*60*24;
		$warehouseInfo = $storeLogMdl->db->select("select * from sdb_syncttx_store_log WHERE create_time<$end_time and create_time>$start_time and warehouse in ('FCNOMT','FCNOMS')  group by warehouse");
		foreach($warehouseInfo as $warehouse){
			$storeInfo = $storeLogMdl->db->select("select * from sdb_syncttx_store_log WHERE create_time<$end_time and create_time>$start_time and  warehouse='".$warehouse['warehouse']."'");
			//echo "<pre>";print_r($warehouse);exit;
			$storeArr = array();
			foreach($storeInfo as $val){
				$storeArr[$val['bn']] = $val['store'];
			}
			$branchInfo = $branchMdl->getList('*',array('branch_bn'=>$warehouse['warehouse']));
			$branch_id = $branchInfo[0]['branch_id'];

			$branchProductInfo = $branchProductMdl->db->select("select p.bn,bp.store,bp.store_freeze from sdb_ome_branch_product as bp LEFT JOIN sdb_ome_products as p ON p.product_id=bp.product_id WHERE bp.branch_id=$branch_id");

			//echo "<pre>";print_r($storeArr);exit;
			$need_sync_store_data = array();
			foreach($branchProductInfo as $pinfo){
				if(!isset($storeArr[$pinfo['bn']])){
					$products[] = array(
							'product_bn'=>$pinfo['bn'],
							 'warehouse'=>$warehouse['warehouse'],
						);
				}
			}
        }
        return $products;
    }
}