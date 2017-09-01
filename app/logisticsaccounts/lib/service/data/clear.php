<?php
class logisticsaccounts_service_data_clear{
    //清除logisticsaccounts的相关表数据
    function data_clear(){
        $app_dbschema_path = APP_DIR.'/logisticsaccounts/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));
                $table_name = 'sdb_logisticsaccounts_'.$file;
                $sql = "truncate table `".$table_name."`;";
                kernel::database()->exec($sql);
            }
        }
       
    }
}