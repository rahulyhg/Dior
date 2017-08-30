<?php
class ome_ctl_admin_aftersale extends desktop_controller{

    var $workground = "aftersale_center";

    function index(){
        header("Location:index.php?app=ome&ctl=admin_return");
    }
}
?>