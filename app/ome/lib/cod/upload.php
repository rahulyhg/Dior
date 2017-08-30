<?php
class ome_cod_upload{
function __construct($app){
        $this->app = $app;
    }
	//private $format = 'json';
	public function doUpload($arrTrade_no){
		 $this->charset = kernel::single('base_charset');
		 $name=time()."-ccb.txt";
				$dirname=dirname(dirname(__FILE__))."/ccb";
				$filename=$dirname."/".$name;//文件名
				$OPN='路威酩轩香水化妆品(上海)有限公司';//'7758258';
				$DAN='31001577914050007531';//'ZHANG JIN RONG';
				if(!file_exists($dirname)){
					if(!mkdir($dirname,0777)){
						echo "创建目录失败";exit();
					}
				}
				$i=1;
				
				foreach($arrTrade_no as $k=>$v){
					foreach($v as $b){
				/*	$b['pay_account']='6227001214920053716';
					$b['BeneficiaryName']='张津榕';
					$b['money']='0.01';
					$b['BeneficiaryBankName']='上海市分行';
					$b['apply_id']=$b['apply_id'];
					
					$b['pay_account']='6227001240680086703';
					$b['BeneficiaryName']='李俊';
					$b['money']='0.01';
					$b['BeneficiaryBankName']='江苏省分行';
					$b['apply_id']=$b['apply_id'];
					
					$b['pay_account']='6214920601466152';
					$b['BeneficiaryName']='张津榕';
					$b['money']='0.01';
					$b['BeneficiaryBankName']='上海光大银行';
					$b['apply_id']=$b['apply_id'];
					
					
				*/	
					$b['BeneficiaryName']=$this->charset->utf2local($b['BeneficiaryName']);
					$b['BankName']=$this->charset->utf2local($b['BankName']);
					$b['BeneficiaryBankName']=$this->charset->utf2local($b['BeneficiaryBankName']);
						if($b['isk']=="1"){//跨行
							$Uploadstring.=$i."|".$OPN."|".$DAN."|310000000|".$b['pay_account']."|".$b['BeneficiaryName']."||".$b['BankName'].$b['BeneficiaryBankName']."|||0|".bcadd($b['money'],0,2)."|01|pcd".$b['apply_id']."\r\n";//跨行
							$i++;
							break;
						}
						if($b['iss']=="1"&&$b['isk']=="0"){//上海建行
							$Uploadstring.=$i."|".$OPN."|".$DAN."|310000000|".$b['pay_account']."|".$b['BeneficiaryName']."|622000000|".$b['BeneficiaryBankName']."|52364||1|".bcadd($b['money'],0,2)."|01|pcd".$b['apply_id']."\r\n";//上海建行
							$i++;
							break;
						}
						if($b['iss']=="0"&&$b['isk']=="0"){//外地建行
							$Uploadstring.=$i."|".$OPN."|".$DAN."|310000000|".$b['pay_account']."|".$b['BeneficiaryName']."|622000000|".$b['BeneficiaryBankName']."|||1|".bcadd($b['money'],0,2)."|01|pcd".$b['apply_id']."\r\n";//外地建行
							$i++;
							break;
						}
						
					}
				}
				 
				 //echo "<pre>";print_r($Uploadstring);exit();
				//@fopen($filename,"w");
			 //	file_put_contents($filename,iconv('utf-8','gb2312',$Uploadstring));
			//	file_put_contents($filename,$Uploadstring);
				
				//下载
				header("Content-Type: application/octet-stream");  
				if (preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT']) ) {  
					header('Content-Disposition:attachment;filename="'.$name.'"');  
				} elseif (preg_match("/Firefox/", $_SERVER['HTTP_USER_AGENT'])) {  
					header('Content-Disposition:attachment;filename*="'.$name.'"');
				} else {  
					header('Content-Disposition:attachment;filename="'.$name .'"');  
				}
				echo $Uploadstring;exit();
				/*$file=@fopen($filename, "rb");
				$contents = "";
				while (!feof($file)) {
					$contents .= fread($file,8192);
				}
				echo $contents;
				@fclose($file);*/
				exit(); 
	}

}