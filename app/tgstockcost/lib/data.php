<?php
class tgstockcost_data{
    function data_clear(){
        //清除数据时重置成本设置
        app::get("ome")->setConf("tgstockcost.cost","");
        app::get("ome")->setConf("tgstockcost.get_value_type","");
        app::get("ome")->setConf("tgstockcost_install_time","");

        $appname = 'tgstockcost';
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