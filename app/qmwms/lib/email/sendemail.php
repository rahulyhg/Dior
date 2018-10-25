<?php
class qmwms_email_sendemail{

    /**
     * 每天三次定时发送失败订单报警邮件
     */
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
        //奇门接口日志表（只查单据取消、发货确认、退货确认三个接口）
        $sql1 = "select original_bn,res_msg,response from sdb_qmwms_qmrequest_log where status = 'failure' and task_name in('order.cancel','deliveryorder.confirm','returnorder.confirm') and last_modified >= {$modify} ";
        $requst_log = kernel::database()->select($sql1);

        //取队列表：1.发货确认、退货确认订单
        $wms_log1 = app::get('qmwms')->model('queue')->getList('id,original_bn,msg',array('status'=>'2','queue_type'=>array('do_delivery','do_return'),'last_modified|bthan'=>$modify));//发货确认、退货确认订单
        //取队列表：2.发货创建、退货创建失败5次的订单
        $wms_log2 = app::get('qmwms')->model('queue')->getList('id,original_bn,msg',array('status'=>'2','repeat_num'=>'5','queue_type'=>array('delivery','return','last_modified|bthan'=>$modify)));//发货创建、退货创建失败5次的订单
        $wms_log = array_merge($wms_log1,$wms_log2);
        //echo "<pre>";print_r($wms_log);exit;
        if(empty($requst_log)&&empty($wms_log)) return;

        //拼接报错信息
        $erroString = '';
        foreach($requst_log as $value){
            $failure_msg = !empty($value['res_msg'])?$value['res_msg']:$value['response'];
            $erroString .= "单据".$value['original_bn']." ".$failure_msg."<br>";
        }
        foreach($wms_log as $w_value){
            //处理发货确认、退货确认订单单据无original_bn的情况
            if(!empty($w_value['original_bn'])){
                $erroString .= "单据".$w_value['original_bn']." ".$w_value['msg']."<br>";
            }else{
                $erroString .= "日志ID为".$w_value['id']."的单据"." ".$w_value['msg']."<br>";
            }
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