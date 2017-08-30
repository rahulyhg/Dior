<?php
/**
 * 
 * 
 * @author yangminsheng@shopex.com
 * @version 1.0
 */


$domain = $argv[1];

$order_id = $argv[2];

$host_id = $argv[3];

if (empty($domain)) {

    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$data['filter']['order_status'] = 'ship';
app::get('ectools')->setConf('analysis_config',$data);
