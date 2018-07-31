<?php
class qmwms_email_sendemail{

    public function sendEmail(){
        $time = time();
        $hour = date('H',$time);

        if($hour == '09'){
            $modify = $time - 15*60*60;//9点发邮件，时间从昨天18点到今天9点,共15h
        }elseif($hour == '14'){
            $modify = $time - 5*60*60;//14点发一次邮件，间隔是5小时
        }else{
            $modify = $time - 4*60*60;//18点各发一次邮件，间隔是4小时
        }

        $sql = "select original_bn,res_msg,response from sdb_qmwms_qmrequest_log where status = 'failure' and original_bn <> '' and last_modified >= {$modify} ";
        $requst_log = kernel::database()->select($sql);
        if(empty($requst_log)) return;
        $erroString = '';
        foreach($requst_log as $value){
            $failure_msg = !empty($value['res_msg'])?$value['res_msg']:$value['response'];
            $erroString .= "单据".$value['original_bn']." ".$failure_msg."<br>";
        }

        //发送报警邮件
        $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
        $subject = '【Dior-PROD】ByPass订单接口失败信息';
        $bodys = "<font face='微软雅黑' size=2>Hi All, <br/>失败订单信息如下：<br>$erroString<br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
        $return = kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
        if(!$return){
            $erroString = str_replace('<br>',"\r\n",$erroString);
            error_log(date('Y-m-d H:i:s').'邮件发送失败,邮件主要内容如下:'."\r\n".var_export($erroString,true)."\r\n", 3, ROOT_DIR.'/data/logs/sendemail'.date('Y-m-d').'.xml');
        }

    }
}

?>