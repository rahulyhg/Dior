<?php
	require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "goodsrma begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_goodsrma')->statistics();
    echo "goodsrma end(".date('Y-m-d H:i:s',time()).")...\n";
