<?php
/**
 *
 * 版权所有 (C) 2003-2009 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址:http://www.shopex.cn/
 * -----------------------------------------------------------------
 * 您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *

 */

class magentoapi_ctl_admin_order extends desktop_controller
{

    var $name = "接口异常订单";
    var $workground = "order_center";
	
      public function index(){
   
        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '接口异常订单';
         
         $params = array(
                'title'=>$this->title,
               // 'actions' => $this->action,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>false,
                'use_view_tab'=>false,
            );
            $this->finder('magentoapi_mdl_errororders',$params);
        }

}

