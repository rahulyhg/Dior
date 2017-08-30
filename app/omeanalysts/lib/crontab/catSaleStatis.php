<?php
	require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "catSaleStatis begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_catSaleStatis')->statistics();
    echo "catSaleStatis end(".date('Y-m-d H:i:s',time()).")...\n";
