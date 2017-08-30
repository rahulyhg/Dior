<?php
class omeanalysts_ctl_ome_zeroSale extends desktop_controller{
		
	var $name = "零销售产品分析";

    public function index(){
		if(empty($_POST)){
			$_POST['time_from'] = date("Y-m-1");
			$_POST['time_to'] = date("Y-m-d",time()-24*60*60);
		}
		kernel::single('omeanalysts_crontab_script_zeroSale')->statistics();
        kernel::single('omeanalysts_ome_zeroSale')->set_params($_POST)->display();
    }

}