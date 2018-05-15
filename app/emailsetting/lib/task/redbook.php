<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class emailsetting_task_redbook{
    
    public $sign_name = '【REDBOOK】';
    

    public function sendOrderError($order_bn,$msg){
        $send_bn = 'redbook_order_error';

        $objSendList = app::get('emailsetting')->model('sendlist');
        
        $sendInfo = $objSendList->getList('*',array('send_bn'=>$send_bn));
        
        $senders = $sendInfo[0]['senders'];
        $subject = $this->sign_name.$sendInfo[0]['send_name'];

        $body = "<font face='微软雅黑' size=2>Hi All, <br/><br/>订单：$order_bn 无法进入系统<br/><br/>原因：$msg ,请查看原因<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>Redbook OMS 开发团队<br/>".date("Y-m-d")."</font>";

        kernel::single('emailsetting_send')->send($senders,$subject,$body);

        
    }
}