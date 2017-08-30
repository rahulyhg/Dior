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

class replacesku_ctl_admin_order extends desktop_controller
{

    var $name = "订单中心";
    var $workground = "order_center";
	   var $order_type = 'all';

      public function index(){
   
        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '待转换订单';
        $this->action = array(
                        array('label' =>'转换订单', 'href' => 'index.php?app=replacesku&ctl=admin_order&act=transform_sku', 'target' => 'dialog::{width:1000,height:400,title:\'转换订单\'}'),

                    );

         $base_filter = array(
           'is_fail'=>'true',
           'auto_status'=>1
         );
         $params = array(
                'title'=>$this->title,
                'actions' => $this->action,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_view_tab'=>true,
                'finder_cols'=>'column_confirm,order_bn,shop_id,shop_type,total_amount,is_cod,custom_mark,createtime,pause',
                'base_filter' => $base_filter
            );
            $this->finder('replacesku_mdl_order',$params);
        }



     public function transform_sku()
     {
          $oOrders = app::get('ome')->model('orders');
          $order_list = $oOrders->getlist('order_id',array('is_fail'=>'true','auto_status'=>1));
          $sku_tran = new replacesku_order;
          $mess = $sku_tran->transform_sku($order_list);
          echo '共有符合条件的待转换订单数:'.$mess['total'].'条记录<br>';
			       echo '失败订单:'.$mess['fail'].' 成功:'.$mess['succ'].' 其它:'.$mess['other'];

     }


     public function pause_order()
     {
          $this->begin('index.php?app=replacesku&ctl=admin_order&act=index&auto_status=1');
          $oOrder = &app::get('ome')->model('orders');
          $order_id = $_GET['order_id'];
          if (empty($order_id)) {
            $this->end(false, app::get('ome')->_('请选择要操作的数据项。'));
            return;
           }else{
               $action = $_GET['action'];
               if($action=='renew'){
                    $oOrder->renewOrder($order_id);
               }else{
                    $oOrder->pauseOrder($order_id);
               }
               $this->end(true,'操作成功');
               return;
          }

     }


}

