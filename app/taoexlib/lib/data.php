<?php
class taoexlib_data{
    function data_clear(){
        $appname = 'taoexlib';
        $app_dbschema_path = APP_DIR.'/'.$appname.'/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
         while( $file = readdir($dbschame_dir) ){
              $ext = substr($file, strpos($file,'.php'));
              if ($file != '..' && $file != '.' && $ext == '.php'){
                  $file = substr($file, 0, strpos($file,'.php'));
                  $table_name = 'sdb_'.$appname.'_'.$file;
                  $sql = "truncate table `".$table_name."`;";
                  kernel::database()->exec($sql);                 
                  }
              }
    }
}
?>