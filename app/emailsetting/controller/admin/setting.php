<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class emailsetting_ctl_admin_setting extends desktop_controller{
    public function index(){
        $this->pagedata['options'] = $this->getOptions();
        $this->pagedata['messengername'] = "messenger";
        $this->page('admin/config.html');
    }

    public function getOptions(){
        return array(
            'sendway'=>array('label'=>app::get('desktop')->_('发送方式'),'type'=>'radio','options'=>array('smtp'=>app::get('desktop')->_("使用外部SMTP发送")),'value'=>app::get('desktop')->getConf('email.config.sendway')?app::get('desktop')->getConf('email.config.sendway'):"smtp"),
            'usermail'=>array('label'=>app::get('desktop')->_('发信人邮箱'),'type'=>'input','value'=>app::get('desktop')->getConf('email.config.sendway')?app::get('desktop')->getConf('email.config.usermail'):'yourname@domain.com'),
            'smtpserver'=>array('label'=>app::get('desktop')->_('smtp服务器地址'),'type'=>'input','value'=>app::get('desktop')->getConf('email.config.smtpserver')?app::get('desktop')->getConf('email.config.smtpserver'):'mail.domain.com'),
            'smtpport'=>array('label'=>app::get('desktop')->_('smtp服务器端口'),'type'=>'input','value'=>app::get('desktop')->getConf('email.config.smtpport')?app::get('desktop')->getConf('email.config.smtpport'):'25'),
            'smtpuname'=>array('label'=>app::get('desktop')->_('smtp用户名'),'type'=>'input','value'=>app::get('desktop')->getConf('email.config.smtpuname')?app::get('desktop')->getConf('email.config.smtpuname'):''),
            'smtppasswd'=>array('label'=>app::get('desktop')->_('smtp密码'),'type'=>'password','value'=>app::get('desktop')->getConf('email.config.smtppasswd')?app::get('desktop')->getConf('email.config.smtppasswd'):''),
            'wmsapi_acceptoremail'=>array('label'=>app::get('desktop')->_('收件人邮箱'),'type'=>'input','value'=>app::get('desktop')->getConf('email.config.wmsapi_acceptoremail')?app::get('desktop')->getConf('email.config.wmsapi_acceptoremail'):'')
        );
    }
    

    
}