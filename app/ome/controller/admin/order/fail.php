<?php
class ome_ctl_admin_order_fail extends desktop_controller{
    var $name = "订单中心";
    var $workground = "order_center";
    var $order_type = 'all';

    function _views(){
        $mdl_order = $this->app->model('order_fail');
        $base_filter = array('is_fail'=>'true', 'archive'=>'1', 'edit_status'=>'true', 'status'=>'active');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>app::get('base')->_('活动'),'filter'=>array('process_status|noequal' => 'cancel'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('取消'),'filter'=>array('process_status' => 'cancel'),'optional'=>false),
        );

        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }

    public function index(){
        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '失败订单';

        $base_filter = array('is_fail'=>'true','status'=>'active');
        if(app::get('replacesku')->is_installed()){
            $base_filter['auto_status']=0;
        }
        $is_export = kernel::single('desktop_user')->has_permission('order_export');#增加失败订单导出权限
        $params = array(
            'title'=>$this->title,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_export'=>$is_export,
            'use_view_tab'=>true,
            'finder_aliasname' => 'order_fail'.$op_id,
            'finder_cols'=>'order_bn,shop_id,shop_type,total_amount,is_cod,pay_status,createtime',
            'base_filter' => $base_filter
        );
        $this->finder('ome_mdl_order_fail',$params);
    }

    public function dosave(){
        $url = 'index.php?app=ome&ctl=admin_order_fail&act=index';
        $pbn = $_POST['pbn'];
        $oldPbn = $_POST['oldPbn'];        
        $order_id = $_POST['order_id'];

        //danny_freeze_stock_log
        define('FRST_TRIGGER_OBJECT_TYPE','订单：失败订单恢复');
        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order_fail：dosave');
        //修正订单项
        if(kernel::single("ome_order_fail")->modifyOrderItems($order_id,$oldPbn,$pbn)){
            $this->splash('success',$url,'订单处理成功');
        }else{
            $this->splash('error',$url,'存在异常商品，订单修正失败！');
        }
    }

    /**
     * 失败订单批量修复货号
     */
    public function batchsave(){
        $url = 'index.php?app=ome&ctl=admin_order_fail&act=index';
        $pbn = $_POST['pbn'];
        $oldPbn = $_POST['oldPbn'];

        $itemObj = &app::get('ome')->model('order_items');
        $queueObj = &app::get('base')->model('queue');

        $orderData = $itemObj->getFailOrderByBn($oldPbn);

        $count = 0;
        $limit = 10;
        $page = 0;
        $orderSdfs = array();

        if(!$oldPbn){
            $this->splash('error',$url,'存在原始货号为空的情况不允许批量修改！');
        }else{
            foreach($oldPbn as $bn){
                if(!$bn || $bn==''){
                    $this->splash('error',$url,'存在原始货号为空的情况不允许批量修改！');
                    break;
                }
            }
        }

        /**
         * 处理拥有相同旧货号明细大于1，且新货号明细货号又不同的情况。
         * 只能单个处理
         */
        $arr = array_combine($pbn, $oldPbn);
        # 过滤键为空 
        unset($arr['']);
        $arr_count_values = array_count_values($arr);
        foreach ($arr_count_values as $key => $count) {
            if ($key && $count > 1) {
                $this->splash('error',$url,'相同旧货号对应不同新货号，不允许批量修改!'); break;
            }
        }

        //重置订单号，并得到队列的sdf数据
        foreach($orderData as $order){
            $orderID[] = $order['order_id'];

            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $orderSdfs[$page]['orderId'][] = $order['order_id'];
            $orderSdfs[$page]['oldPbn'] = $oldPbn;
            $orderSdfs[$page]['pbn'] = $pbn;
        }
        $opinfo = kernel::single('ome_func')->getDesktopUser();

        //插入队列
        if($orderID && $orderSdfs){
            foreach($orderSdfs as $v){
                $queueData = array(
                    'queue_title'=>'异常订单批量修正',
                    'start_time'=>time(),
                    'params'=>array(
                        'sdfdata'=>$v,
                        'opinfo'=>$opinfo,
                        'app' => 'ome',
                        'ctl' => 'admin_order'
                    ),
                    'worker'=>'ome_order_fail.batchModifyOrder',
                );

                $queueObj->save($queueData);
            }
            $queueObj->flush();
            $this->splash('success',$url,'批量修改请求已提交');
        }else{
            $this->splash('error',$url,'数据错误，订单修正失败！');
        }
    }
}