<?php
class wms_service_data_clear{
    //清除ome的相关表数据  desktop数据也在ome中删除
    function data_clear(){
        $app_dbschema_path = APP_DIR.'/wms/dbschema';

        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));
                $table_name = 'sdb_wms_'.$file;

                if(in_array($table_name,array('sdb_wms_print_tag','sdb_wms_print_tmpl'))){
                    continue;
                }

                $sql = "truncate table `".$table_name."`;";

                kernel::database()->exec($sql);
            }
        }
    }
}