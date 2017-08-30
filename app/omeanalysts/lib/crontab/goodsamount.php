<?php
	require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "goodsamount begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_goodsamount')->statistics();
    echo "goodsamount end(".date('Y-m-d H:i:s',time()).")...\n";
