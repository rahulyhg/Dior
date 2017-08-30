<?php
class omeanalysts_data{
    //清除omeanalysts的相关表数据
    function data_clear(){
        $app_dbschema_path = APP_DIR.'/'.omeanalysts.'/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
         while( $file = readdir($dbschame_dir) ){
              $ext = substr($file, strpos($file,'.php'));
              if ($file != '..' && $file != '.' && $ext == '.php'){
                  $file = substr($file, 0, strpos($file,'.php'));
                  $table_name = 'sdb_omeanalysts_'.$file;
                  $sql = "truncate table `".$table_name."`;";
                  kernel::database()->exec($sql);
                  }
              }
        $sql = "truncate table `sdb_eccommon_analysis_logs`";
        kernel::database()->exec($sql);
    }
}