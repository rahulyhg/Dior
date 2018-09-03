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
    ##################
    ## 建议将时间维度切成3次调用,如2016-03~2018-09
    ## 1.$startTime = '2016-03' $endTime = '2017-02'
    ## 2.$startTime = '2017-03' $endTime = '2018-02'
    ## 3.$startTime = '2018-03' $endTime = '2018-08'
    ## 这样将总体数据分3次处理（3个线程同时跑），防止内存溢出、apache连接超时
    ##################
    $startTime = '2018-03';  // 开始时间
    $endTime   = '2018-09';  // 结束时间
    kernel::single('ome_kafka_kafkaQueueHandle')->getExcel($startTime, $endTime);
    echo "<br/> end";