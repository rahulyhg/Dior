<?php
class ome_ctl_admin_delivery extends desktop_controller{
    var $name = "发货单";
    var $workground = "console_center";

    function index(){
        $filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('ready','progress','succ')
        );
        
        if(isset($_POST['status']) && ($_POST['status']!='')){
            $filter['status'] = $_POST['status'];
        }
        $actions = array();
        $user = kernel::single('desktop_user');
        if($user->has_permission('console_process_receipts_print_export')){
            $base_filter_str = http_build_query($base_filter);

            $actions[] =  array(
            'label'=>'导出',
            'submit'=>'index.php?app=omedlyexport&ctl=ome_delivery&act=index&action=export',
            'target'=>'dialog::{width:600,height:300,title:\'导出\'}'
            );
       }
        $this->finder('ome_mdl_delivery',array(
            'title' => '发货单',
            'base_filter' => $filter,
            'actions' => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
       ));
    }

    function reback(){
        $this->page('admin/delivery/reback_delivery.html');
    }

    function back(){
        $this->begin();
        if (empty($_POST['select_bn']) && empty($_POST['bn_select'])){
            $this->end(false, '请输入正确的单号', '', $autohide);
        }
        $autohide = array('autohide'=>3000);
        $Objdly  = &app::get('ome')->model('delivery');
        $OiObj  = &app::get('ome')->model('delivery_items');
        $ObjdlyOrder  = &app::get('ome')->model('delivery_order');
        $orderObj = &app::get('ome')->model('orders');
        $pObj = &app::get('ome')->model('products');
        $select_type = 'order_bn';
        //
        $orders = $orderObj->dump(array('order_bn'=>$_POST['bn_select']),'order_id');
        if (empty($orders)) {
            $this->end(false, '此订单号不存在!', '', $autohide);
        }
        $order_id = $orders['order_id'];

        $deliveryids = $Objdly->getDeliverIdByOrderId($order_id);
        if (!$deliveryids) {
            $this->end(false, '订单号不存在对应发货单!', '', $autohide);
        }
        $delivery_list = $Objdly->getList('*',array('delivery_id'=>$deliveryids,'pause'=>'false','process'=>'false'));
        if(empty($delivery_list)){
                $this->end(false, '没有该单号的发货单', '', $autohide);
        }
        $detail = array();
        foreach ($delivery_list as $delivery ) {
            $items = $OiObj->getList('*',array('delivery_id'=>$delivery['delivery_id']));
            #获取订单
            $order_bn = $ObjdlyOrder->getOrderInfo('order_bn',$delivery['delivery_id']);
            foreach($items as $k=>$value){
                $barcode = $pObj->dump(array('product_id'=>$value['product_id']),'barcode');
                $items[$k]['barcode'] = $barcode['barcode'];
            }
            if(($delivery['stock_status']=='true') || ($delivery['deliv_status']=='true') || ($delivery['expre_status']=='true')){
                $this->pagedata['is_confirm'] = true;
            }
            if($delivery['is_bind']=='true'){
              $countinfo = $Objdly->getList('count(parent_id)',array('parent_id'=>$delivery['delivery_id']));
              $count = $countinfo[0]['count(parent_id)'];
              $this->pagedata['height'] = 372+26*$count;
            }
                $consignee['name'] = $delivery['ship_name'];
                $consignee['area'] = $delivery['ship_area'];
                $consignee['province'] = $delivery['ship_province'];
                $consignee['city'] = $delivery['ship_name'];
                $consignee['district'] = $delivery['ship_district'];
                $consignee['addr'] = $delivery['ship_addr'];
                $consignee['zip'] = $delivery['ship_zip'];
                $consignee['telephone'] = $delivery['ship_telephone'];
                $consignee['mobile'] = $delivery['ship_mobile'];
                $consignee['email'] = $delivery['ship_email'];
                $consignee['r_time'] = $delivery['ship_name'];
            $detail[] = array(
                'items'=>$items,
                'consignee'=>$consignee,
                'delivery_bn'=>$delivery['delivery_bn'],
                'delivery'=>$delivery['delivery'],
                'logi_name'=>$delivery['logi_name'],
                'logi_no'=>$delivery['logi_no'],
                'weight'=>$delivery['weight'],
                 'delivery_id'=>$delivery['delivery_id'],
            
            );
        }
        $this->pagedata['select_type'] = $select_type;
        $this->pagedata['bn_select']   = $_POST['bn_select'];
         $this->pagedata['orders'] = $orders;
        $this->pagedata['detail']      = $detail;
        $this->page('admin/delivery/reback_delivery.html');
    }

    /**
     * 打回操作
     *
     */
    function doReback(){
        $rs = array('rsp'=>'succ','msg'=>'撤销成功');
        $autohide = array('autohide'=>3000);
        $memo = $_POST['memo'];
        $Objdly  = &app::get('ome')->model('delivery');
        $delivery_id = $_POST['delivery_id'];
        $flag = $_POST['flag'];
        if ($delivery_id) {
            if ($flag == 'OK') {//合单时拆分
                foreach ($delivery_id as $deliveryid ) {
                    $result = $Objdly->splitDelivery($deliveryid, $_POST['id'],$_POST['id']);
      
                    if ($result) {
                        $Objdly->rebackDelivery($_POST['id'], $memo);
                    }else{
                       $rs = array('rsp'=>'fail','msg'=>'撤销失败');
                    }
                }
            }else{
                $result = $Objdly->rebackDelivery($delivery_id, $memo);
                if (!$result) {
                    $rs = array('rsp'=>'fail','msg'=>'撤销失败');
                }
            }
        }
        
        
        echo json_encode($rs);
    }

 

    /**
     * 填写打回备注
     *
     * @param bigint $dly_id
     */
    function showmemo(){
        $deliveryObj  = &$this->app->model('delivery');
        $dly_id = $_GET['delivery_id'];

        $dly          = $deliveryObj->getlist('delivery_id,is_bind,delivery_bn,status,process',array('delivery_id'=>$dly_id));
        $idd = array();
        foreach ($dly as $dk=>$dy ) {
            if ($dy['process'] == 'true' || in_array($dy['status'],array('failed', 'cancel', 'back', 'succ','return_back'))){
            echo '<script>alert("当前发货单已发货或者已取消不可撤销!");</script>';
            exit;
            
            }
            if ($dy['is_bind'] == 'true'){
                $ids = $deliveryObj->getItemsByParentId($dy['delivery_id'], 'array');
                $returnids = implode(',', $ids);
                
                if ($ids){
                    foreach ($ids as $v){
                        $delivery = $deliveryObj->dump($v, 'delivery_bn');
                        $order_id = $deliveryObj->getOrderBnbyDeliveryId($v);
                        $idd[$v]['delivery_bn'] = $delivery['delivery_bn'];
                        $idd[$v]['order_bn'] = $order_id['order_bn'];
                        $idd[$v]['delivery_id'] = $v;
                        $dly[$dk]['idd'] =$idd; 
                    }
                }
                
            }
        }
 
        $this->pagedata['returnids'] = $returnids;
                $this->pagedata['ids'] = $ids;
                $this->pagedata['idd'] = $idd;
        $this->pagedata['dly'] = $dly;
       
        $this->display("admin/delivery/delivery_showmemo.html");
    }

   

  

    
 
}
?>
