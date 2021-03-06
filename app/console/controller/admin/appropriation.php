<?php
class console_ctl_admin_appropriation extends desktop_controller{
    var $name = "调拔计划";
    var $workground = "console_purchasecenter";
    function index(){

        $actions = array(
                array(
                    'label'=>'新建',
                    'href'=>'index.php?app=console&ctl=admin_appropriation&act=addtransfer',
                    'target'=>'_blank'
                    ),
                array(
                    'label'=>'导出模板',
                    'href'=>'index.php?app=console&ctl=admin_appropriation&act=exportTemplate',
                    'target'=>'_blank'
                    ),
                    
        );

        $params = array('title'=>'新建调拔单',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>true,
                        'use_buildin_import'=>true,
                        'use_buildin_filter'=>true,
                        'orderBy' => 'appropriation_id desc'
                    );
        /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();

        //只要有一个仓库管理权限就显示新建调拨单按钮
        $is_new = true;
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                if(count($branch_ids)>1){
                    $is_new = true;
                }
                $oApp = &app::get('taoguanallocate')->model('appropriation_items');
                $app_list = $oApp->getList('appropriation_id', array('to_branch_id'=>$branch_ids), 0,-1);
                $app_list1 = $oApp->getList('appropriation_id', array('from_branch_id'=>$branch_ids), 0,-1);
                $app_lists = array_merge($app_list,$app_list1);

                $app_list_data = array();
                if ($app_lists)
                foreach ($app_lists as $p){
                    $app_list_data[] = $p['appropriation_id'];
                }
                if ($app_list_data){
                    $app_list_data = array_unique($app_list_data);
                    $params['base_filter']['appropriation_id'] = $app_list_data;
                }else{
                    $params['base_filter']['appropriation_id'] = 'false';
                }
            }else{
                $params['base_filter']['appropriation_id'] = 'false';
            }
        }else{
            $branch_list = $oBranch->Get_branchlist();
            if(count($branch_list)>1){
                $is_new = true;
            }
        }

        if($is_new){
           $params['actions'] = $actions;
        }

        $this->finder('taoguanallocate_mdl_appropriation', $params);
    }



     /*
    * 新建调拨单
    */
    function addtransfer(){
        $OBranch = &app::get('ome')->model('branch');
        $branch  = $OBranch->getList('branch_id, name',array('type'=>array('main','damaged')),0,-1);
        $allBranch  = $OBranch->getAllBranchs('branch_id, name');

        $OProducts= &app::get('ome')->model('products');

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $OBranch->getBranchByUser();
           if(count($branch_list)>1){
               $from_branch_check = $branch_list[0]['branch_id'];
               $to_branch_check = $branch_list[1]['branch_id'];
           }
        }else{
           $branch_list = $branch;
           if(count($branch)>1){
               $from_branch_check = $branch[0]['branch_id'];
               $to_branch_check = $branch[1]['branch_id'];
           }
        }
        $oDly_corp = app::get('ome')->model('dly_corp');
        $dly_corp = $oDly_corp->getlist('*',array('disabled'=>'false'));
        $this->pagedata['dly_corp'] = $dly_corp;
        $this->pagedata['from_branch_check'] = $from_branch_check;
        $this->pagedata['to_branch_check'] = $to_branch_check;

        $this->pagedata['all_branch']   = $allBranch;
        $this->pagedata['branch_list']   = $branch_list;
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['branch'] = $branch ;
        $appropriation_type = &app::get('ome')->getConf('taoguanallocate.appropriation_type');
        if (!$appropriation_type) $appropriation_type = 'directly';
        $this->pagedata['appropriation_type'] = $appropriation_type;

        $this->singlepage("admin/appropriation/transfer.html");
    }

    function getProducts($from_branch_id = null,$to_branch_id = null){
        $pStockObj = kernel::single('console_stock_products');
        $pro_id = $_POST['product_id'];
        $pro_bn= $_GET['bn'];
        $pro_barcode= $_GET['barcode'];

        $pro_name= $_GET['name'];

        if (is_array($pro_id)){
            $filter['product_id'] = $pro_id;
        }

        if($pro_bn){

           $filter = array(
               'bn'=>$pro_bn
           );
        }

        if($pro_barcode){

           $filter = array(
               'barcode'=>$pro_barcode
           );
        }

        if($pro_name){
            $filter = array(
               'name'=>$pro_name
           );
        }

        $pObj = &app::get('ome')->model('products');
        $pObj->filter_use_like = true;
        $data = $pObj->getList('visibility,product_id,bn,name,price,barcode,spec_info',$filter,0,-1);
        $pObj->filter_use_like = false;

        $rows = array();
        $pids = array();
        if (!empty($data)){
            $oBranchProduct = &app::get('ome')->model('branch_product');
            foreach ($data as $k => $item){
                $pids[] = $item['product_id'];
            }
            #$from_branch_product_store = $oBranchProduct->getStoreListByBranch($from_branch_id,$pids);
            #$to_branch_product_store = $oBranchProduct->getStoreListByBranch($to_branch_id,$pids);

            foreach ($data as $k => $item){
                $item['price'] = app::get('purchase')->model('po')->getPurchsePrice($item['product_id'], 'asc');
                $item['num'] = (isset($filter['barcode']))?1:0;
                $item['from_branch_num'] = $pStockObj->get_branch_usable_store($from_branch_id,$item['product_id']);
                $item['to_branch_num'] = $pStockObj->get_branch_usable_store($to_branch_id,$item['product_id']);
                $rows[] = $item;
            }
        }

        echo "window.autocompleter_json=".json_encode($rows);
    }

     /*
     * 调拔单保存
     */
    function do_save(){
        $this->begin();
        $oAppropriation = &app::get('taoguanallocate')->model('appropriation');
        $oBranch_product = &app::get('ome')->model('branch_product');
        $channelLib = kernel::single('channel_func');
        $branchLib = kernel::single('ome_branch');
        $from_branch_id = $_POST['from_branch_id'];
        $to_branch_id = $_POST['to_branch_id'];
        $memo = $_POST['memo'];
        $nums = $_POST['at'];
        $from_branch_num = $_POST['from_branch_num'];
        $to_branch_num = $_POST['to_branch_num'];
        $operator = $_POST['operator'];
        $product_id = $_POST['product_id'];
        $appropriation_type = $_POST['appropriation_type'];

        if(!$from_branch_id || !$to_branch_id){
           $this->end(false,'请选择调出仓库和调入仓库','index.php?app=console&ctl=admin_appropriation&act=addtransfer');
        }
        $from_wms_id = $branchLib->getWmsIdById($from_branch_id);
        $to_wms_id = $branchLib->getWmsIdById($to_branch_id);
        $from_is_selfWms = $channelLib->isSelfWms($from_wms_id);//调出仓库是否自有仓储
        $to_is_selfWms = $channelLib->isSelfWms($to_wms_id);//调入仓库是否自有仓储
        if($appropriation_type==1&&(!$from_is_selfWms||!$to_is_selfWms)){
            $this->end(false,'第三方仓库只能使用出入库调拨！','index.php?app=console&ctl=admin_appropriation&act=addtransfer');
        }
        if($from_branch_id == $to_branch_id){
            if($from_branch_id[$v] == $to_branch_id[$v]){
                $this->end(false,'调出仓库和新仓库不能是同一个','index.php?app=console&ctl=admin_appropriation&act=addtransfer');
            }
        }

        if(empty($nums)){
            $this->end(false, '调拨单中必须有商品', 'index.php?app=console&ctl=admin_appropriation&act=addtransfer');
        }

        foreach($nums as $product_id=>$num){
            if(app::get('taoguaninventory')->is_installed()){

            $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($product_id,$to_branch_id);

            if(!$check_inventory){
                $this->end(false, '此商品正在盘点中，不可以调拔!', 'index.php?app=console&ctl=admin_appropriation&act=addtransfer');
            }
             $check_inventory1 = kernel::single('taoguaninventory_inventorylist')->checkproductoper($product_id,$from_branch_id);

            if(!$check_inventory1){
                $this->end(false, '此商品正在盘点中，不可以调拔!', 'index.php?app=console&ctl=admin_appropriation&act=addtransfer');
            }
            }
           if($from_branch_num[$product_id]<intval($num)) {
                $this->end(false,'调拨数量('.$num.')不能大于库存数量('.$from_branch_num[$product_id].')','index.php?app=console&ctl=admin_appropriation&act=addtransfer');
           }

           if(intval($num)==0){
               $this->end(false,'调拨数量不可为0','index.php?app=console&ctl=admin_appropriation&act=addtransfer');
           }

           $adata[] = array('from_pos_id'=>0,'to_pos_id'=>0,'from_branch_id'=>$from_branch_id,'to_branch_id'=>$to_branch_id,'product_id'=>$product_id,'num'=>$num,'from_branch_num'=>$from_branch_num[$product_id],'to_branch_num'=>$to_branch_num[$product_id],'corp_id'=>$_POST['corp_id']);

        }
        $iostockObj = kernel::single('console_iostockdata');
        $allocateObj = kernel::single('console_receipt_allocate');
        $result = $allocateObj->to_savestore($adata,$appropriation_type,$memo,$operator,$msg);
        if($result){

            //调拔出库通知已修改为审核时才发起通知
            #$iostockObj->notify_otherstock(1,$result,'create');
            $this->end(true,'调拔成功!','index.php?app=console&ctl=admin_appropriation');
        }else{
             $this->end(false,'调拔失败!','',array('msg'=>$msg));
        }

    }

    function findProduct(){

        # 隐藏商品不显示
        if (!isset($_POST['visibility'])) {
            $base_filter['visibility'] = 'true';
        }elseif(empty($_POST['visibility'])){
            unset($_POST['visibility']);
        }

        if ($_GET['branch_id']) {
            $base_filter['product_group'] = true;
            $base_filter['branch_id'] = $_GET['branch_id'];
            $object_method = array('count'=>'countAnother','getlist'=>'getBranchPdtList');
        }

        $params = array(
            'title'=>'仓库货品列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'base_filter' => $base_filter,
            'object_method'=>$object_method,
        );
        $this->finder('ome_mdl_products', $params);
    }

    /**
    * 打印调拔单
    */
    function printAppropriation($iso_id){
        $oAppropriation_items = app::get('taoguanallocate')->model('appropriation_items');
        $items = $oAppropriation_items->select()->columns('*')->where('appropriation_id=?',$iso_id)->instance()->fetch_all();
        foreach ($items as $key => $item) {
            $items[$key]['spec_info'] = &$spec[$item['product_id']];
            $items[$key]['barcode'] = &$barcode[$item['product_id']];
            $items[$key]['frome_branch_store'] = &$frome_branch_store[$item['from_branch_id']][$item['product_id']];
            $items[$key]['to_branch_store'] = &$to_branch_store[$item['to_branch_id']][$item['product_id']];

            $product_id[] = $item['product_id'];
        }

        if ($items) {

            $productList = app::get('ome')->model('products')->getList('product_id,spec_info,barcode',array('product_id'=>$product_id));
            foreach ($productList as $product) {
                $spec[$product['product_id']] = $product['spec_info'];
                $barcode[$product['product_id']] = $product['barcode'];
            }

            $branch_products = app::get('ome')->model('branch_product')->getList('product_id,branch_id,store',array('product_id'=>$product_id,'branch_id'=>array($items[0]['from_branch_id'],$items[0]['to_branch_id'])));
            foreach ($branch_products as $key => $value) {
                $frome_branch_store[$value['branch_id']][$value['product_id']] = $value['store'];
                $to_branch_store[$value['branch_id']][$value['product_id']] = $value['store'];
            }
        }

        if ($items[0]) {
            $from_branch_id = $items[0]['from_branch_id']; $to_branch_id = $items[0]['to_branch_id'];

            $branches = app::get('ome')->model('branch')->getList('name,branch_id',array('branch_id'=>array($from_branch_id,$to_branch_id)));

            foreach ($branches as $key => $branch) {
                if ($from_branch_id == $branch['branch_id']) {
                    $this->pagedata['from_branch_name'] = $branch['name'];
                }

                if ($to_branch_id == $branch['branch_id']) {
                    $this->pagedata['to_branch_name'] = $branch['name'];
                }
            }
        }
        $this->pagedata['items'] = $items;

        $oAppropriation = app::get('taoguanallocate')->model('appropriation');
        $Appropriation_info = $oAppropriation->dump($iso_id,'memo');
        $this->pagedata['memo'] = $Appropriation_info['memo'];
        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],'appropriation',$this);
    }

    /**
    * 删除未入库调拔单
    *
    *
    */
    function deleteAppropriation($id){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        $iostockorder = &app::get('taoguaniostockorder')->model('iso')->dump(array('original_id'=>$id,'type_id'=>40),'confirm');

        if ($iostockorder['confirm']!='N'){
            $this->end(false,'入库单已确认不可以删除!');
        }else{
            $result = app::get('taoguanallocate')->model('appropriation')->deleteAppropriation($id);
            if ($result) {
                $this->end(true,'删除成功!');
            }else{
                $this->end(false,'删除失败!');
            }
        }
    }

    /**
    * 根据采购单编号返回采购单所有信息
    *
    */
    function getPurchaseBybn($bn,$from_branch_id = null,$to_branch_id = null) {
        $data = array();
        $purchase = &app::get('purchase');
        $Po = $purchase->model('po')->getlist('branch_id,po_id',array('po_bn'=>$bn,'eo_status'=>3),0,1);//已入库
        $data = $Po[0];
        $total_nums = 0;
        $items = $purchase->model('po_items')->getlist('*',array('po_id'=>$data['po_id']),0,-1);
        foreach($items as $ik=>$iv) {
            $items[$ik]['num'] = $iv['in_num'];
            $total_nums+=$iv['in_num'];

            $pids[] = $iv['product_id'];
        }

        $oBranchProduct = &app::get('ome')->model('branch_product');
        $from_branch_product_store = $oBranchProduct->getStoreListByBranch($from_branch_id,$pids);
        $to_branch_product_store = $oBranchProduct->getStoreListByBranch($to_branch_id,$pids);
        foreach ($items as $k => $item){
            $items[$k]['from_branch_num'] = isset($from_branch_product_store[$item['product_id']]) ? $from_branch_product_store[$item['product_id']] : 0;
            $items[$k]['to_branch_num'] = isset($to_branch_product_store[$item['product_id']]) ? $to_branch_product_store[$item['product_id']] : 0;
        }

        $data['total_nums'] = $total_nums;
        $data['items'] = $items;
        echo json_encode($data);

    }
    /**
    * 导出调拨单模板
    *
    */
    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=调拨单模板".date('YmdHis').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        //导出操作日志
        $logParams = array(
            'app' => $this->app->app_id,
            'ctl' => trim($_GET['ctl']),
            'act' => trim($_GET['act']),
            'modelFullName' => '',
            'type' => 'export',
            'params' => array(),
        );
        ome_operation_log::insert('warehouse_other_template_export', $logParams);
        $appropriationObj = &app::get('taoguanallocate')->model('appropriation');
        $title1 = $appropriationObj->exportTemplate('appropriation');
        $title2 = $appropriationObj->exportTemplate('items');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }
}
?>