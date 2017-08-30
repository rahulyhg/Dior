<?php
	require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "rmatype begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_rmatype')->statistics();
    echo "rmatype end(".date('Y-m-d H:i:s',time()).")...\n";
