<?php
class console_ctl_admin_iostockorder extends desktop_controller{
    var $name = "出入库计划";
    var $workground = "console_purchasecenter";

    /**
     *
     * 其他入库列表
     */
    function other_iostock(){
        $io = $_GET['io'];
        if($io){
            $title = '入库单';
        }else{
            $title = '出库单';
        }

        $params = array(
           'actions' => array(
                array(
                    'label'=>'新建',
                    'href'=>'index.php?app=console&ctl=admin_iostockorder&act=iostock_add&p[0]=other&p[1]='.$io,
                    'target'=>'_blank'
                ),
               array('label'=>app::get('taoguaniostockorder')->_('导出模板'),'href'=>'index.php?app=console&ctl=admin_iostockorder&act=exportTemplate&p[1]='.$io,'target'=>'_blank'),
                array('label' => '推送单据至WMS',
                            'submit' => 'index.php?app=console&ctl=admin_iostockorder&act=batch_sync&io='.$io, 
                            'confirm' => '你确定要对勾选的单子发送至仓储吗？', 
                            'target' => 'refresh'),
            ),
            'title'=>$title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            //'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
            'finder_cols'=>'column_edit,column_confirm,name,iso_bn,oper,operator,original_bn,create_time,type_id',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $oIso = &app::get('taoguaniostockorder')->model('iso');
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

        $iostock_type = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type($io,true);

        if ($_POST['type_id']&& in_array($_POST['type_id'],$iostock_type) ) {
            $params['base_filter']['type_id'] = $_POST['type_id'];
            
        }else{
            $params['base_filter']['type_id'] = $iostock_type;
        }
        
        #$params['base_filter']['confirm'] = 'N';
        //$this->workground = "console_purchasecenter";
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
        $actions = array();
        $actions[] = array('label' => '推送单据至WMS',
                            'submit' => 'index.php?app=console&ctl=admin_iostockorder&act=batch_sync&io='.$io, 
                            'confirm' => '你确定要对勾选的单子发送至仓储吗？', 
                            'target' => 'refresh');
        $params = array(
            'title'=>$title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'actions'=>$actions,
            'finder_cols'=>'column_edit,column_confirm,name,iso_bn,oper,operator,original_bn,create_time,type_id',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $oIso = &app::get('taoguaniostockorder')->model('iso');
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
		//$params['base_filter']['iso_status'] = array('1','2');
        #$params['base_filter']['confirm'] = 'N';
        $this->workground = "console_purchasecenter";
        $this->finder('taoguaniostockorder_mdl_iso', $params);
    }



    function iostock_add($type,$io){
        if($io){
            $order_label = '入库单';
        }else{
            $order_label = '出库单';
            $oDly_corp = app::get('ome')->model('dly_corp');
            
            $dly_corp = $oDly_corp->getlist('*',array('disabled'=>'false'));
            $this->pagedata['dly_corp'] = $dly_corp;
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
        $this->pagedata['iostock_types'] = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type($io);
        #外部仓库列表
        $oExtrabranch = &app::get('ome')->model('extrabranch');
        $extrabranch = $oExtrabranch->getlist('branch_id,name','',0,-1);
        $this->pagedata['extrabranch'] = $extrabranch;
        if($io){
             $this->singlepage("admin/iostock/instock_add.html");
        }else{
             $this->singlepage("admin/iostock/outstock_add.html");
        }
    }

    function iostock_edit($iso_id,$io,$act){
        $order_label = $io ? '入库单' : '出库单';

        //获取出入库单信息
        $isoObj = &app::get('taoguaniostockorder')->model('iso');
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
        #外部仓库列表
        $oExtrabranch = &app::get('ome')->model('extrabranch');
        $extrabranch = $oExtrabranch->getlist('branch_id,name','',0,-1);
        $this->pagedata['extrabranch'] = $extrabranch;
        #
        $oDly_corp = app::get('ome')->model('dly_corp');
        $dly_corp = $oDly_corp->getlist('*',array('disabled'=>'false'));
        $this->pagedata['dly_corp'] = $dly_corp;
        $this->pagedata['io'] = $io;
        $this->pagedata['act'] = $act;
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
        $isoItemObj = &app::get('taoguaniostockorder')->model('iso_items');
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

        $this->begin('index.php?app=console&ctl=admin_iostockorder&act='.$_POST['io_act'].'&io='.$_POST['io']);
        $data = $_POST;
        $data['old_items'] = explode('-',$data['old_items']);
        $pStockObj = kernel::single('console_stock_products');

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

            if($data['io'] == '0'){
                $usable_store = $pStockObj->get_branch_usable_store($data['branch'],$product_id);
                #$aRow = $branchProductObj->dump(array('product_id'=>$product_id, 'branch_id'=>$data['branch']),'store');
                if($data['at'][$product_id] > $usable_store){
                    $this->end(false, '货号：'.$bn.'出库数不可大于库存数'.$usable_store);
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
            'extrabranch_id'=>$data['extrabranch_id']
        );
        if ($data['corp_id']) {
            $iostockorder_data['corp_id'] = $data['corp_id'];
        }
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
        $this->begin("index.php?app=console&ctl=admin_iostockorder");

        $_POST['iso_price'] = $_POST['iso_price'] ? $_POST['iso_price'] : 0;
        $oBranchProduct = &app::get('ome')->model('branch_product');
        $productObj = &app::get('ome')->model('products');
        $pStockObj = kernel::single('console_stock_products');
        if(!$_POST['bn']) {
            $this->end(false, '请先选择入库商品！.');
        }
        //判断类型是否是残损
        $branch_id = $_POST['branch'];
        $branch_detail = kernel::single('console_iostockdata')->getBranchByid($branch_id);
        if ($_POST['type_id'] == '50' || $_POST['type_id'] == '5'){

            if ($branch_detail['type']!='damaged'){
                $this->end(false, '出入库类型为残损出入库，仓库必须为残仓!');
            }
        }else{
             if ($branch_detail['type']=='damaged'){
                $this->end(false, '出入库类型不为残损出入库,不可以选择残仓!');
            }
        }
        $products = array();
        foreach($_POST['bn'] as $product_id=>$bn){
            if($_POST['at'][$product_id] == 0) {
                $this->end(false, '库存数量不能为0.');
            }

            if($_POST['io'] == '0'){
                #$aRow = $oBranchProduct->dump(array('product_id'=>$product_id, 'branch_id'=>$_POST['branch']),'store');
                $usable_store = $pStockObj->get_branch_usable_store($_POST['branch'],$product_id);

                if($_POST['at'][$product_id] > $usable_store){
                    $pInfo = array();
                    $pInfo = $productObj->dump($product_id,'name,bn');
                    $this->end(false, '货号：'.$pInfo['bn'].'出库数不可大于库存数'.$usable_store);
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
        $iso_id = kernel::single('console_iostockorder')->save_iostockorder($_POST,$msg);

        if ($iso_id){


            $this->end(true, '保存完成');
        }else {

            $this->end(false, '保存失败', '', array('msg'=>$msg));
        }
    }

    function getProductStore(){
        $product_id=$_POST['pid'];
        $branch_id=$_POST['bid'];
        $pStockObj = kernel::single('console_stock_products');

        if($product_id>0 && $branch_id>0){
            $usable_store = $pStockObj->get_branch_usable_store($branch_id,$product_id);
            #$branchProductObj = &app::get('ome')->model('branch_product');
            #$product = $branchProductObj->dump(array('product_id'=>$product_id, 'branch_id'=>$branch_id),'store');
            echo json_encode(array('result' => 'true', 'store' => $usable_store));
        }
    }
    /**
    * 出入库单残损确认
    */
    function doDefective($iso_id,$io){

        $iso_itemsObj = &app::get('taoguaniostockorder')->model('iso_items');
        $iso_items = $iso_itemsObj->getlist('*',array('iso_id'=>$iso_id,'defective_num|than'=>'0'),0,-1);
        $iso = array();
        $iso['iso_id'] = $iso_id;
        $iso['iso_items'] = $iso_items;
        $this->pagedata['iso'] = $iso;
        $this->singlepage('admin/iostock/stock_defective.html');
    }

    /**
    * 残损确认
    */
    function doDefectiveconfirm(){
        $this->begin("index.php?app=console&ctl=admin_iostockorder");
        $iso_id = intval($_POST['iso_id']);
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        $iostockObj = kernel::single('console_iostockdata');
        $iso = $oIso->dump(array('iso_id'=>$iso_id),'branch_id,iso_bn,type_id,iso_id,supplier_id,supplier_name,cost_tax,oper,create_time,operator,defective_status');
        if ($iso['defective_status']!='1'){
            $this->end(false,'此单据已确认或无需确认!');
        }
        if (!in_array($iso['type_id'],array('5','50')) ) {
            $damagedbranch = $iostockObj->getDamagedbranch( $iso['branch_id'] );
            if( empty($damagedbranch) ){
                $this->end(false,$item['bn'].'有不良品，但未设置主仓对应的残仓');
            }
            $branch_id = $damagedbranch['branch_id'];
        }else{
            $branch_id = $iso['branch_id'];
        }
        
        $io = $_POST['io'];
        #查询是否有不良品
        #
        $iostock_data = array(
                'type_id'=>'50',
                'branch_id'=>$branch_id,
                'iso_bn'=>$iso['iso_bn'],
                'iso_id'=>$iso['iso_id'],
                'supplier_id'=>$iso['supplier_id'],
                'supplier_name'=>$iso['supplier_name'],
                'cost_tax'=>$iso['cost_tax'],
                'oper'=>$iso['oper'],
                'create_time'=>$iso['create_time'],
        );
        $iso_data = $iostockObj->get_iostockData($iso_id);

        $items_data = array();
        foreach($iso_data['items'] as $item){
            if($item['defective_num'] > 0 ){
                $items[] = array(
                    'bn'=>$item['bn'],
                    'nums'=>$item['defective_num'],
                    'price'=>$item['price'],
                    'iso_items_id'=>$item['iso_items_id']
                );
            }

        }
        if (count($items)>0){
            $iostock_data['items'] = $items;
            $result = kernel::single('console_iostockorder')->confirm_iostockorder($iostock_data,'50',$msg);
            if($result){
                #更新确认状态
                $io_update_data = array(
                    'defective_status'=>'2',
                );
                $oIso->update($io_update_data,array('iso_id'=>$iso_id));
                $this->end(true,'成功');
            }else{
                $this->end(false,'残损确认失败!');
            }

        }else{
            $this->end(false,'没有可确认的货品');
        }



    }


    /**
    * 差异查看确认
    */
    function difference($iso_id,$io){

        $isoObj = &app::get('taoguaniostockorder')->model('iso');
        $suObj = &app::get('purchase')->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $iso = $isoObj->dump($iso_id,'*');
        $stockObj = kernel::single('console_receipt_stock');
        $iso['iso_items'] = $stockObj->difference_stock($iso['iso_bn']);
        $total_num=0;
        if ($iso['iso_items'])
        foreach($iso['iso_items'] as $k=>$v){
            $total_num+=$v['nums'];
        }
        $su = $suObj->dump($iso['supplier_id'],'name');
        $br = $brObj->dump($iso['branch_id'], 'name');
        $iso['iso_id']   = $iso_id;
        $iso['branch_name']   = $br['name'];
        $iso['supplier_name'] = $su['name'];
        $iso['create_time'] = date("Y-m-d", $iso['create_time']);
        $iso['total_num']     = $total_num;
        $iso['memo'] = $iso['memo'];
        $this->pagedata['iso'] = $iso;
        $this->pagedata['io'] = $io;

        $this->singlepage('admin/iostock/stock_difference.html');
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

         $title1 = $obj_iso->exportTemplate($p);
         $title2 = $obj_iso->exportTemplate('item');
         echo '"'.implode('","',$title1).'"';
         echo "\n\n";
         echo '"'.implode('","',$title2).'"';
    }

    /**
    * 审核出入库单据
    *
    */
    public function check($iso_id,$io,$act){
        $isoObj = &app::get('taoguaniostockorder')->model('iso');
        $suObj = &app::get('purchase')->model('supplier');
        $oExtrabranch = app::get('ome')->model('extrabranch');

        $brObj = &app::get('ome')->model('branch');
        $iso = $isoObj->dump($iso_id,'*',array('iso_items'=>array('*')));
        $extrabranch_id = $iso['extrabranch_id'];
        $extrabranch = $oExtrabranch->dump($extrabranch_id,'name');

        $total_num=0;
        if ($iso['iso_items'])
        foreach($iso['iso_items'] as $k=>$v){
            $total_num+=$v['nums'];
        }
        $su = $suObj->dump($iso['supplier_id'],'name');
        $br = $brObj->dump($iso['branch_id'], 'name');
        $iso['iso_id']   = $iso_id;
        $iso['branch_name']   = $br['name'];
        $iso['supplier_name'] = $su['name'];
        $iso['create_time'] = date("Y-m-d", $iso['create_time']);
        $iso['total_num']     = $total_num;
        $iso['memo'] = $iso['memo'];
        $iso['extrabranch_name'] = $extrabranch['name'];
        $this->pagedata['iso'] = $iso;
        $this->pagedata['io'] = $io;
        $this->pagedata['act'] = $act;
        $this->pagedata['amount'] = $iso['product_cost'] + $iso['iso_price'];
        $this->singlepage('admin/iostock/stock_check.html');

    }

    /**
    * 保存出入库审核单据
    *
    */
    public function doCheck(){
        $this->begin('index.php?app=console&ctl=admin_iostockorder&act='.$_POST['io_act'].'&io='.$_POST['io']);
        #更新单据审核状态
        $iso_id = intval( $_POST['iso_id'] );
        $io = $_POST['io'];
        $pStockObj = kernel::single('console_stock_products');
        $isoObj = &app::get('taoguaniostockorder')->model('iso');

        #库存状态判断\
        $iso = $isoObj->dump($iso_id,'check_status,branch_id,iso_bn');
        $branch_id = $iso['branch_id'];
        if ($iso['check_status']!='1'){
            $this->end(false,'此单据已审核!');
        }

        if ($io == '0'){
            $oIso_items = &app::get('taoguaniostockorder')->model('iso_items');
            #需要判断可用库存是否足够
            $iso_items = $oIso_items->getlist('bn,nums,product_id',array('iso_id'=>$iso_id),0,-1);

            foreach($iso_items as $ik=>$iv){
                //判断选择商品库存是否充足
                $usable_store = $pStockObj->get_branch_usable_store($branch_id,$iv['product_id']);

                if($iv['nums'] > $usable_store){
                    $this->end(false, $iv['bn'].'出库数量不可大于库存数量.'.$usable_store);
                }
            }

        }
        $iso_data = array('check_status'=>'2');
        $result = $isoObj->update($iso_data,array('iso_id'=>$iso_id));
        if ($result){
            if ($io == '0'){
                #将库存冻结
                kernel::single('console_receipt_stock')->clear_stockout_store_freeze(array('iso_bn'=>$iso['iso_bn']),'+');
                #出库
                kernel::single('console_event_trigger_otherstockout')->create(array('iso_id'=>$iso_id),false);
            }else{
                #入库
                kernel::single('console_event_trigger_otherstockin')->create(array('iso_id'=>$iso_id),false);
            }


            $this->end(true,'审核成功');
        }else{

            $this->end(false, '审核失败');
        }

    }

    /**
    * 取消单据
    */
    function cancel($iso_id,$io,$type){
        $isoObj = &app::get('taoguaniostockorder')->model('iso');

        #库存状态判断
        $iso = $isoObj->dump($iso_id,'iso_bn,iso_id,type_id');
        $title = '';
        switch($iso['type_id']){
            case '4':
            case '40':
                $io_type = 'ALLCOATE';
                $title.='调拔单';
            break;
            case '5':
            case '50':
                $io_type = 'DEFECTIVE';
                $title.='残损';
            break;
            case '7':
            case '70':
                $io_type = 'DIRECT';
                $title.='直接';
            break;
            default:
                $io_type = 'OTHER';
                $title.='其它';
            break;

        }
        if ($io){
            $method = 'otherstockin';
            $title.='入库';
        }else{
            $method = 'otherstockout';
            $title.='出库';
        }

        $this->pagedata['iso'] = $iso;
        $this->pagedata['io'] = $io;
        $this->pagedata['type'] = $type;
        $this->pagedata['title'] = $title;
        unset($iso);
        $this->display("admin/iostock/stock_cancel.html");
    }


    /**
    * 执行取消出入库
    */
    function doCancel(){
        $this->begin('index.php?app=console&ctl=admin_iostockorder&act='.$_POST['type'].'&io='.$_POST['io']);
        $type = $_POST['type'];
        $iso_id = $_POST['iso_id'];
        $isoObj = &app::get('taoguaniostockorder')->model('iso');
        $stockObj = kernel::single('console_receipt_stock');
        $iso = $isoObj->dump($iso_id,'iso_bn,iso_id,type_id,branch_id,iso_status,check_status');
        if ($iso['iso_status']>1){
            $this->end(false,'取消失败!');
        }else{
            $isoObj->update(array('iso_status'=>4),array('iso_id'=>$iso_id));
            #如果是已审核，取消冻结库存
            if ($iso['check_status'] == '2' && $_POST['io']=='0') {
                $stockObj->clear_stockout_store_freeze(array('iso_bn'=>$iso['iso_bn']),'-');
            }
            $this->end(true,'成功');
        }

    }

    /**
    * 确认是否可以取消
    */
    function checkCancel($iso_id){

        $isoObj = &app::get('taoguaniostockorder')->model('iso');
        //$iso = $isoObj->dump($iso_id,'iso_bn,iso_id,type_id,branch_id,check_status,iso_status,out_iso_bn');
        $iso = $isoObj->dump($iso_id,'iso_bn,iso_id,type_id,branch_id,check_status,iso_status,out_iso_bn');
        $iso_status = $iso['iso_status'];
        $io = $_GET['io'];
        if ($iso['check_status'] == '2'){
            if ($iso_status>1) {
                $result = array('rsp'=>'fail','err_msg'=>'单据所在状态不允许此次操作');
            }else{
                switch($iso['type_id']){
                    case '4':
                    case '40':
                        $io_type = 'ALLCOATE';
                    break;
                    case '5':
                    case '50':
                        $io_type = 'DEFECTIVE';
                    break;
                    case '7':
                    case '70':
                        $io_type = 'DIRECT';
                    break;
                    default:
                        $io_type = 'OTHER';
                    break;
                }
                if ($io){
                    $method = 'otherstockin';
                }else{
                    $method = 'otherstockout';
                }
                $branch_id = $iso['branch_id'];
                $data = array(
                    'io_type'=>$io_type,
                    'io_bn'=>$iso['iso_bn'],
                    'out_iso_bn'=>$iso['out_iso_bn'],
                    'branch_id'=>$branch_id
                );

                $result = kernel::single('console_event_trigger_'.$method)->cancel($data, true);
            }

        }else{
            $result = array('rsp'=>'succ');
        }

        echo json_encode($result);

    }

	
	/**
	 * 调拔入库列表
	 * 
	 * @access  public
	 * @author cyyr24@sina.cn
	 */
	function allocate_iostocklist()
	{
	    $io = $_GET['io'];
        $iostock_instance = kernel::service('ome.iostock');

        if($io){
            $title = '调拨入库列表';
            eval('$type='.get_class($iostock_instance).'::ALLOC_STORAGE;');
        }else{
            $title = '调拨出库列表';
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
            'finder_cols'=>'name,iso_bn,oper,operator,original_bn,create_time,type_id',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $oIso = &app::get('taoguaniostockorder')->model('iso');
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
        
        $this->workground = "console_center";
        $this->finder('taoguaniostockorder_mdl_iso', $params);
	} // end func

    
    /**
     *更多
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function more_items($iso_id)
    {
        $isoObj  = &app::get('taoguaniostockorder')->model('iso');
        $productObj   = &app::get('ome')->model('products');
        $iso = $isoObj->dump($iso_id,'iso_id',array('iso_items'=>array('*')));
        foreach($iso['iso_items'] as $k=>$order_item){
            $product = $productObj->dump($order_item['product_id'],'spec_info,barcode');
            $order_item['spec_info'] = $product['spec_info'];
            $order_item['barcode'] = $product['barcode'];
            $iso['iso_items'][$k] = $order_item;
        }
        $finder_id = $_GET['_finder']['finder_id'];
        $appr_id = $_GET['apprid'];
        $render = app::get('console')->render();
        $pObj = &app::get('ome')->model('products');
        $itemObj = &app::get('console')->model('stockdump_items');
        $omeObj = &app::get('ome')->render();
        $page = $_GET['page'] ? $_GET['page'] : 1;
        $pagelimit = 10;
        $offset = ($page-1)*$pagelimit;
        $sql = "SELECT COUNT(*) FROM `sdb_console_stockdump_items` WHERE stockdump_id =".$appr_id;
        $tmp = kernel::database()->select($sql);
        $items = $itemObj->getList('*',array('stockdump_id'=>$appr_id),$offset,$pagelimit);
        $count = $tmp[0]['COUNT(*)'];
        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>'index.php?app=console&ctl=admin_stockdump&act=more_items&apprid='.$appr_id.'&page=%d',
        ));
        
        if ($items)
        foreach ($items as $key => $item){
            //将商品的显示名称改为后台的显示名称
            $product_name = $pObj->getList('name,spec_info,unit',array('bn'=>$items[$key]['bn']));
            $items[$key]['product_name'] = $product_name[0]['name'];
            $items[$key]['spec_info'] = $product_name[0]['spec_info'];
            $items[$key]['unit'] = $product_name[0]['unit'];
        
        }

        $render->pagedata['items'] = $items;
        $render->pagedata['pager'] = $pager;
        $this->singlepage('admin/stockdump/stockdump_more_item.html');
    }

    /**
     * 单据发送至第三方.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batch_sync()
    {
        $this->begin('');
        kernel::database()->exec('commit');
        $isoObj  = &app::get('taoguaniostockorder')->model('iso');
        $ids = $_POST['iso_id'];
        $isoList = $isoObj->getList('iso_id',array('iso_id'=>$ids,'check_status'=>'2','iso_status'=>array('1')));

        $io = $_GET['io'];
        foreach ($isoList  as $iso ) {
            $iso_id = $iso['iso_id'];

            if ($io == '0'){
             
                kernel::single('console_event_trigger_otherstockout')->create(array('iso_id'=>$iso_id),false);
            }else{
                #入库
                kernel::single('console_event_trigger_otherstockin')->create(array('iso_id'=>$iso_id),false);
            }
        }
        
        $this->end(true, '命令已经被成功发送！！');
    }
}
?>