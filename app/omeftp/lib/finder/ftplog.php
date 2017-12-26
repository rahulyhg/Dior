<?php
class omeftp_finder_ftplog{
	var $column_edit = "操作";
    var $column_edit_width = "100";

    function column_edit($row) {
		
		if($row['io_type']=='in'){
			return '<a target="_blank" href="index.php?app=omeftp&ctl=admin_ftplog&act=downFile&p[0]=' . $row['ftp_log_id']. '">下载</a>';
		}else{
			return '<a href="index.php?app=omeftp&ctl=admin_ftplog&act=retry&p[0]=' . $row['ftp_log_id']. '">重试</a>';
		}
    }
}