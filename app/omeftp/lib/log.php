<?php
/**
 * AX日志类
 * 发货文件日志和FTP文件日志操作类
 * @author lijun
 * @package omeftp_log
 *
 */
class omeftp_log{

	public function __construct(&$app){
		 $this->app = $app;
		 $this->file_log_model = $this->app->model('filelog');
		 $this->ftp_log_model = $this->app->model('ftplog');
	}

	public function write_log($data,$type='file'){
		if($type=='file'){
			return $this->file_log_model->insert($data);
		}else{
			return $this->ftp_log_model->insert($data);
		}
	}

	public function update_log($data,$log_id,$type='file'){
		if($type=='file'){
			$this->file_log_model->update($data,array('file_log_id'=>$log_id));
		}else{
			$this->ftp_log_model->update($data,array('ftp_log_id'=>$log_id));
		}
	}

}