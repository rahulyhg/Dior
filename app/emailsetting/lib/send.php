<?php
class emailsetting_send{
    

     public $smtpSetting;
     public function __construct(&$app){
        $aTmp['usermail'] = app::get('desktop')->getConf('email.config.usermail');
        $aTmp['smtpport'] = app::get('desktop')->getConf('email.config.smtpport');
        $aTmp['smtpserver'] = app::get('desktop')->getConf('email.config.smtpserver');
        $aTmp['smtpuname'] = app::get('desktop')->getConf('email.config.smtpuname');
        $aTmp['smtppasswd'] = app::get('desktop')->getConf('email.config.smtppasswd');
        $this->smtpSetting = $aTmp;
     }
    
    public function send($senders,$subject,$body,$files = array()){
        if(!is_array($senders)){
            $senders = explode(';',$senders);
        }
       // echo "<pre>";print_r($senders);exit;
        $return = kernel::single('emailsetting_email_sendemail')->sendEmail($this->smtpSetting,$senders,$subject,$body,$files);
        return $return;
    }

}