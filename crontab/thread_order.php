<?php
require_once(dirname(__FILE__) . '/config/config.php');
require_once(LIB_DIR . 'orderManager.php');


$manager = new orderManager();
$manager->exec();