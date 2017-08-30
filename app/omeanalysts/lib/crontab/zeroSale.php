<?php
$root_dir = realpath(dirname(__FILE__).'/../../../../');
require_once($root_dir."/script/crontab/runtime.php");

// 零销售产品分析
if ( omequeue_queue::is_allow_exec('04:02') ){
    echo "zeroSale begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_zeroSale')->statistics();
    echo "zeroSale end(".date('Y-m-d H:i:s',time()).")...\n";
    exit;
}