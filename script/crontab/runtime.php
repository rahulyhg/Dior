<?php
$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/config/config.php");

define('APP_DIR',ROOT_DIR."/app");

if (PHP_OS == 'WINNT')
    define('PHP_EXEC',dirname(ini_get('extension_dir')).'/php');
else
    define('PHP_EXEC',PHP_BINDIR.'/php');

require_once(APP_DIR.'/base/kernel.php');
@require_once(APP_DIR.'/base/defined.php');

if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}
cachemgr::init(false);

// 脚本方式下获取WEB地址
base_kvstore::instance('setting/ome')->fetch('sh_base_url', $sh_base_url);
if ( $sh_base_url ){
    define('BASE_URL', $sh_base_url);
}