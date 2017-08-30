<?php
$root_dir = realpath(dirname(__FILE__).'/../../../../');
require_once($root_dir."/script/crontab/runtime.php");

// 库存日报表记录
if ( omequeue_queue::is_allow_exec('03:05') ){
    echo "bpstock begin(".date('Y-m-d H:i:s',time()).")...\n";
    kernel::single('omeanalysts_crontab_script_bpStockDetail')->statistics();
    echo "bpstock end(".date('Y-m-d H:i:s',time()).")...\n";
    exit;
}