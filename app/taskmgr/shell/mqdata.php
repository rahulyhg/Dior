<?php

require_once('shell.php');

$mq = new taskmgr_connecter_rabbitmq();

$mq->load('autochk', $GLOBALS['__RABBITMQ_CONFIG']);

for($i=0;$i<10;$i++){

    $data['logi_no'] = 'dly'. sprintf('%05d', $i);
    $data['url'] = 'http://www.baidu.com/?rand=' . rand(1,10000);
    $msg = json_encode($data);
    $mq->publish($msg,'erp.task.autochk.*');
}
