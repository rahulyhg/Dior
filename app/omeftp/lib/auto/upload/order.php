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
            $params['local'] = $row['file_local_route'];
            $params['resume'] = 0;
            $orderStr = file_get_contents($params['local']);
            $strCount = substr_count($orderStr,'HEADER');
            if($strCount==1){
                $ftp_flag = $this->ftp_operate->push($params,$msg);
                if($ftp_flag){
                    $this->operate_log->update_log(array('status'=>'succ','lastmodify'=>time(),'memo'=>'上传成功！'),$row['ftp_log_id'],'ftp');
                }else{
                    //发送报警邮件
                    $file_name = $params['remote'];
                    $file_route = $params['local'];

                    $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
                    $subject = '【Dior-PROD】ByPass SO文件'.$file_name.'上传失败';//【ADP-PROD】ByPass订单#10008688发送失败
                    $bodys = "<font face='微软雅黑' size=2>Hi All, <br/>SO文件全路径：<br>$file_route<br/><br>错误信息是：<br>$msg<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                    kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
                }
            }else{
                 $this->operate_log->update_log(array('status'=>'fail','lastmodify'=>time(),'memo'=>'文件内容异常'),$row['ftp_log_id'],'ftp');

                //发送报警邮件
                $file_name = $params['remote'];
                $file_route = $params['local'];

                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
                $subject = '【Dior-PROD】ByPass SO文件'.$file_name.'上传失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys = "<font face='微软雅黑' size=2>Hi All, <br/>SO文件全路径：<br>$file_route<br/><br>错误信息是：<br>文件内容异常<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }
    }
	
	

}