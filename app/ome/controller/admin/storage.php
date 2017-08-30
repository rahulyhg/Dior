<?php
class ome_ctl_admin_storage extends desktop_controller{

    var $workground = "storage_center";

    function index(){
        header("Location:index.php?app=ome&ctl=admin_stock");
    }
}
?>