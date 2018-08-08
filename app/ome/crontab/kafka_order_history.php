<?php
    // kafka订单历史状态生成文件处理
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
    $startTime = '1533198110';  // 开始时间
    $endTime   = '1533199110';  // 结束时间
    kernel::single('ome_kafka_kafkaQueueHandle')->order_history_xls($startTime, $endTime);
    echo "<br/> end";