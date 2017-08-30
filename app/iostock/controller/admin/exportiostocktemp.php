<?php
class iostock_ctl_admin_exportiostocktemp extends desktop_controller{

     function index(){
        $this->pagedata['iotype'] = array('采购入库'     => 'index.php?app=iostock&ctl=admin_purchase&act=exportTemplate',
                                          '采购退货'     => 'index.php?app=iostock&ctl=admin_purchaseReturns&act=exportTemplate',
                                          '换货入库'     => 'index.php?app=iostock&ctl=admin_changeorderreturns&act=exportTemplate',
                                          '退货入库'     => 'index.php?app=iostock&ctl=admin_orderreturns&act=exportTemplate',
                                          '商品调拨'     => 'index.php?app=iostock&ctl=admin_transfer&act=exportTemplate',
                                          '商品残损'     => 'index.php?app=iostock&ctl=admin_damaged&act=exportTemplate',
                                          '盘点差异过账' => 'index.php?app=iostock&ctl=admin_inventory&act=exportTemplate'
                                        );
        echo $this->page('admin/temp/download.html');
    }
}