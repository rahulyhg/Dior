<?php
class omeftp_auto_upload_order{
	

	public function __construct(){
		$this->app = $app;
        $this->file_obj = kernel::single('omeftp_type_txt');
		$this->ftp_operate = kernel::single('omeftp_ftp_operate');
		$this->operate_log = kernel::single('omeftp_log');
	}



    public function pushOrder(){
       $list = app::get('omeftp')->model('ftplog')->getList('*',array('status'=>'prepare'),0,100);

        foreach($list as $row){
            $params = array();

            $params['remote'] = $this->file_obj->getFileName($row['file_ftp_route']);
            $params['local'] = $file_params['file_local_route'];
            $params['resume'] = 0;

            $ftp_flag = $this->ftp_operate->push($params,$msg);
            if($ftp_flag){
                $this->operate_log->update_log(array('status'=>'succ','lastmodify'=>time(),'memo'=>'上传成功！'),$row['ftp_log_id'],'ftp');
            }
        }
    }
	
	

}