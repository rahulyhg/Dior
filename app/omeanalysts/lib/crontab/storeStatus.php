<?php
$root_dir = realpath(dirname(__FILE__).'/../../../../');
require_once($root_dir."/script/crontab/runtime.php");

// 库存状况综合分析
if ( kernel::single('ome_func')->isRunTime('04:00',$msg) ){
    echo "storeStatus begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_storeStatus')->statistics();
    echo "storeStatus end(".date('Y-m-d H:i:s',time()).")...\n";
    exit;
}