<?php
class taoguaninventory_service_data_clear{
	//清除taoguaninventory的相关表数据 
	function data_clear(){
		$app = 'taoguaninventory';
        $app_dbschema_path = APP_DIR.'/'.$app.'/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
         while( $file = readdir($dbschame_dir) ){
              $ext = substr($file, strpos($file,'.php'));
              if ($file != '..' && $file != '.' && $ext == '.php'){
                  $file = substr($file, 0, strpos($file,'.php'));
                  $table_name = 'sdb_'.$app.'_'.$file;
                  if($table_name){
                      if($table_name!='sdb_taoguaninventory_encoded_state'){//过滤盘点编码表
                          $sql = "truncate table `".$table_name."`;";
        //                  echo $sql."<br />";
                          kernel::database()->exec($sql);     
                      }
                  }               }
         }
	}
}