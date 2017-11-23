<?php
class giftcard_ctl_admin_cards extends desktop_controller{

		var $name = "礼品卡卡劵";
	
		public function index(){
   
			$op_id = kernel::single('desktop_user')->get_id();
			$this->title = '礼品卡卡劵';
         
			$params = array(
                'title'=>$this->title,
               // 'actions' => $this->action,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>false,
                'use_view_tab'=>false,
				'use_buildin_filter'=>true,
            );
            $this->finder('giftcard_mdl_cards',$params);
        }

}

