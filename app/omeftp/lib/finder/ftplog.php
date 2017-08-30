<?php
class omeftp_finder_ftplog{
	var $column_edit = "操作";
    var $column_edit_width = "100";

    function column_edit($row) {

		return '<a href="index.php?app=omeftp&ctl=admin_ftplog&act=retry&p[0]=' . $row['ftp_log_id']. '">重试</a>';
    }
}