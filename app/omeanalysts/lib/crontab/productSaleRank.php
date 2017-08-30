<?php
	require_once(dirname(__FILE__) .'/config.php');
	cachemgr::init(false);
    echo "productSale begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_productSaleRank')->statistics();
    echo "productSale end(".date('Y-m-d H:i:s',time()).")...\n";
