<?php
class omeanalysts_ctl_ome_catSaleStatis extends desktop_controller{
        
    var $name = "商品类目销售对比统计";

    public function index(){
        if(empty($_POST)){
            $_POST['time_from'] = date("Y-m-1");
            $_POST['time_to'] = date("Y-m-d",time()-24*60*60);
        }
        //商品类目销售对比统计crontab的手动调用
        //kernel::single('omeanalysts_crontab_script_catSaleStatis')->statistics();
        kernel::single('omeanalysts_ome_catSaleStatis')->set_params($_POST)->display();
    }

}