<?php
require_once(dirname(__FILE__) . '/config/config.php');
require_once(LIB_DIR . 'queueManager.php');


$manager = new queueManager();
$manager->exec();