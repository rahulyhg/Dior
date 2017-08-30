<?php
class ome_ctl_admin_process extends desktop_controller{

    var $workground = "delivery_center";

    function index(){
        header("Location:index.php?app=ome&ctl=admin_receipts_print");
    }
}
?>