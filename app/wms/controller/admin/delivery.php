<?php
class wms_ctl_admin_delivery extends desktop_controller{
    var $name = "发货单";
    var $workground = "invoice_center";

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

        $this->finder('wms_mdl_delivery',array(
            'title' => '发货单',
            'base_filter' => $filter,
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

        $dlyObj  = &app::get('wms')->model('delivery');
        $OiObj  = &app::get('wms')->model('delivery_items');
        $dlyBillLib = kernel::single('wms_delivery_bill');


        if($_POST['select_bn'] == 'logi_no'){
            $select_type = 'logi_no';

            $delivery_id = $dlyBillLib->getDeliveryIdByPrimaryLogi($_POST['bn_select']);
            if(!$delivery_id){
                $delivery_id = $dlyBillLib->getDeliveryIdBySecondaryLogi($_POST['bn_select']);
            }

            $detail = $dlyObj->dump(array('delivery_id'=>$delivery_id));
        }elseif($_POST['select_bn']=='delivery_bn'){
            $select_type = 'delivery_bn';
            $detail = $dlyObj->dump(array('delivery_bn'=>$_POST['bn_select']));
        }

        $items = $OiObj->getList('*',array('delivery_id'=>$detail['delivery_id']));
        if(empty($detail)){
            $this->end(false, '没有该单号的发货单', '', $autohide);
        }
        if($detail['status'] == 1){
            $this->end(false, '该发货单已经被打回，无法继续操作', '', $autohide);
        }
        if($detail['delivery_logi_number'] > 0){
            $this->end(false, '该发货单已部分发货，无法继续操作', '', $autohide);
        }
        if($detail['status'] == 2){
            $this->end(false, '该发货单已暂停，无法继续操作', '', $autohide);
        }
        if($detail['status'] == 3){
            $this->end(false, '该发货单已经发货，无法继续操作', '', $autohide);
        }
        if($detail['type'] == 'reject'){
            $this->end(false, '该发货单是原样寄回的单子，无法继续操作', '', $autohide);
        }

        $pObj = &app::get('ome')->model('products');
        foreach($items as $k=>$value){
            $barcode = $pObj->dump(array('product_id'=>$value['product_id']),'barcode');
            $items[$k]['barcode'] = $barcode['barcode'];
        }

        if((($detail['print_status'] & 1) == 1) || (($detail['print_status'] & 2) == 2 ) || (($detail['print_status'] & 4) == 4)){
            $this->pagedata['is_confirm'] = true;
        }

        $this->pagedata['select_type'] = $select_type;
        $this->pagedata['bn_select']   = $_POST['bn_select'];
        $this->pagedata['items']       = $items;
        $this->pagedata['detail']      = $detail;
        $this->page('admin/delivery/reback_delivery.html');
    }

    /**
     * 打回操作
     *
     */
    function doReback(){
        $autohide = array('autohide'=>3000);
        $this->begin('index.php?app=wms&ctl=admin_delivery&showmemo&p[0]='.$_POST['delivery_id']);
        if (empty($_POST['memo'])){
            $this->end(false, '备注请不要留空', '', $autohide);
        }

        $dlyObj  = &app::get('wms')->model('delivery');
        $dlyProcessLib = kernel::single('wms_delivery_process');
        $opObj = &app::get('ome')->model('operation_log');

        //$delivery_bn = $dlyObj->dump(array('delivery_id'=>$_POST['delivery_id']),'delivery_bn');
        //$logi_info = $delivery_bn['logi_no'] ;

        $dlyProcessLib->rebackDelivery($_POST['delivery_id'], $_POST['memo']);
        //$opObj->write_log('delivery_back@wms', $_POST['delivery_id'], '发货单打回');

        //如果安装拣货app，将拣货单状态设为取消
        if (app::get('tgkpi')->is_installed()) {
            $pickObj = &app::get('tgkpi')->model('pick');
            $pickObj->update(array('pick_status'=>'cancel'),array('delivery_id'=>$_POST['delivery_id']));
        }
        $this->end(true, '操作成功', 'index.php?app=wms&ctl=admin_delivery&act=reback', $autohide);

    }

    /**
     * 填写打回备注
     *
     * @param bigint $dly_id
     */
    function showmemo($dly_id){
        $deliveryObj  = &app::get('wms')->model('delivery');
        $dly          = $deliveryObj->dump($dly_id,'delivery_bn');
        $this->pagedata['delivery_id'] = $dly_id;
        $this->pagedata['delivery_bn'] = $dly['delivery_bn'];
        $this->display("admin/delivery/delivery_showmemo.html");
    }

}
?>
