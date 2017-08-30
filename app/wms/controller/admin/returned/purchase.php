<?php
/**
 * 退货单
 * @author ome team
 * @copyright www.shopex.cn 2010.4.27
 */
class wms_ctl_admin_returned_purchase extends desktop_controller{

    var $name = "退货单";
    var $workground = "wms_center";

    function oList() {
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
    	   if ($branch_ids){
            $params['base_filter']['branch_id'] = $branch_ids;
        }else{
            $params['base_filter']['branch_id'] = 'false';
        }
        $params = array(
            'title'=> '采购退货',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'orderBy' => 'returned_time desc',
            'base_filter' => array('rp_type'=>'eo','return_status'=>'1','check_status'=>'2'),
        	'finder_cols'=>'column_edit,supplier_id,name,product_cost,delivery_cost,amount,logi_no,return_status,operator',
        );
        $this->finder('purchase_mdl_returned_purchase', $params);
    }


    /**
     * 退货出库
     *
     */
    function purchaseShift($rp_id){
        
        $this->begin('index.php?app=wms&ctl=admin_returned_purchase&act=oList');
        if (empty($rp_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        $rpObj = &app::get('purchase')->model('returned_purchase');
        $suObj = &app::get('purchase')->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $pObj = &app::get('ome')->model('products');
        $data = $rpObj->dump($rp_id, '*', array('returned_purchase_items' => array('*')));
        //当前供应商
        $supplier_detail = $suObj->dump($data['supplier_id'], 'supplier_id,name');
        $this->pagedata['supplier_detail'] = $supplier_detail;

        /*编辑不允许改变仓库，所以默认为单仓库
        //获取仓库模式
        $branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        */
        if (!$branch_mode){
            $branch_mode = 'single';
        }
        $this->pagedata['branch_mode'] = $branch_mode;

        $su = $suObj->dump($data['supplier_id'],'name');
        $br = $brObj->dump($data['branch_id'], 'name');
        $data['branch_name']   = $br['name'];
        $data['supplier_name'] = $su['name'];
        $this->pagedata['po_items'] = $data['returned_purchase_items'];
        $data['memo'] = unserialize($data['memo']);
        $this->pagedata['po'] = $data;
        $this->singlepage("admin/returned/purchase/purchase_shift.html");
    }

     /**
     * 保存退货出库
     *
     */
    function doShift() {
        $this->begin('index.php?app=wms&ctl=admin_returned_purchase&act=oList');
        $rp_id = $_POST['rp_id'];
        $at = $_POST['at'];
        $pr = $_POST['pr'];
        $ato = $_POST['at_o'];
        $ids = $_POST['ids'];

        $rpObj = &app::get('purchase')->model('returned_purchase');
        $rp_itemObj = &app::get('purchase')->model('returned_purchase_items');
        $oProducts = &app::get('ome')->model("products");
        $branchProductObj = &app::get('ome')->model("branch_product");
        $data = $rpObj->dump($rp_id, '*', array('returned_purchase_items'=>array('*')));

        $total = 0;
        if(empty($at) || empty($pr)){
            $this->end(false, '暂无出库货品', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
        }
        foreach($at as $k=>$v){
            if($v != $ato[$k]){
               $this->end(false, '出库数量与退货数量不符', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
            }

        }

        foreach($ids as $k=> $i){
            $rp_items = $rp_itemObj->dump($i,'price,product_id,num,barcode,name,spec_info,bn');

            $Products = $oProducts->dump($rp_items['product_id'],'unit,goods_id,store');

             if(app::get('taoguaninventory')->is_installed()){
                 $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($rp_items['product_id'],$data['branch_id']);

                if(!$check_inventory){
                    $this->end(false, '此商品正在盘点中，不可以出入库操作!', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
                }
             }
            $total += $at[$k]*$rp_items['price'];
            $shift_items[$rp_items['product_id']] = array(
                'product_id' => $rp_items['product_id'],
                'product_bn' => $rp_items['bn'],
                'name' => $rp_items['name'],
                'spec_info' => $rp_items['spec_info'],
                'bn' => $rp_items['bn'],
                'unit' => $Products['unit'],
                'store' => $Products['store'],
                'price' => $rp_items['price'],//1212增加
                'nums' => $at[$k],
              );
        }

        foreach($shift_items as $v){
            if($v['nums'] > $v['store']){
               $this->end(false, '产品条码: ' . $v['product_bn'].' 出库数量大于实际库存', 'index.php?app=wms&ctl=admin_returned_purchase&act=purchaseShift');
            }
        }

        //事件触发，通知oms采购退货单入库
        $outdata = array(
            'rp_id'=>$rp_id,
            'memo'=>htmlspecialchars($_POST['memo']),
            'items'=>$shift_items,
        );
        #
        kernel::single('wms_event_trigger_purchasereturn')->outStorage($outdata, true);
        #kernel::single('wms_iostockdata')->notify_purchaseReturn($data,'update');
       
       $this->end(true, '出库成功');
    }

    /**
     * 打印退货单
     *
     * @param int $rp_id
     */
    function printItem($rp_id){
        $rpObj = &app::get('purchase')->model('returned_purchase');
        $suObj = &app::get('purchase')->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $prObj = &app::get('ome')->model('products');
        $rp = $rpObj->dump($rp_id, '*', array('returned_purchase_items'=>array('*')));
        $su = $suObj->dump($rp['supplier_id'],'name');
        $bran = $brObj->dump($rp['branch_id'],'name');
        $rp['supplier'] = $su['name'];
        $rp['branch'] = $bran['name'];
        $rp['memo'] = unserialize($rp['memo']);
        $rp['po_items'] = $rp['returned_purchase_items'];
        $this->pagedata['po'] = $rp;
        $this->pagedata['time'] = time();
        $this->pagedata['base_dir'] = kernel::base_url();

        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],'purreturn',$this);
 
    }

    /**
     * 拒绝退货
     *
     * @param int $po_id
     */
    function cancel($rp_id){
        $rpObj = app::get('purchase')->model('returned_purchase');
        if(count($_POST)>0){
            $rp_id = $_POST['rp_id'];
            $rp = $rpObj->dump($rp_id, 'memo,rp_bn,branch_id');
            $operator = $_POST['operator'];
            $this->begin('index.php?app=wms&ctl=admin_returned_purchase&act=oList');
            if (empty($rp_id)){
                $this->end(false,'操作出错，请重新操作');
            }
            $newmemo =  htmlspecialchars($_POST['memo']);
            $data = array('io_bn'=>$rp['rp_bn'],'io_type'=>'PURCHASE_RETURN','branch_id'=>$rp['branch_id'],'memo'=>$newmemo);
            kernel::single('wms_event_trigger_purchasereturn')->cancel($data, true);
            #kernel::single('wms_iostockdata')->notify_purchaseReturn(array('rp_id'=>$rp_id,'memo'=>$newmemo),'CANCEL');
            $this->end(true, '出库拒绝已完成');
        }else{
            $rp = $rpObj->dump($rp_id, 'supplier_id');
            $oSupplier = &app::get('purchase')->model('supplier');
            $supplier = $oSupplier->dump($rp['supplier_id'], 'operator');
            $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
            $this->pagedata['id'] = $rp_id;
            $this->display("admin/returned/purchase/purchase_cancel.html");
        }
    }
    #使用扫描枪时，根据条形码,获取product_id
    function getProductId(){
        $barcode = $_POST['barcode'];
        $obj_product = app::get('ome')->model('products');
        $product_id = $obj_product->getList('product_id',array('barcode'=>$barcode));
        if(!empty($product_id[0]['product_id'])){
            echo $product_id[0]['product_id'];
        }else{
            echo NULL;
        }
    }

    
    
}