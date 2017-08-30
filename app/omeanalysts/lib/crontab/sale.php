<?php
	require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "saleStatis begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_sale')->statistics();
    echo "saleStatis end(".date('Y-m-d H:i:s',time()).")...\n";
