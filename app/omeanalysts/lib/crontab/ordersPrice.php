<?php
	require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "ordersPrice begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_ordersPrice')->orderPrice();
    echo "ordersPrice end(".date('Y-m-d H:i:s',time()).")...\n";
