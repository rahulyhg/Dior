<?php
class omeftp_ftp_operate implements omeftp_interface_ftp{


	public function __construct(){
		$obj_name = 'omeftp_sftp';
        $this->ftp_obj = kernel::single($obj_name);
		$this->params = app::get('omeftp')->getConf('ftp_service_setting');
		$this->preprod_params = array();
    } 

	/**
     * 将本地文件上传到存储服务器
     *
     * @params array $params 参数 array('local'=>'本地文件路径','remote'=>'远程文件路径')
     * @params string $msg 
     * @return bool
     */
     public function push($params, &$msg){
		 
		 if($this->ftp_obj->instance($this->params,$msg)){
			 if($this->ftp_obj->push($params,$msg)){

				 if(!$this->push_md5($params, $msg)){
					return false;
				 }

				 //$this->push_prepro($params,$msg);
				return true;
			 }else{
				return false;
			 }
		 }else{
			 return false;
		 }

	 }

	  public function push_prepro($params, &$msg){
		 $this->preprod_params = array(
				'host'=>'10.0.101.201',
				'port'=>'22040',
				'name'=>'LVMH_AX',
				'pass'=>'f82cc31ba61d',
			 );//获取文件暂时写死为正式的地址
		 $this->md5_push_prepro($params,$msg);
		 if($this->ftp_obj->instance($this->preprod_params,$msg)){
			 $params['remote'] = 'PROD/'.$params['remote'];
			 if($this->ftp_obj->push($params,$msg)){
				return true;
			 }else{
				return false;
			 }
		 }else{
			 return false;
		 }
	 }

	  public function md5_push_prepro($params, &$msg){
		 $this->preprod_params = array(
				'host'=>'10.0.101.201',
				'port'=>'22040',
				'name'=>'LVMH_AX',
				'pass'=>'f82cc31ba61d',
			 );//获取文件暂时写死为正式的地址
		 
		 if(strpos($params['local'],'.csv')){
			$md5_file_local = ROOT_DIR.'/ftp/Testing/in/'.basename($params['local'],'.csv').'.bal';
		 }else{
			$md5_file_local = ROOT_DIR.'/ftp/Testing/in/'.basename($params['local'],'.dat').'.bal';
		 }
		 $params['local'] = $md5_file_local;
		 $params['remote'] = basename($params['local']);
		 if($this->ftp_obj->instance($this->preprod_params,$msg)){
			 $params['remote'] = 'PROD/'.$params['remote'];
			 if($this->ftp_obj->push($params,$msg)){
				return true;
			 }else{
				return false;
			 }
		 }else{
			 return false;
		 }
	 }


	 public function push_md5($params,&$msg){
		$md5file = md5_file($params['local']);
		if(strpos($params['local'],'.csv')){
			$md5_file_local = ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()).'/'.basename($params['local'],'.csv').'.bal';
		}else{
			$md5_file_local = ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()).'/'.basename($params['local'],'.dat').'.bal';
		}
	
		if(!file_exists($md5_file_local)){
			file_put_contents($md5_file_local,$md5file);
		}
		$params['local'] = $md5_file_local;
		$params['remote'] = basename($params['local']);

		if($this->ftp_obj->instance($this->params,$msg)){
			 if($this->ftp_obj->push($params,$msg)){
				return true;
			 }else{
				return false;
			 }
		 }else{
			 return false;
		 }
	 }

    public function get_file_list( $dir){
		
		 if($this->ftp_obj->instance($this->params,$msg)){
			 if($list=$this->ftp_obj->get_list($dir)){
				return $list;
			 }else{
				return false;
			 }
		 }else{
			 return false;
		 }
	}
	

    /**
     * 将存储服务器中的文件下载到本地
     *
     * @params array $params 参数 array('local'=>'本地文件路径','remote'=>'远程文件路径','resume'=>'文件指针位置')
     * @params string $msg 
     * @return bool 
     */
    public function pull( $params, &$msg){
		
		if($this->ftp_obj->instance($this->params,$msg)){
			 if($this->ftp_obj->pull($params,$msg)){
				return true;
			 }else{
				return false;
			 }
		 }else{
			 return false;
		 }
	}
	
	/**
      * 获取传入文件在存储服务器中的大小
      *
      * @params string $filename 文件名称(无路径)
      * @return ini    文件存在则返回文件大小，文件不存在则返回 -1 或者 false
      */
    public function size($filename){
	}

    /**
     * 根据传入文件名称参数删除存储服务器中的文件
     *
      * @params string $filename 文件名称(无路径)
      * @return bool
     */
    public function delete_ftp($filename){
		
		if($this->ftp_obj->instance($this->params,$msg)){
			 if($this->ftp_obj->delete($filename)){
				return true;
			 }else{
				return false;
			 }
		 }else{
			 return false;
		 }
	}

	/**
     * 根据传入文件名称参数删除本地文件
     *
      * @params string $filename 文件名称(无路径)
      * @return bool
     */
    public function delete_loc($filename){
	}

}