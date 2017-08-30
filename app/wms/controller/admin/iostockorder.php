<?php
class wms_ctl_admin_iostockorder extends desktop_controller{
    var $name = "出入库管理";
    var $workground = "wms_center";

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
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        #if (!$is_super){
            #$branch_ids = $oBranch->getBranchByUser(true);
        if ($branch_ids){
            $oIso = &app::get('taoguaniostockorder')->model("iso");
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
        #}

        $params['base_filter']['type_id'] = $type;
        $params['base_filter']['confirm'] = 'N';
        $params['base_filter']['check_status'] = '2';
        $this->finder('taoguaniostockorder_mdl_iso', $params);
    }
    /**
     *
     * 其他入库列表
     */
    function other_iostock(){
        $io = $_GET['io'];
        if($io){
            $title = '其他入库';
            $this->name = "入库管理";
        }else{
            $title = '其他出库';
            $this->name = "出库管理";
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
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        #if (!$is_super){
            #$branch_ids = $oBranch->getBranchByUser(true);
        if ($branch_ids){
            $oIso = &app::get('taoguaniostockorder')->model("iso");
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
        #}

        $params['base_filter']['type_id'] = kernel::single('wms_iostockorder')->get_create_iso_type($io,true);
        $params['base_filter']['confirm'] = 'N';
        $params['base_filter']['check_status'] = '2';
        $this->finder('taoguaniostockorder_mdl_iso', $params);
    }



    function iostockorder_confirm($iso_id,$io){
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        $oIsoItems = &app::get('taoguaniostockorder')->model("iso_items");
        $oProducts  = &app::get('ome')->model("products");
        $oProduct_pos = &app::get('ome')->model("branch_product_pos");
        $count = count($oIsoItems->getList('*',array('iso_id'=>$iso_id), 0, -1));
        $iso_items = $oIsoItems->getList('*',array('iso_id'=>$iso_id));
        $iso = $oIso->dump($iso_id,'branch_id,supplier_id,type_id');
        foreach($iso_items as $k=>$v){
            $product = $oProducts->dump($v['product_id'],'unit,barcode');
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
        $this->pagedata['count']=$count;
        $this->pagedata['branch_id']=$iso['branch_id'];
        $this->pagedata['type_id'] = $iso['type_id'];
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
        $this->begin('index.php?app=wms&ctl=admin_iostockorder');
        $oIsoItems = &app::get('taoguaniostockorder')->model("iso_items");
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        $oBranch_pos = app::get('ome')->model("branch_pos");
        $oProduct_pos = app::get('ome')->model("branch_product_pos");
        $entry_num = $_POST['entry_num'];
        $iso_id = $_POST['iso_id'];
        $ids = $_POST['ids'];
        $branch_id = $_POST['branch_id'];
        $Iso = $oIso->dump(array('iso_id'=>$iso_id),'confirm,type_id');
        if ($Iso['type_id']=='5' || $Iso['type_id']=='50'){
            $branch_detail = kernel::single('wms_iostockdata')->getBranchByid($branch_id);
            if ($branch_detail['type']!='damaged'){
                $this->end(false, '出入库类型为残损入库，仓库必须为残仓');
            }
        }
        $io = $_POST['io'];

        if($io){
            $label = '入库';
        }else{
            $label = '出库';
        }
        if($Iso['confirm']=='Y'){
            $this->end(false, '此单据已确认!', 'index.php?app=wms&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
        }
        if (empty($ids)){
            $this->end(false, '请选择需要'.$label.'的商品', 'index.php?app=wms&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
        }

        $ret = array();
        $error_bn = array();
        $oBranchProduct = &app::get('ome')->model('branch_product');
        foreach ($ids as $k=>$id) {
            if ($entry_num[$id] <= 0){
                $this->end(false, ''.$label.'量必须大于0', 'index.php?app=wms&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
            }

            if($io == '0'){
                $aRow = $oBranchProduct->dump(array('product_id'=>$_POST['product_ids'][$id], 'branch_id'=>$_POST['branch_id']),'store,store_freeze');
                if($entry_num[$id] > ($aRow['store'])){
                    $this->end(false, '出库数量不可大于库存数量.');
                }
            }

        }
         $iso_items = $oIsoItems->getList('*',array('iso_id'=>$iso_id));
         foreach($iso_items as $ik=>$iv){
             if(app::get('taoguaninventory')->is_installed()){
                 $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($iv['product_id'],$branch_id);

                if(!$check_inventory){
                    $this->end(false, '此商品正在盘点中，不可以出入库操作!', 'index.php?app=wms&ctl=admin_iostockorder&act=iostockorder_confirm&p[0]='.$iso_id.'&p[1]='.$io);
                }
             }
         }

        //事件触发，通知oms出入库
        $type_id = $_POST['type_id'];
        if($io){#入
            kernel::single('wms_event_trigger_otherinstorage')->inStorage(array('iso_id'=>$iso_id), true);
        }else{
            kernel::single('wms_event_trigger_otheroutstorage')->outStorage(array('iso_id'=>$iso_id), true);
        }



        $this->end(true, $label.'完成');
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
                break;
            case '0':
                $this->base_filter = array();
                $this->title = '出库单查询';
                $confirm_label = '出库单确认';
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
           'use_buildin_export'=>true,
           'use_buildin_import'=>false,
           'use_buildin_filter'=>true,
           'finder_cols'=>'name,iso_bn,oper,operator,original_bn,create_time,type_id',
           //'finder_aliasname'=>$finder_aliasname,
           //'finder_cols'=>$finder_cols,
        ));
    }
}
?>