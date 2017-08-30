<?php
/**
 * 执行EC_OS指定域名环境下的初始化代码
 * 
 * @author hzjsq@msn.com
 * @version 1.0
 */
define('ROOT_DIR',realpath(dirname(__FILE__).'/../../'));
define('APP_DIR',ROOT_DIR."/app/");


if(isset($argv[1])){
    $server_name = $argv[1];
    $_SERVER['SERVER_NAME'] = $server_name;
}else{
    die('Please give me SERVER_NAME param !!!');
}

require_once(ROOT_DIR . "/config/config.php");



require_once(APP_DIR . '/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}
require_once(APP_DIR . "/base/defined.php");
$GLOBALS['shell'] = new base_shell_loader;