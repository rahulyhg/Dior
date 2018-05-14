<?php
class ome_warning
{
	function __construct($app){
        $this->app = $app;
    }
	
	public function getShippedFile(){
		$now=date("Ymd");
		$dir=ROOT_DIR.'/ftp/Testing/out/'.$now;
		$flag="ORDER_REG_DIOR_10";
		$arrFilesName=array();
		
		if(@is_dir($dir)){
			if ($dh = @opendir($dir)){
				while (($file = @readdir($dh)) !== false){
					if (substr("$file", 0, 1) != "."&&substr($file,-3)!="bal"&&strpos($file,$flag)!==false){
					  	$filename=substr($file,0,strpos($file,"."));
						$arrFilesName[$filename]=$filename;
					}
				}
				@closedir($dh);
			}
		}
		
		$pattern='/H\|CN\|01\|2920\|1190\|(.*)\|SO-/i';
		
		if(!empty($arrFilesName)){
			foreach($arrFilesName as $file){
				$content=NULL;
				$content=@file_get_contents($dir."/".$file.".dat");
				
				$matchData=array();

				preg_match_all($pattern,$content,$matchData);
				
				if(!$this->checkShippedOrder($matchData[1][0],end($matchData[1]))){
					kernel::single("emailsetting_send")->send("jinrong.zhang@d1m.cn;jasmine.yu@d1m.cn","Dior 有未发货订单",$dir."/".$file.".dat");
				}
			}
		}
	}
	
	public function checkShippedOrder($head_order_bn,$end_order_bn){
		if(empty($head_order_bn)||empty($end_order_bn))return true;
		
		if($head_order_bn==$end_order_bn){
			$flag=1;
		}else{
			$flag=2;
		}
		$sql="order_bn IN ('$head_order_bn','$end_order_bn')";
		
		$objOrder=kernel::single("ome_mdl_orders");
		$arrOrders=array();
		$arrOrders=$objOrder->db->select("SELECT order_bn FROM sdb_ome_orders WHERE ship_status IN ('1','3','4') AND $sql");
		
		if(count($arrOrders)!=$flag){//发邮件
			return false;
		}
		
		return true;
	}
}