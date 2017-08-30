<?php
class taoguaniostockorder_ctl_admin_iostockorder extends desktop_controller{
    var $name = "出入库管理";
    var $workground = "storage_center";

    /**
     *
     * 其他入库列表
     */
    function other_iostock(){
        $io = $_GET['io'];
        if($io){
            $title = '其他入库';
        }else{
            $title = '其他出库';
        }

        $params = array(
           'actions' => array(
                array(
                    'label'=>'新建',
                    'href'=>'index.php?app=taoguaniostockorder&ctl=admin_iostockorder&act=iostock_add&p[0]=other&p[1]='.$io,
                    'target'=>'_blank'
                ),
               array('label'=>app::get('taoguaniostockorder')->_('导出模板'),'href'=>'index.php?app=taoguaniostockorder&ctl=admin_iostockorder&act=exportTemplate&p[1]='.$io,'target'=>'_blank'),
            ),
            'title'=>$title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            //'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
            'finder_cols'=>'column_confirm,name,iso_bn,oper,operator,original_bn,create_time,type_id',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $oIso = &$this->app->model('iso');
                $iso_list = $oIso->getList('iso_id', array('branch_id'=>$branch_ids), 0,-1);
                if ($iso_list){
                    foreach ($iso_list as $p){
                        $isolist[] = $p['iso_id'];
                    }
                }
                if ($isolist){
                    $isolist = array_unique($isolist);
                    $params['base_filter']['iso_id'] = $isolist;
                }else{
                    $params['base_filter']['iso_id'] = 'false';
                }
            }else{
                $params['base_filter']['iso_id'] = 'false';
            }
        }

        $params['base_filter']['type_id'] = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type($io,true);
        $params['base_filter']['confirm'] = 'N';

        $this->finder('taoguaniostockorder_mdl_iso', $params);
    }

    function allocate_iostock(){
        $io = $_GET['io'];
        $iostock_instance = kernel::service('ome.iostock');
        if($io){
            $title = '调拨入库';
            eval('$type='.get_class($iostock_instance).'::ALLOC_STORAGE;');
        }else{
            $title = '调拨出库';
            eval('$type='.get_class($iostock_instance).'::ALLOC_LIBRARY;');
        }

        $params = array(
            'title'=>$title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'finder_cols'=>'column_confirm,name,iso_bn,oper,operator,original_bn,create_time,type_id',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $oIso = &$this->app->model('iso');
                $iso_list = $oIso->getList('iso_id', array('branch_id'=>$branch_ids), 0,-1);
                if ($iso_list){
                    foreach ($iso_list as $p){
                        $isolist[] = $p['iso_id'];
                    }
                }
                if ($isolist){
                    $isolist = array_unique($isolist);
                    $params['base_filter']['iso_id'] = $isolist;
                }else{
                    $params['base_filter']['iso_id'] = 'false';
                }
            }else{
                $params['base_filter']['iso_id'] = 'false';
            }
        }

        $params['base_filter']['type_id'] = $type;
        $params['base_filter']['confirm'] = 'N';

        $this->finder('taoguaniostockorder_mdl_iso', $params);
    }

    function iostock_add($type,$io){
        if($io){
            $order_label = '入库单';
        }else{
            $order_label = '出库单';
        }

        $suObj = &app::get('purchase')->model('supplier');
        $data  = $suObj->getList('supplier_id, name','',0,-1);

        $brObj = &app::get('ome')->model('branch');
        $row   = $brObj->getList('branch_id, name','',0,-1);

        /* 获取操作员管辖仓库 */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $brObj->getBranchByUser();
        }
        $this->pagedata['branch_list']   = $branch_list;
        $is_super = 1;
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['supplier'] = $data;
        $operator = kernel::single('desktop_user')->get_name();
        $this->pagedata['operator'] = $operator;

        $this->pagedata['branch']   = $row;
        $this->pagedata['branchid']   = $branch_id;
        $this->pagedata['cur_date'] = date('Ymd',time()).$order_label;
        $this->pagedata['io'] = $io;

        /*if ( $iostock_service = kernel::service('ome.iostock') ){
            if ( method_exists($iostock_service, 'get_iostock_types') ){
                $this->pagedata['iostock_types'] = $iostock_service->get_iostock_types();
            }
        }*/

        $this->pagedata['iostock_types'] = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type($io);

        if($io){
             $this->singlepage("admin/iostock/instock_add.html");
        }else{
             $this->singlepage("admin/iostock/outstock_add.html");
        }
    }

    function iostock_edit($iso_id,$io){
        $order_label = $io ? '入库单' : '出库单';

        //获取出入库单信息
        $isoObj = &$this->app->model('iso');
        $data = $isoObj->dump($iso_id, '*', array('iso_items' => array('*')));
        $productIds = array();
        foreach($data['iso_items'] as $k=>$v){
            $productIds[] = $v['product_id'];
            $total_num+=$v['nums'];
        }
        $data['total_num'] = $total_num;
        $data['items'] = implode('-',$productIds);

        //获取仓库信息
        $branchObj = &app::get('ome')->model('branch');
        $branch   = $branchObj->dump(array('branch_id'=>$data['branch_id']),'branch_id, name');
        $data['branch_name'] = $branch['name'];

        //获取出入库类型信息
        $iostockTypeObj = &app::get('ome')->model('iostock_type');
        $iotype = $iostockTypeObj->dump(array('type_id'=>$data['type_id']),'type_name');
        $data['type_name'] = $iotype['type_name'];

        $operator = kernel::single('desktop_user')->get_name();
        $data['oper'] = $data['oper'] ? $data['oper'] : $operator;

        $this->pagedata['io'] = $io;
        $this->pagedata['iso'] = $data;
        $this->pagedata['order_label'] = $order_label;
        $this->pagedata['act_status'] = trim($_GET['act_status']);
        $this->singlepage("admin/iostock/instock_edit.html");
    }

    function getEditProducts($iso_id){
        if ($iso_id == ''){
            $iso_id = $_POST['p[0]'];
        }
        $productObj = &app::get('ome')->model('products');
        $isoItemObj = &$this->app->model('iso_items');
        $rows = array();
        $items = $isoItemObj->getList('*',array('iso_id'=>$iso_id),0,-1);
        if ($items){
            $product_ids = array();
            foreach ($items as $k => $v){
                $product_ids[] = $v['product_id'];
                $items[$k]['name'] = $v['product_name'];
                $items[$k]['num'] = $v['nums'];
                $items[$k]['barcode'] = &$product[$v['product_id']]['barcode'];
                $items[$k]['visibility'] = &$product[$v['product_id']]['visibility'];
                $items[$k]['spec_info'] = &$product[$v['product_id']]['spec_info'];
                #新建调拨单时,如果开启固定成本，price就是商品价格；如果没有开启，则是0
                $items[$k]['price'] = $v['price'];
            }
            if($product_ids){
                $plist = $productObj->getList('product_id,visibility,barcode,spec_info',array('product_id'=>$product_ids));
                foreach ($plist as $value) {
                    $product[$value['product_id']]['visibility'] = $value['visibility'];
                    $product[$value['product_id']]['barcode'] = $value['barcode'];
                    $product[$value['product_id']]['spec_info'] = $value['spec_info'];
                }
            }
        }
        $rows = $items;
        echo json_encode($rows);
    }

    function do_edit_iostock(){
        $this->begin("index.php?app=taoguaniostockorder&ctl=admin_iostockorder");
        $data = $_POST;
        $data['old_items'] = explode('-',$data['old_items']);


        //出入库明细信息
        $branchProductObj = &app::get('ome')->model('branch_product');
        $isoItemObj = &app::get('taoguaniostockorder')->model('iso_items');
        $product_cost = 0;
        $iso_items = array();
        $productIds = array();
        $appropriation_items = array();

        foreach($data['bn'] as $product_id=>$bn){
            if($data['at'][$product_id] == 0) {
                $this->end(false, '库存数量不能为0.');
            }

            if($data['io'] == 0){
                $aRow = $branchProductObj->dump(array('product_id'=>$product_id, 'branch_id'=>$data['branch']),'store');
                if($data['at'][$product_id] > $aRow['store']){
                    $this->end(false, '货号：'.$bn.'出库数不可大于库存数'.$aRow['store']);
                }
            }

            $iso_items[$product_id] = array(
                'iso_id'=>$data['iso_id'],
                'iso_bn'=>$data['iso_bn'],
                'product_id'=>$product_id,
                'bn'=>$bn,
                'product_name'=>$data['product_name'][$product_id],
                'unit'=>$data['unit'][$product_id],
                'nums'=>$data['at'][$product_id],
                'price'=>$data['pr'][$product_id],
            );

            $item = array();
            $item = $isoItemObj->dump(array('product_id'=>$product_id, 'iso_id'=>$data['iso_id']),'iso_items_id');
            if($item['iso_items_id']>0){
                $iso_items[$product_id]['iso_items_id'] = $item['iso_items_id'];
            }

            $product_cost+= $data['at'][$product_id] * $data['pr'][$product_id];
            $productIds[] = $product_id;

            $appropriation_items[$product_id] = array(
                'product_id'=>$product_id,
                'bn'=>$bn,
                'product_name'=>$data['product_name'][$product_id],
                'num'=>$data['at'][$product_id],
            );
        }

        //出入库主单信息
        $operator = kernel::single('desktop_user')->get_name();
        $operator = $operator ? $operator : 'system';
        $iostockorder_data = array(
            'iso_id'=>$data['iso_id'],
            'name' => $data['iostockorder_name'],
            'iso_price' => $data['iso_price'],
            'oper' => $data['operator'],
            'operator' => $operator ,
            'product_cost'=>$product_cost,
            'memo' => $data['memo'],
            'iso_items'=>$iso_items,
        );

        $isoObj = &app::get('taoguaniostockorder')->model('iso');

        if($isoObj->save($iostockorder_data)){
            $delFilter = $delIds = array();
            $delIds = array_diff($data['old_items'], $productIds);
            $delIds = array_values($delIds);
            foreach($delIds as $key=>$val){
                if(!$val){
                    unset($delIds[$key]);
                }
            }
            if(is_array($delIds) && count($delIds)>0){
                $delFilter['iso_id'] = $data['iso_id'];
                $delFilter['product_id'] = $delIds;
                $isoItemObj->delete($delFilter);
            }

            #更新调拨单明细
            if($data['act_status'] == 'allocate_iostock') {//当是调拔出库时才更新调拔单
                $isodata = $isoObj->dump(array('iso_id'=>$data['iso_id']),'original_id');
                $filter = array('appropriation_id'=>$isodata['original_id']);

                $apprItemObj = app::get('taoguanallocate')->model('appropriation_items');
                $apprItems = $apprItemObj->dump($filter,'from_branch_id,to_branch_id,from_pos_id,to_pos_id');
                $apprItemObj->delete($filter);

                foreach($appropriation_items as $k=>$v){
                    $appropriation_items[$k]['appropriation_id'] = $isodata['original_id'];
                    $appropriation_items[$k]['from_branch_id'] = $apprItems['from_branch_id'];
                    $appropriation_items[$k]['from_pos_id'] = $apprItems['from_pos_id'];
                    $appropriation_items[$k]['to_pos_id'] = $apprItems['to_pos_id'];
                    $appropriation_items[$k]['to_branch_id'] = $apprItems['to_branch_id'];
                    $apprItemObj->save($appropriation_items[$k]);
                }
            }


            $this->end(true, '保存完成');
        }else{
            $this->end(false, '保存失败');
        }
    }

    function do_save_iostockorder(){
        $this->begin("index.php?app=taoguaniostockorder&ctl=admin_iostockorder");

        $_POST['iso_price'] = $_POST['iso_price'] ? $_POST['iso_price'] : 0;
        $oBranchProduct = &app::get('ome')->model('branch_product');
        $productObj = &app::get('ome')->model('products');

        if(!$_POST['bn']) {
            $this->end(false, '请先选择入库商品！.');            
        }

        $products = array();
        foreach($_POST['bn'] as $product_id=>$bn){
            if($_POST['at'][$product_id] == 0) {
                $this->end(false, '库存数量不能为0.');
            }

            if($_POST['io'] == 0){
                $aRow = $oBranchProduct->dump(array('product_id'=>$product_id, 'branch_id'=>$_POST['branch']),'store');
                if($_POST['at'][$product_id] > $aRow['store']){
                    $pInfo = array();
                    $pInfo = $productObj->dump($product_id,'name,bn');
                    $this->end(false, '货号：'.$pInfo['bn'].'出库数不可大于库存数'.$aRow['store']);
                }
            }

            $products[$product_id] = array('bn'=>$bn,
                'nums'=>$_POST['at'][$product_id],
                'unit'=>$_POST['unit'][$product_id],
                'name'=>$_POST['product_name'][$product_id],
                'price'=>$_POST['pr'][$product_id],
            );
        }
        $_POST['products'] = $products;
        if (kernel::single('taoguaniostockorder_iostockorder')->save_iostockorder($_POST,$msg)){
            $this->end(true, '保存完成');
        }else {
            //$msg['delivery_bn'] = $dly['delivery_bn'];
            $this->end(false, '保存失败', '', array('msg'=>$msg));
        }
    }

    function getProductStore(){
        $product_id=$_POST['pid'];
        $branch_id=$_POST['bid'];

        if($product_id>0 && $branch_id>0){
            $branchProductObj = &app::get('ome')->model('branch_product');
            $product = $branchProductObj->dump(array('product_id'=>$product_id, 'branch_id'=>$branch_id),'store');
            if(!empty($product) && is_array($product)){
                echo json_encode(array('result' => 'true', 'store' => $product['store']));
            }else{
                echo json_encode(array('result' => 'false', 'store' => 0));
            }
        }
    }

 	/**
     *
     * 出入库单查询
     */
  function search_iostockorder(){
        $io = $_GET['io'];
        switch ($io){
            case '1':
                $this->base_filter = array();
                $this->title = '入库单查询';
                $confirm_label = '入库单确认';
                $is_export = kernel::single('desktop_user')->has_permission('instockorder_export');#增加入库单导出权限
                break;
            case '0':
                $this->base_filter = array();
                $this->title = '出库单查询';
                $confirm_label = '出库单确认';
                $is_export = kernel::single('desktop_user')->has_permission('outstockorder_export');#增加出库单导出权限
                break;
        }

        $this->base_filter['confirm'] = 'Y';
        if($_POST['type_id']) {
            $this->base_filter['type_id'] = intval($_POST['type_id']);
        }else{
            $this->base_filter['type_id'] = kernel::single('taoguaniostockorder_iostockorder')->get_iso_type($io,true);
        }

        $this->finder('taoguaniostockorder_mdl_iso',array(
           'title' => $this->title,
           'actions' => array(
              // array('label'=>$confirm_label,'submit'=>'index.php?app=ome&ctl=admin_order&act=dispatching','target'=>'dialog::{width:400,height:200,title:\'订单分派\'}'),
           ),
           'base_filter' => $this->base_filter,
           'use_buildin_new_dialog' => false,
           'use_buildin_set_tag'=>false,
           'use_buildin_recycle'=>false,
           'use_buildin_export'=>$is_export,
           'use_buildin_import'=>false,
           'use_buildin_filter'=>true,
           'finder_cols'=>'name,iso_bn,oper,operator,original_bn,create_time,type_id',
           //'finder_aliasname'=>$finder_aliasname,
           //'finder_cols'=>$finder_cols,
        ));
    }

    function iostockorder_confirm($iso_id,$io){
        $oIso = &$this->app->model("iso");        
        $oIsoItems = &$this->app->model("iso_items");
        $oProducts  = &app::get('ome')->model("products");
        $oProduct_pos = &app::get('ome')->model("branch_product_pos");
        $count = count($oIsoItems->getList('*',array('iso_id'=>$iso_id), 0, -1));
        $iso_items = $oIsoItems->getList('*',array('iso_id'=>$iso_id));
        $iso = $oIso->dump($iso_id,'branch_id,supplier_id,type_id,original_id');
        foreach($iso_items as $k=>$v){
            $product = $oProducts->dump($v['product_id'],'unit,barcode');

            //$Po_items[$k]['name'] = $v['name'];
            //$iso_items[$k]['unit'] = $product['unit'];
            $iso_items[$k]['barcode'] = $product['barcode'];
            $assign = $oProduct_pos->get_pos($v['product_id'],$iso['branch_id']);
            if(empty($assign)){
                $iso_items[$k]['is_new']="true";
            }else{
                $iso_items[$k]['is_new']="false";
            }
            $iso_items[$k]['spec_info'] = $v['spec_info'];
            $iso_items[$k]['entry_num'] = $v['nums'];
        }

        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['iso_items'] = $iso_items;
        $this->pagedata['iso_id'] = $iso_id;
        $this->pagedata['count']=$count;//branch_id
        $this->pagedata['branch_id']=$iso['branch_id'];
        $this->pagedata['type_id'] = $iso['type_id'];
        $this->pagedata['original_id'] = $iso['original_id'];
        $this->pagedata['io'] = $io;
        if($io){
            $this->singlepage("admin/iostock/instock_confirm.html");
        }else{
            $this->singlepage("admin/iostock/outstock_confirm.html");
        }

    }

 	/**
     * 出入库确认
     */
    function save_iso_confirm(){  
        #error_log(var_export($_POST,1),3,'d:/test_log/save_iso_confirm.txt');
        $this->begin('index.php?app=taoguaniostockorder&ctl=admin_iostockorder');
        $oIsoItems = &$this->app->model("iso_items");
        $oIso = &$this->app->model("iso");
        $oBranch_pos = app::get('ome')->model("branch_pos");
        $oProduct_pos = app::get('ome')->model("branch_product_pos");
        $entry_num = $_POST['entry_num'];
        $iso_id = $_POST['iso_id'];
        $ids = $_POST['ids'];
        $branch_id = $_POST['branch_id'];
        //$pos_name = $_POST['pos_name'];
        //$pos_id = array();
        $Iso = $oIso->dump(array('iso_id'=>$iso_id),'confirm,supplier_id');
        
        $io = $_POST['io'];
        if($io){
            $label = '入库';
        }else{
            $label = '出库';
        }
        if($Iso['confirm']=='Y'){
            $this->end(false, '此单据已确认!', 'index.php?app=taoguaniostockorder&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
        }
        if (empty($ids)){
            $this->end(false, '请选择需要'.$label.'的商品', 'index.php?app=taoguaniostockorder&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
        }
        $ret = array();
        $error_bn = array();
        $oBranchProduct = &app::get('ome')->model('branch_product');
        foreach ($ids as $k=>$id) {
            if ($entry_num[$id] <= 0){
                $this->end(false, ''.$label.'量必须大于0', 'index.php?app=taoguaniostockorder&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
            }

            if($io == 0){
                $aRow = $oBranchProduct->dump(array('product_id'=>$_POST['product_ids'][$id], 'branch_id'=>$_POST['branch_id']),'store');
                if($entry_num[$id] > $aRow['store']){
                    $this->end(false, '出库数量不可大于库存数量.');
                }
            }

        }
         $iso_items = $oIsoItems->getList('*',array('iso_id'=>$iso_id));
         foreach($iso_items as $ik=>$iv){
             if(app::get('taoguaninventory')->is_installed()){
                 $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($iv['product_id'],$branch_id);

                if(!$check_inventory){
                    $this->end(false, '此商品正在盘点中，不可以出入库操作!', 'index.php?app=taoguaniostockorder&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
                }
             }
         }
         
        if($io && !empty($Iso['supplier_id']) && $_POST['type_id'] == '70' ){
            $oProducts = &app::get('ome')->model("products");
            $su_goodsObj = &app::get('purchase')->model('supplier_goods');

            foreach($iso_items as $ik=>$iv){
                $Products = $oProducts->dump($iv['product_id'],'goods_id');
                if($Products['goods_id']!=''){
                    $supplier_goods = array(
                        'supplier_id' => $Iso['supplier_id'],
                        'goods_id' => $Products['goods_id']
                    );

                    $su_goodsObj->save($supplier_goods);

                }
            }
        }

        if (kernel::single('taoguaniostockorder_iostockorder')->check_iostockorder($_POST,$msg)){
            $this->end(true, $label.'完成');
        }else {
            //$msg['delivery_bn'] = $dly['delivery_bn'];
            $this->end(false, $label.'失败',  'index.php?app=taoguaniostockorder&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io, array('msg'=>$msg));
        }
    }
    #导出模板
    function exportTemplate($p){
        if($p){
            #入库
            $name='RK';
        }else{
            #出库
            $name ='CK';
        }
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".$name.date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $obj_iso = &app::get('taoguaniostockorder')->model('iso');
         //仓储-入库管理-其它入库-导出模板-操作日志
        $logParams = array(
            'app' => $this->app->app_id,
            'ctl' => trim($_GET['ctl']),
            'act' => trim($_GET['act']),
            'modelFullName' => '',
            'type' => 'export',
            'params' => array(),
        );
        ome_operation_log::insert('warehouse_other_template_export', $logParams);
        
         $title1 = $obj_iso->exportTemplate($p);
         $title2 = $obj_iso->exportTemplate('item');
         echo '"'.implode('","',$title1).'"';
         echo "\n\n";
         echo '"'.implode('","',$title2).'"'; 
    }
}
?>