<?php
class ome_ctl_admin_payment_cfg extends desktop_controller{
    var $name = "支付方式";
    var $workground = "setting_tools";
    function index(){
        $this->finder('ome_mdl_payment_cfg',array(
            'title'=>'支付方式管理',
            'actions'=>array(
                            array('label'=>'同步支付方式','href'=>'index.php?app=ome&ctl=admin_payment_cfg&act=getPayment&finder_id='.$_GET['finder_id']),
                        ),
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>false,
            'use_buildin_filter'=>true,
         ));
    }

    public function getPayment(){
        $this->begin('index.php?app=ome&ctl=admin_payment_cfg');
        $shopObj = &app::get('ome')->model('shop');
        $shopList = $shopObj->getList('shop_id');
        foreach($shopList as $shop){
            kernel::single("ome_payment_func")->sync_payments($shop['shop_id']);
        }
        $this->end(true, app::get('base')->_('发送成功'));
    }
}

?>
