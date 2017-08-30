<?php
class omeftp_type_txt{

	public function __construct(){
		
	}
	
   /**
	*@向目标文件中写信息
	*
	*@params array $params 文件信息
	*/
	public function toWrite($params,&$msg){
		$res = $this->open($params['file'],$params['method'],$msg);

		
		if($res){
			if(flock($res,LOCK_EX)){
				if(!fwrite($res,$this->characet($params['data']))) { 
					$msg = "数据无法写入";
					return false;
				} 
			}else{
				$msg="无法锁定文件！";
				return false;
			}
			flock($res,LOCK_UN);
			fclose($res);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	*@打开目标文件
	*
	*@params array $file 目标文件 @method 打开方式
	*/
	public function open($file,$method='a',&$msg='error is null'){
		//打开文件         
		if(!$fp_res=fopen($file,$method)) {  
			
			$msg="文件无法打开";
			return false;  
		}  
		return $fp_res;	
	}
	
	public function toRead($params,&$msg){
		$str = '';
		if(file_exists($params['file'])){ 
			$str=file_get_contents($params['file']); 
		}else{ 
			$msg = "没有这个文件"; 
		}
		return $str;
	}
	/**
	*@获取文件名称
	*
	*@params array $fileName_str 文件全路径
	*/
	public function getFileName($fileName_str){
		return basename($fileName_str);
	}

	public function delete_file($fileName_str){
		if(!@unlink($fileName_str)){
			return false;
		}
		return true;
	}

	public function characet($data){
		if( !empty($data) ){   
			$filetype = mb_detect_encoding($data , array('utf-8','gb2312','gbk','latin1','big5')) ;  
		//	error_log(var_export($filetype,true),3,'f:/cc.txt');
			if( $filetype != 'utf-8'){  
				$data = mb_convert_encoding($data ,'utf-8' , $filetype);  
			} 
		}  
		return $data;   
	}


}