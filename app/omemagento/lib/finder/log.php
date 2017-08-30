<?php
class omemagento_finder_log{

    var $column_edit = "操作";
    var $column_edit_width = "100";

    function column_edit($row) {
      return '<a href="index.php?app=omemagento&ctl=admin_requestlog&act=retry&p[0]=' . $row['log_id']. '">重试</a>';
    }
}