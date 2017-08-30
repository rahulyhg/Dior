<?php
$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {
    die('No Params');
}

set_time_limit(0);

//require_once(dirname(__FILE__) . '/../../lib/init.php');
require_once(dirname(__FILE__) . '/../../../../script/lib/init.php');

cachemgr::init(false);

kernel::single('wms_crontab_script_consign')->exec_batch();
