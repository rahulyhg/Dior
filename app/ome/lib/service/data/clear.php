<?php
class ome_service_data_clear{
    //清除ome的相关表数据  desktop数据也在ome中删除
    function data_clear(){
        $app_dbschema_path = APP_DIR.'/ome/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));
                $table_name = 'sdb_ome_'.$file;
                if(in_array($table_name,array('sdb_ome_branch','sdb_ome_print_tmpl','sdb_ome_goods_type','sdb_ome_groups','sdb_ome_shop','sdb_ome_iostock_type','sdb_ome_operations','sdb_ome_print_otmpl'))){
                    continue;
                }
                $sql = "truncate table `".$table_name."`;";
                //echo $sql."<br />";
                kernel::database()->exec($sql);
            }
        }
        //删除除admin以外的操作员
        $sql_pam = "DELETE FROM sdb_pam_account WHERE `account_id` > 1;";
        kernel::database()->exec($sql_pam);
        //删除除admin以外的用户
        $sql_desktop = "DELETE FROM sdb_desktop_users WHERE  `user_id` > 1;";
        kernel::database()->exec($sql_desktop);
        //保留我的仓库
        $sql_ome_branch = "DELETE FROM sdb_ome_branch WHERE  `branch_id` > 1;";
        kernel::database()->exec($sql_ome_branch);
        //保留预定义的打印模板
        $sql_ome_print_tmpl = "DELETE FROM sdb_ome_print_tmpl WHERE  `prt_tmpl_id` > 12;";
        kernel::database()->exec($sql_ome_print_tmpl);
        //保留预定义的商品类型
        $sql_ome_goods_type = "DELETE FROM sdb_ome_goods_type WHERE  `type_id` > 1;";
        kernel::database()->exec($sql_ome_goods_type);
        //保留预定义的订单确认小组
        $sql_ome_groups = "DELETE FROM sdb_ome_groups WHERE  `group_id` > 1;";
        kernel::database()->exec($sql_ome_groups);
        //清除base下的通知表数据
        $sql_base_rpcnotify = "truncate table `sdb_base_rpcnotify`";
        kernel::database()->exec($sql_base_rpcnotify);
        //清除回收站的数据
        $sql_desktop_recycle = "truncate table `sdb_desktop_recycle`";
        kernel::database()->exec($sql_desktop_recycle);
        //清除搜索保留的数据
        $sql_desktop_filter = "truncate table `sdb_desktop_filter`";
        kernel::database()->exec($sql_desktop_filter);

        //清除eccommon 数据
        $sql_desktop_filter = "truncate table `sdb_eccommon_analysis_logs`";
        kernel::database()->exec($sql_desktop_filter);

        $sql_desktop_filter = "truncate table `sdb_eccommon_analysis`";
        kernel::database()->exec($sql_desktop_filter);
    }
}