<?php
class ome_ctl_admin_invoice extends desktop_controller{

    var $workground = "invoice_center";

    function index(){
        header("Location:index.php?app=ome&ctl=admin_payment");
    }
}
?>