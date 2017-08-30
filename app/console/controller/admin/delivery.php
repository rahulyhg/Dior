<?php
class console_ctl_admin_delivery extends desktop_controller {

    var $name = "发货单列表";
    var $workground = "console_center";


    /**
     *
     * 发货单列表
     */
    function index(){

        $user = kernel::single('desktop_user');
        
        $actions = array();
       
       $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('ready','progress','succ'),
        );
        $base_filter = array_merge($base_filter,$_GET);
        switch ($_GET['view']) {
            case '1':
                $actions[] = 
                    array('label' => '发送至第三方',
                            'submit' => 'index.php?app=console&ctl=admin_delivery&act=batch_sync', 
                            'confirm' => '你确定要对勾选的发货单发送至第三方吗？', 
                            'target' => 'refresh');
               
                break;
        }
        if($user->has_permission('console_process_receipts_print_export')){
            $base_filter_str = http_build_query($base_filter);
            if ($_GET['view'] == '1') {
                $query_status = 'progress';
            }elseif($_GET['view'] == '2'){
                $query_status = 'succ';
            }
            $actions[] =  array(
            'label'=>'导出',
            'submit'=>'index.php?app=omedlyexport&ctl=ome_delivery&act=index&action=export&status='.$query_status,
            'target'=>'dialog::{width:600,height:300,title:\'导出\'}'
            );
       }
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'actions' => $actions,
            'title'=>'发货单',
            'base_filter' => $base_filter,
        );

        
        $this->finder('console_mdl_delivery', $params);
    }

   //未发货 已发货 全部
    function _views(){
        $oDelivery = app::get('ome')->model('delivery');
        $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
        );
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array('status' => array('ready','progress','succ')),'optional'=>false),
            1 => array('label'=>app::get('base')->_('待发货'),'filter'=>array('process'=>array('FALSE'),'status'=>array('progress','ready')),'optional'=>false),
            2 => array('label'=>app::get('base')->_('已发货'),'filter'=>array('process'=>array('TRUE'),'status'=>'succ'),'optional'=>false),
           
        );
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $oDelivery->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=console&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }

        return $sub_menu;
    }

    
    /**
     * 发送至第三方
     * @
     * @
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batch_sync()
    {
        $this->begin('');
        kernel::database()->exec('commit');
        $ids = $_POST['delivery_id'];
        $oOperation_log = &app::get('ome')->model('operation_log');
        $sendObj = app::get('console')->model('delivery_send');
        if (!empty($ids)) {
            foreach ($ids as  $deliveryid) {
                $original_data = kernel::single('ome_event_data_delivery')->generate($deliveryid);
                $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
                $sendObj->update_send_status($deliveryid,'sending');
                $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);
                $oOperation_log->write_log('delivery_modify@ome',$deliveryid,"发货单开始发送第三方结果".serialize($result),NULL,$opInfo);
                
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }
    
    
    /**
     * 撤销订单
     * @param   
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function pauseorder()
    {
        $is_super = kernel::single('desktop_user')->is_super();
        if ($is_super) {

            $this->page('admin/pauseorder.html');
        }else{
            echo '非管理员不可操作';
        }
    }

     function back(){
         $this->begin();
         $is_super = kernel::single('desktop_user')->is_super();
         if (!$is_super) {
             $this->end(false, '非超级管理员不可操作');
         }
        if (empty($_POST['select_bn']) && empty($_POST['bn_select'])){
            $this->end(false, '请输入正确的单号', '', $autohide);
        }
        $autohide = array('autohide'=>3000);
        $Objdly  = &app::get('ome')->model('delivery');
        $OiObj  = &app::get('ome')->model('delivery_items');
        $ObjdlyOrder  = &app::get('ome')->model('delivery_order');
        
        if($_POST['select_bn']=='order_bn'){
            $select_type = 'order_bn';
            $detail = $Objdly->getDeliveryByOrderBn($_POST['bn_select']);
            if (!$detail) {
                 $this->end(false, '发货单未生成 不走此流程', '', $autohide);
            }
            $detail['consignee']['name'] = $detail['ship_name'];
            $detail['consignee']['area'] = $detail['ship_area'];
            $detail['consignee']['province'] = $detail['ship_province'];
            $detail['consignee']['city'] = $detail['ship_name'];
            $detail['consignee']['district'] = $detail['ship_district'];
            $detail['consignee']['addr'] = $detail['ship_addr'];
            $detail['consignee']['zip'] = $detail['ship_zip'];
            $detail['consignee']['telephone'] = $detail['ship_telephone'];
            $detail['consignee']['mobile'] = $detail['ship_mobile'];
            $detail['consignee']['email'] = $detail['ship_email'];
            $detail['consignee']['r_time'] = $detail['ship_name'];
        }
        $items = $OiObj->getList('*',array('delivery_id'=>$detail['delivery_id']));
        if(empty($detail)){
            $this->end(false, '没有该单号的发货单', '', $autohide);
        }
        if($detail['status'] == 'back'){
            $this->end(false, '该发货单已经被打回，无法继续操作', '', $autohide);
        }
        #获取订单
        $order_bn = $ObjdlyOrder->getOrderInfo('order_bn',$detail['delivery_id']);
        if($detail['status'] == 'cancel'){
            $this->end(false, '该发货单已经被取消，无法继续操作'."<br>".'订单号:'.$order_bn[0]['order_bn'], '', $autohide);
        }
        if($detail['delivery_logi_number'] > 0){
            $this->end(false, '该发货单已部分发货，无法继续操作', '', $autohide);
        }
        if($detail['pause'] == 'true'){
            $this->end(false, '该发货单已暂停，无法继续操作', '', $autohide);
        }
        if($detail['process'] == 'true'){
            $this->end(false, '该发货单已经发货，无法继续操作', '', $autohide);
        }
        $pObj = &app::get('ome')->model('products');
        foreach($items as $k=>$value){
            $barcode = $pObj->dump(array('product_id'=>$value['product_id']),'barcode');
            $items[$k]['barcode'] = $barcode['barcode'];
        }

        if(($detail['stock_status']=='true') || ($detail['deliv_status']=='true') || ($detail['expre_status']=='true')){
            $this->pagedata['is_confirm'] = true;
        }
        if($detail['is_bind']=='true'){
              $countinfo = $Objdly->getList('count(parent_id)',array('parent_id'=>$detail['delivery_id']));
              $count = $countinfo[0]['count(parent_id)'];
              $this->pagedata['height'] = 372+26*$count;
        }
        $this->pagedata['select_type'] = $select_type;
        $this->pagedata['bn_select']   = $_POST['bn_select'];
        $this->pagedata['items']       = $items;
        $this->pagedata['detail']      = $detail;
        $this->page('admin/pauseorder.html');
    }

    /**
     * 打回操作
     *
     */
    function doReback(){
        $autohide = array('autohide'=>3000);
        $this->begin('index.php?app=ome&ctl=admin_delivery&showmemo&p[0]='.$_POST['id']);
        $is_super = kernel::single('desktop_user')->is_super();
         if (!$is_super) {
             $this->end(false, '非超级管理员不可操作');
         }
        if (empty($_POST['id']) && !empty($_POST['flag'])){
            $this->end(false, '请选择至少一张发货单', '', $autohide);
        }
        if (empty($_POST['memo'])){
            $this->end(false, '备注请不要留空', '', $autohide);
        }
        $delivery_id = $_POST['delivery_id'];
        $dlyObj = &app::get('ome')->model("delivery");
        $orderObj = &app::get('ome')->model("orders");
        $oOperation_log = &app::get('ome')->model('operation_log');
        $doObj = &app::get('ome')->model('delivery_order');
        $branch_productObj = &app::get('ome')->model('branch_product');
        $delivery_itemsObj = &app::get('ome')->model('delivery_items');
        $deliveryInfo = $dlyObj->dump($delivery_id,'*');
        $tmpdly = array(
            'delivery_id' => $deliveryInfo['delivery_id'],
            'status' => 'cancel',
            'logi_id' => '',
            'logi_name' => '',
            'logi_no' => NULL,
        );
        $dlyObj->save($tmpdly);
        $oOperation_log->write_log('delivery_modify@ome',$deliveryInfo['delivery_id'],'发货单撤销');
        //增加branch_product释放冻结库存
        $branch_id = $deliveryInfo['branch_id'];
        $product_ids = $delivery_itemsObj->getList('product_id,number',array('delivery_id'=>$delivery_id),0,-1);
        foreach($product_ids as $key=>$v){
            $branch_productObj->unfreez($branch_id,$v['product_id'],$v['number']);
        }
        $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
        //是否是合并发货单
        if($deliveryInfo['is_bind'] == 'true'){
            //取关联发货单号进行暂停
            $delivery_ids = $dlyObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
            if($delivery_ids){
                foreach ($delivery_ids as $id){
                    $tmpdly = array(
                        'delivery_id' => $id,
                        'status' => 'cancel',
                        'logi_id' => '',
                        'logi_name' => '',
                        'logi_no' => NULL,
                    );
                    $dlyObj->save($tmpdly);
                    $oOperation_log->write_log('delivery_modify@ome',$id,'发货单撤销');
                }
            }

            //取关联订单号进行还原
            
            if($order_ids){
                foreach ($order_ids as $id){
                    $order['order_id'] = $id;
                    $order['confirm'] = 'N';
                    $order['process_status'] = 'unconfirmed';
                    $orderObj->save($order);
                    $oOperation_log->write_log('order_modify@ome',$id,'发货单撤销,订单还原需重新审核,备注:'.$_POST['memo']);
                }
            }
        }else{
            //还原当前订单
            $order_id = $order_ids[0];
            $order['order_id'] = $order_id;
            $order['confirm'] = 'N';
            $order['process_status'] = 'unconfirmed';
            
            $orderObj->save($order);
            $oOperation_log->write_log('order_modify@ome',$order_id,'发货单撤销,订单还原需重新审核,备注:'.$_POST['memo']);
        }
        //冻结库存释放
        $this->end(true, '操作成功', 'index.php?app=console&ctl=admin_delivery&act=pauseorder', $autohide);
    }

    /**
     * 填写打回备注
     *
     * @param bigint $dly_id
     */
    function showmemo($dly_id){
        $deliveryObj  = &app::get('ome')->model("delivery");
        $dly          = $deliveryObj->dump($dly_id,'is_bind,delivery_bn');
        
        if ($dly['is_bind'] == 'true'){
            $ids = $deliveryObj->getItemsByParentId($dly_id, 'array');
            $returnids = implode(',', $ids);
            $idd = array();
            if ($ids){
                foreach ($ids as $v){
                    $delivery = $deliveryObj->dump($v, 'delivery_bn');
                    $order_id = $deliveryObj->getOrderBnbyDeliveryId($v);
                    $idd[$v]['delivery_bn'] = $delivery['delivery_bn'];
                    $idd[$v]['order_bn'] = $order_id['order_bn'];
                    $idd[$v]['delivery_id'] = $v;
                }
            }
            $this->pagedata['returnids'] = $returnids;
            $this->pagedata['ids'] = $ids;
            $this->pagedata['idd'] = $idd;
        }
        $this->pagedata['delivery_id'] = $dly_id;
        $this->pagedata['delivery_bn'] = $dly['delivery_bn'];
        $this->display("admin/delivery_showmemo.html");
    }
}
