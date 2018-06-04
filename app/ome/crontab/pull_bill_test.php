<?php
    // 自动获取支付宝、微信对账单
    $root_dir = realpath(dirname(__FILE__) . '/../../../');
    require_once($root_dir . "/config/config.php");
    define('APP_DIR',ROOT_DIR . "/app/");
    require_once(APP_DIR . '/base/kernel.php');
    if(!kernel::register_autoload()){
        require(APP_DIR . '/base/autoload.php');
    }
    require_once(APP_DIR . '/base/defined.php');
    cachemgr::init(false);
    echo "begin <br/>";
    // 注意 支付宝和微信的账单日期格式有差别 aliPay：2017-05-11，WeChat：20170511
    $type = 'aliPay'; // aliPay：支付宝, WeChat：微信
    $bill_date = '2017-05-15';
    kernel::single('ome_auto_pullBillFile')->pull_bill_handle($bill_date,$type);
    echo "<br/> end";