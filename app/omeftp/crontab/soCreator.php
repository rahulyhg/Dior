<?php
/**
 * Created by PhpStorm.
 * User: Jinxing.zhou
 * Date: 2018/10/23
 * Time: 14:28
 */

$root_dir = realpath(dirname(__FILE__).'/../../../');//echo $root_dir;exit();
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}
require_once(APP_DIR.'/base/defined.php');
cachemgr::init(false);
echo "begin <br/>";
//发货SO文件
kernel::single('omeftp_service_delivery')->cron_Delivery(false,'wxpayjsapi'); //非积分订单微信支付
kernel::single('omeftp_service_delivery')->cron_Delivery(false,'alipay');//非积分订单支付宝支付
kernel::single('omeftp_service_delivery')->cron_Delivery(false,'cod');//非积分订单货到付款
kernel::single('omeftp_service_delivery')->cron_Delivery(true);//积分订单
//退货SO文件
kernel::single('omeftp_service_reship')->cron_Reship('wxpayjsapi'); //非积分订单微信支付
kernel::single('omeftp_service_reship')->cron_Reship('alipay'); //非积分订单微信支付
kernel::single('omeftp_service_reship')->cron_Reship('cod'); //非积分订单微信支付
//拒收SO文件
kernel::single('omeftp_service_back')->cron_back('wxpayjsapi'); //非积分订单微信支付
kernel::single('omeftp_service_back')->cron_back('alipay'); //非积分订单微信支付
kernel::single('omeftp_service_back')->cron_back('cod'); //非积分订单微信支付
echo "<br/> end";