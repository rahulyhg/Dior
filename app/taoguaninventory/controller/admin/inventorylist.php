<?php
class taoguaninventory_ctl_admin_inventorylist extends desktop_controller{

    var $name = "盘点表管理";
    var $workground = "storage_center";

    function _views(){
        $sub_menu = $this->_views_pd();
        return $sub_menu;
    }
    function _views_pd(){
        $mdl_inventory = app::get('taoguaninventory')->model("inventory");
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
            1 => array('label'=>app::get('base')->_('待确认'),'filter'=>array('confirm_status' =>array(1,4)),'optional'=>false),
            2 => array(
                'label'=>app::get('base')->_('已确认'),
                'filter'=>array('confirm_status' =>'2'),
                'optional'=>false),
            3 => array(
                'label'=>app::get('base')->_('已作废'),
                'filter'=>array('confirm_status' =>'3'),
                'optional'=>false),
            );


        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = $v['filter'];
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_inventory->count($v['filter']);
            //$sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
            $sub_menu[$k]['href'] = 'index.php?app=taoguaninventory&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&flt='.$_GET['flt'].'&view='.$i++;
        }
        return $sub_menu;
    }
    /*
     *
     */
    function index(){
        switch ($_GET['flt']) {
            case 'list':
                $this->title = '盘点列表';
                    $this->action = array(
                        array('label' =>'新建', 'href' => 'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=inventory_selectbranch'),
                        array('label' =>'模板导出', 'href' => 'index.php?app=taoguaninventory&ctl=admin_inventory&act=export', 'target' => 'dialog::{width:700,height:400,title:\'模板导出\'}'),
                         array('label' =>'盘点导入', 'href' => 'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=import', 'target' => 'dialog::{width:700,height:400,title:\'盘点导入\'}'),
                         array('label'=>app::get('desktop')->_('作废'),'icon'=>'add.gif','confirm'=>app::get('desktop')->_('确定作废选中项？作废后将不可恢复'),'submit'=>'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=batch_cancel'),
                        array('label'=>app::get('desktop')->_('删除'),'icon'=>'add.gif','confirm'=>app::get('desktop')->_('确定删除选中项？删除后将不可恢复'),'submit'=>'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=batch_delete'),
                        );
                break;
            case 'confirm':
                $this->title = '盘点确认';
                $this->action=array();
                break;
        }
        $params = array(
                        'title'=>$this->title,
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        //'use_buildin_import'=>true,
                        'use_buildin_filter'=>true,
						                  'orderBy'=>'inventory_id DESC',
                        'actions'=>$this->action,
                    );
          /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $params['base_filter']['branch_id'] = $branch_ids;
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }
           # 在列表上方添加搜索
//        if(!isset($_GET['action'])) {
//            $panel = new desktop_panel($this);
//
//            $panel->setId('inventory_finder_top');
//            $panel->setTmpl('admin/finder/finder_panel_filter.html');
//
//            $panel->show('taoguaninventory_mdl_inventory', $params);
//
//        }

        $this->finder('taoguaninventory_mdl_inventory', $params);
    }



    /*
     * 盘点明细
     */
    function detail_inventory($inventory_id=null, $page=1){
        set_time_limit(0);
        $inventory_id = intval($inventory_id);
        $is_auto = $_GET['is_auto'];

        $page = intval($page);
        $page = $page ? $page : 1;
        $pagelimit = 10;
        $oInventory = $this->app->model('inventory');
        $oInventory_items = $this->app->model('inventory_items');
        $inventory_detail = $oInventory->dump($inventory_id, '*');

        $shortage_over = $_GET['shortage_over'];
        if(($is_auto=='0') || ($is_auto=='1')){

            $filter = array('inventory_id'=>$inventory_id,'is_auto'=>$is_auto);
            $total = $oInventory->getInventoryTotal($inventory_id,$is_auto,$shortage_over);
        }else{
            $filter = array('inventory_id'=>$inventory_id);
            $total = $oInventory->getInventoryTotal($inventory_id,'',$shortage_over);
        }
        if($_GET['shortage_over']==1){
            $show_shortage_over = $_GET['shortage_over'];
            $filter['shortage_over|noequal']=0;

        }

        //盘点明细

        $inventory_items = $oInventory_items->getList('*', $filter, $pagelimit*($page-1), $pagelimit,'is_auto ASC,bn DESC');
        $branch_id =  $inventory_detail['branch_id'];

        $total_price = 0;
        #盈亏总金额
        $total_shortage_over_price = 0;
        if ($inventory_items)
        foreach ($inventory_items as $k=>$v){
            #成本价
            $price = kernel::single('taoguaninventory_inventorylist')->get_price($v['product_id'],$branch_id);
            $inventory_items[$k]['price'] = $price;
            #盈亏数量 =实际数量-账面数量
            $shortage_over = $v['actual_num']-$v['accounts_num'];
            #盈亏金额
            $shortage_over_price = $price * $shortage_over;
            $inventory_items[$k]['shortage_over_price'] = $shortage_over_price;
            $total_price += $price;
            $total_shortage_over_price  += $shortage_over_price;
            
             //小计
             $subtotal['accounts_num'] += $v['accounts_num'];
             $subtotal['actual_num'] += $v['actual_num'];
             $subtotal['shortage_over'] += $v['shortage_over'];
        }
        $total[0]['price'] = $total_price;
        $total[0]['shortage_over_price'] = $total_shortage_over_price;
        $count = $total['count'];

        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=detail_inventory&p[0]='.$inventory_id.'&p[1]=%d&view='.$_GET['view'].'&from='.$_GET['from'].'&is_auto='.$is_auto.'&shortage_over='.$shortage_over,
        ));
        $this->pagedata['pager'] = $pager;
        $this->pagedata['detail'] = $inventory_detail;
        $this->pagedata['items'] = $inventory_items;
        $this->pagedata['inventory_id'] = $inventory_id;
        $this->pagedata['subtotal'] = $subtotal;#小计
        $this->pagedata['total'] = $total[0];#总计
        $this->pagedata['is_auto'] = $is_auto;
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['view'] = $_GET['view'];
        $this->page("admin/inventory/detail_inventory.html");
    }

    function add_inventory(){
        $branch_id = $_POST['branch_id'];

        if(!$branch_id){
            $this->begin("index.php?app=taoguaninventory&ctl=admin_inventorylist&act=inventory_selectbranch");
            $this->end(false,'请选择仓库');
        }
        $brObj = &app::get('ome')->model('branch');

        $branch_product = kernel::single('taoguaninventory_inventorylist')->check_product_iostock($branch_id);

        $mdl_Inventory = $this->app->model('inventory');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch = $brObj->dump(array('branch_id'=>$branch_id),'name,branch_id');
        $inventory_list = $mdl_Inventory->getlist('inventory_name,inventory_date,op_name,inventory_id',array('confirm_status'=>'1','branch_id'=>$branch_id),0,-1);

        $this->pagedata['branch_product'] = $branch_product;
        $this->pagedata['date']         = date("Y-m-d");
        $this->pagedata['inventory_name'] = date('Ymd').$branch['name'];
        $this->pagedata['op_name'] =  kernel::single('desktop_user')->get_name();
        $this->pagedata['branch'] = $branch;
        $this->pagedata['inventory_list'] = $inventory_list;
        $this->page("admin/inventory/addonline.html");
    }

    function addtoInventory(){
        $this->begin();
        $branch_id = $_POST['branch_id'];
        $brObj = &app::get('ome')->model('branch');
        $branch = $brObj->dump($branch_id,'name,branch_id');
        $inventory_name = $_POST['inventory_name'];
        /*新建盘点计划主表*/
        $invObj = $this->app->model('inventory');
        $inventory = $invObj->dump(array('inventory_name'=>$inventory_name),'inventory_id');
        $inventory_data = $_POST;
        $inventory_data['branch_name']=$branch['name'];
        if($inventory_data['inventory_type']=='2'){
            $inv_exist = $invObj->dump(array('branch_id'=>$branch_id,'inventory_type'=>'2','confirm_status'=>'1'),'inventory_id');

            if($inv_exist){
                $this->end(false,'此仓库已有全盘的盘点单存在');
            }
            $inv_exist1 = $invObj->dump(array('branch_id'=>$branch_id,'inventory_type'=>3,'confirm_status'=>1),'inventory_id');
             if($inv_exist1){
                $this->end(false,'请将部分盘点确认后再新建全盘');
            }
        }
        if($inventory_data['inventory_type']=='1' || $inventory_data['inventory_type']=='3'){
             $inv_exist2 = $invObj->dump(array('branch_id'=>$branch_id,'inventory_type'=>'2','confirm_status'=>'1'),'inventory_id');
             if($inv_exist2){
                $this->end(false,'请将此仓库全盘确认后再新建部分盘点');
            }
        }
        if($inventory_data['inventory_type']=='4'){
            $branch_product = kernel::single('taoguaninventory_inventorylist')->check_product_iostock($branch_id);
            if($branch_product){

                   $this->end(false,'此仓库已存在进出库商品不可以期初盘点');
                    return false;
                }
                $branch_inventory = kernel::single('taoguaninventory_inventorylist')->get_inventorybybranch_id($branch_id);
                if($branch_inventory){
                    $this->end(false,'此仓库已有类型为期初的盘点单存在!');

                    return false;
                }

        }
        if($inventory){
            $this->end(false,'此盘点名称已存在,您可以选择加入,或者修改盘点名称');
        }
        $inventory_id = kernel::single('taoguaninventory_inventorylist')->create_inventory($inventory_data,$msg);

        if($inventory_id){
            $this->end(true, '跳转中。.', 'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=go_inventory&inventory_id='.$inventory_id);
         }else{
            $this->end(false, $msg, 'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=add_invertory');
         }
    }
    /**
    *盘点加入
    */
    function go_inventory(){
        set_time_limit(0);
        $invObj = $this->app->model('inventory');
        $oInventory_items = $this->app->model('inventory_items');
        $inventory_id = $_GET['inventory_id'];
        $page = intval($_GET['page']);
        $page = $page ? $page : 1;
        $pagelimit = 3;
        $op_name = kernel::single('desktop_user')->get_name();
        $inventory = $invObj->dump($inventory_id,'inventory_name,branch_id,branch_name,pos,inventory_id,memo,op_name,add_time,inventory_type');
        $refresh = kernel::single('taoguaninventory_inventorylist')->refresh_shortage_over($inventory_id,$inventory['branch_id']);

        $this->pagedata['inventory'] = $inventory;

        $this->pagedata['pos'] = $inventory['pos'];

        $this->pagedata['date']         = date("Y年m月d日");
        //盘点明细
        $inventory_items = $oInventory_items->getList('*', array('inventory_id'=>$inventory_id,'is_auto'=>'0'), $pagelimit*($page-1), $pagelimit,'oper_time desc');
        //总计
        $total = $invObj->getInventoryTotal($inventory_id,0);

        if ($inventory_items)
        foreach ($inventory_items as $k=>$v){
             //小计
             $subtotal['accounts_num'] += $v['accounts_num'];
             $subtotal['actual_num'] += $v['actual_num'];
             $subtotal['shortage_over'] += $v['shortage_over'];
        }
        $count = $total['count'];
         $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=go_inventory&inventory_id='.$inventory_id.'&page=%d',
        ));
        $this->pagedata['subtotal'] = $subtotal;
        $this->pagedata['total'] = $total[0];
        $this->pagedata['pager'] = $pager;
        $this->pagedata['items'] = $inventory_items;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['count'] = $count;
        $this->page("admin/inventory/addinventory_online.html");
    }
    //新建盘点
    function create_inventory(){
        $this->begin('index.php?app=taoguaninventory&ctl=admin_inventorylist&act=go_inventory&inventory_id='.$_POST['inventory_id']);
        $inventory_id = $_POST['inventory_id'];
        if (!$_POST['branch_id']){
            $this->end(false, '无仓库信息');
        }
        if($_POST['pos']=='1'){
            if(!$_POST['pos_name']){
                $this->end(false, '无货位信息');
            }
        }
        if ($_POST['number'] == 0 || !empty($_POST['number'])){
            if (!is_numeric($_POST['number']) || intval($_POST['number']) < 0){
                $this->end(false, '请输入自然数');
            }
        }
        $msg = '';
        $result = kernel::single('taoguaninventory_inventorylist')->save_inventory($_POST,$msg);
        if ($result){
            $msg = $msg!='' ? $msg : '盘点完成'; 

            $this->end(true, '盘点完成','index.php?app=taoguaninventory&ctl=admin_inventorylist&act=go_inventory&inventory_id='.$inventory_id);
        }else {
            $msg = $msg!='' ? $msg : '盘点失败'; 
            $this->end(false, '盘点失败');
        }

    }

    /*
    * 盘点明细
    */

    function detail_inventory_object(){
        $item_id = $_GET['item_id'];
        $product_id = $_GET['product_id'];
        $mdl_inventory_object = $this->app->model('inventory_object');
        $inventory_object_list =  $mdl_inventory_object->getList('oper_name,bn,barcode,pos_name,actual_num,oper_time', array('item_id'=>$item_id,'product_id'=>$product_id));
        $this->pagedata['items'] =$inventory_object_list;
        $this->page("admin/inventory/detail_inventory_object.html");
    }

    /*
    * 导出
    */
    function export_inventory(){
        set_time_limit(0);
        @ini_set('memory_limit','64M');
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=storange".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $mdl_inventory = $this->app->model('inventory');
        $mdl_inventory_items = $this->app->model('inventory_items');
        $inventory_id = $_POST['inventory_id'];
        $shortage_over = $_POST['shortage_over'];
        $is_auto = $_POST['is_auto'];
        if($is_auto=='0' || $is_auto=='1'){
            $filter = array('inventory_id'=>$inventory_id,'is_auto'=>$is_auto);
        }else{
            $filter = array('inventory_id'=>$inventory_id);
        }
        $inventory_items = $mdl_inventory_items->getList('name,bn,spec_info,unit,accounts_num,price,actual_num,shortage_over', $filter);
        $title1 = $mdl_inventory->exportTemplate('shortage_over');
        echo '"'.implode('","',$title1).'"';
        echo "\n";
        foreach($inventory_items as $k=>$v){
            $v['name'] = kernel::single('base_charset')->utf2local($v['name']);
            $v['spec_info'] = kernel::single('base_charset')->utf2local($v['spec_info']);
            //$v['bn'] = $v['bn']."\t";
            $v['bn'] = kernel::single('base_charset')->utf2local($v['bn'])."\t";
            $v['unit'] = kernel::single('base_charset')->utf2local($v['unit'])."\t";
            
            #盈亏金额
            if(!empty($v['price'])){
                $v['shortage_over_price'] = $v['shortage_over'] * $v['price'];
            }
            if($shortage_over==1){
               if($v['shortage_over']!=0){

                    echo '"'.implode('","',$v).'"'."\n";
                }
          }else{

               echo '"'.implode('","',$v).'"'."\n";
           }

        }
    }

    /*
    * 盘点编辑
    *
    */
    function edit_inventory(){

        $inventory_id = intval($_GET['inventory_id']);

        $oInventory_items = $this->app->model('inventory_items');
        $oinventory_object = $this->app->model('inventory_object');
        $oInventory = $this->app->model('inventory');

        $inventory = $oInventory->dump($inventory_id,'branch_id,inventory_name,inventory_bn,branch_name,inventory_type,difference,inventory_checker,pos,second_checker,finance_dept,warehousing_dept,add_time,inventory_date,branch_name');
        $inventory_items = $oInventory_items->getList('*', array('inventory_id'=>$inventory_id,'is_auto'=>'0'));
        foreach($inventory_items as $k=>$v){
            $inventory_items[$k]['PRIMARY_ID']=$v['product_id'];
            $item=$oinventory_object->getlist('*',array('inventory_id'=>$v['inventory_id'],'product_id'=>$v['product_id']));
            foreach($item as $key=>$val){
                $item[$key]['PRIMARY_ID']=$val['obj_id'].$val['item_id'];
                $item[$key]['oper_time']=date('Y-m-d',$val['oper_time']);
            }
            $inventory_items[$k] ['item']= $item;

        }


        $branch_product = kernel::single('taoguaninventory_inventorylist')->check_product_iostock($inventory['branch_id']);

        $this->pagedata['inventory_items'] = $inventory_items;
        $this->pagedata['branch_product'] = $branch_product;
        $this->pagedata['inventory_id'] = $inventory_id;
        $this->pagedata['inventory'] = $inventory;

        $this->singlepage('admin/inventory/edit_inventory.html');
    }


    function findProduct($supplier_id=null){
        $params = array(
                        'title'=>'商品列表',
                        'use_buildin_new_dialog' => true,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,

                    );
        $this->finder('ome_mdl_products', $params);
   }

    function getProducts(){
        $pro_id = $_POST['product_id'];
        $pro_bn= $_GET['bn'];
        $pro_name= $_GET['name'];
        $pro_barcode= $_GET['barcode'];
        if (is_array($pro_id)){
            $filter['product_id'] = $pro_id;
        }
        if($pro_bn){
           $filter = array(
               'bn'=>$pro_bn
           );
        }
        if($_GET['branch_id']){
            $branch_id = $_GET['branch_id'];
        }
        if($pro_name){
            $filter = array(
               'name'=>$pro_name
           );
        }
        if($pro_barcode){
            $filter = array(
               'barcode'=>$pro_barcode
           );
        }

        $pObj = &app::get('ome')->model('products');
        $pObj->filter_use_like = true;
        $data = $pObj->getList('product_id,bn,name,price,barcode,spec_info',$filter,0,-1);
        $pObj->filter_use_like = false;

        if (!empty($data)){
            foreach ($data as $k => $item){

                $item['num'] = 0;
                //$item['price'] = $this->app->model('po')->getPurchsePrice($item['product_id'], 'asc');
                if (!$item['price']){
                    $data[$k]['price'] = 0;
                }
                if($branch_id){
                    $data[$k]['accounts_num'] = kernel::single('taoguaninventory_inventorylist')->get_accounts_num($item['product_id'],$branch_id);
                    $data[$k]['pos_name'] = '';
                }
                $data[$k]['PRIMARY_ID'] = $item['product_id'];

            }
        }

        $rows = $data;
        echo "window.autocompleter_json=".json_encode($rows);
    }

    /*
    *返回商品json结果
    *@return json
    */
    function getProduct(){
        $searchtype = $_GET['searchtype'];
        $product_bn = $_GET['product_bn'];

        if($searchtype=='bn'){
            $filter = array(
                'bn' => $product_bn
            );
        }else if($searchtype=='barcode'){
            $filter = array(
                'barcode' => $product_bn
            );
        }
        $pObj = &app::get('ome')->model('products');

        $data = $pObj->getlist('product_id,bn,name,price,barcode,spec_info',$filter);

        $product = array();

        if($data){
            foreach($data as $k=>$v){
                $data[$k]['PRIMARY_ID'] = $data['product_id'];
            }

            $product= json_encode($data);
            echo $product;
        }
    }



     function getEditProducts($inventory_id){

        if ($inventory_id == ''){
            $inventory_id = $_POST['p[0]'];
        }
        $filter['inventory_id']=$inventory_id;
        $filter['is_auto']='0';
        $searchtype = $_GET['searchtype'];

        $product_bn = $_GET['product_bn'];
        if($searchtype){
            if($searchtype=='bn'){
                $filter['bn'] = $product_bn;
            }else if($searchtype=='barcode'){
                $filter['barcode'] = $product_bn;
            }
        }
        $oInventory_items = $this->app->model('inventory_items');
        $oinventory_object = $this->app->model('inventory_object');
        $oInventory = $this->app->model('inventory');

        $rows = array();
        $items = $oInventory_items->getList('product_id,bn,name,spec_info,unit,pos_name,accounts_num,actual_num,shortage_over,price,inventory_id,item_id,accounts_num',$filter,0,-1);

       foreach($items as $k=>$v){

           $items[$k]['PRIMARY_ID'] = $v['product_id'];
           $item = $oinventory_object->getlist('pos_id,item_id,product_id,bn,barcode,obj_id,oper_name,oper_time,pos_name,actual_num',array('inventory_id'=>$v['inventory_id'],'product_id'=>$v['product_id']));
           foreach($item as $key=>$val){
               $item[$key]['PRIMARY_ID'] = $val['obj_id'].$val['item_id'];
                $item[$key]['oper_time'] = date('Y-m-d',$val['oper_time']);
           }
            $items[$k] ['item']= $item;

       }
        if ($items)
        $rows = $items;
        echo json_encode($rows);
    }
    /**
    *编辑盘点单基本信息
    */
    function doEditbasic(){
        $oInventory = $this->app->model('inventory');
        $data = $_POST;
        $this->begin();
        $branch_id = $data['branch_id'];
        $inventory_data['inventory_type'] = $data['inventory_type'];
        $inventory_data['inventory_name'] = $data['inventory_name'];
        $inventory_data['inventory_id'] = $data['inventory_id'];
         if($data['inventory_type']==2){
            $inv_exist = $oInventory->dump(array('branch_id'=>$branch_id,'inventory_type'=>2,'confirm_status'=>1),'inventory_id');

            if($inv_exist['inventory_id']!=$data['inventory_id'] && $inv_exist){
                $this->end(false,'此仓库已有全盘的盘点单存在');
            }
            $inv_exist1 = $oInventory->dump(array('branch_id'=>$branch_id,'inventory_type'=>3,'confirm_status'=>1),'inventory_id');

             if($inv_exist1['inventory_id']!=$data['inventory_id'] && $inv_exist1){
                $this->end(false,'请将部分盘点确认后再新建全盘');
            }
        }
        if($inventory_data['inventory_type']==3){
             $inv_exist2 = $oInventory->dump(array('branch_id'=>$branch_id,'inventory_type'=>2,'confirm_status'=>1),'inventory_id');
             if($inv_exist2['inventory_id']!=$data['inventory_id'] && $inv_exist2){
                $this->end(false,'请将此仓库全盘确认后再新建部分盘点');
            }
        }

        $result=$oInventory->save($inventory_data);
        kernel::single('taoguaninventory_inventorylist')->hide_add_product_list($data['inventory_id'],$data['inventory_type'],$data['branch_id']);
        $this->end(true, '修改成功','index.php?app=taoguaninventory&ctl=admin_inventorylist&act=index&flt=confirm');
    }
    /*
    *编辑盘点单
    *
    */
    function doEdit(){
        $data = $_POST;

        $this->begin();
        $oInventory_items = $this->app->model('inventory_items');
        $oInventory = $this->app->model('inventory');
        $opObj  = &app::get('ome')->model('operation_log');
//        $inventory_data['inventory_type'] = $data['inventory_type'];
//        $inventory_data['inventory_name'] = $data['inventory_name'];
//        $inventory_data['inventory_id'] = $data['inventory_id'];
//        $result=$oInventory->save($inventory_data);
        if(is_array($data['product_id'] )){
        foreach($data['product_id'] as $k=>$v){

            $inventory =  array(
                    'inventory_id'=>$data['inventory_id'],
                    'product_id'=>$v,
                    'branch_id'=>$data['branch_id'],
                    //'pos_id'=>$data['pos_id'][$k],
            );
            $inv_item = $oInventory_items->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$v),'item_id,actual_num');

            $inventory['item_id'] = $inv_item['item_id'];
            if($data['cname'][$v]){
                foreach($data['cname'][$v] as $key=>$val){
                    $inventory['number'] = $val;
                    $inventory['obj_id'] = $key;
                    $inventory['item_id'] = $inv_item['item_id'];
                    $inventory['pos_id'] = $data['pos_id'][$v][$key];
                    $inventory['pos_name'] = $data['pos_name'][$v][$key];
                    kernel::single('taoguaninventory_inventorylist')->update_inventory_item($inventory);
               }
            }
            if($data['pname'][$v]){
                foreach($data['pname'][$v] as $pkey=>$pval){

                    $inventory['number'] = $pval;
                    if($data['ppos_id'][$v]){
                        $inventory['pos_name'] = $data['ppos_id'][$v][$pkey];
                    }
                    unset($inventory['obj_id']);
                    unset($inventory['pos_id']);
                    $inventory['item_id'] = $inv_item['item_id'];

                    kernel::single('taoguaninventory_inventorylist')->update_inventory_item($inventory);
                }
            }
        }
            kernel::single('taoguaninventory_inventorylist')->update_inventorydifference($data['inventory_id']);

            $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], '盘点单编辑');
            $this->end(true, '盘点修改完成','app=taoguaninventory&ctl=admin_inventorylist&act=index&flt=confirm');
        }else{

            $this->end(false, '您没有添加任何盘点商品','index.php?app=taoguaninventory&ctl=admin_inventorylist&act=edit_inventory&inventory_id='.$data['inventory_id'].'');
        }

    }

    /**
     * @删除盘点表明细
     * @access public
     * @param void
     * @return void
     */
    public function del_inventory(){
        $action = $_GET['action'];
        if($action=='del_obj_id'){
             $del_obj_data  = array(
                                    'action'=>'obj',
                                    'obj_id'=>$_GET['obj_id'],
                                    'inventory_id'=>$_GET['inventory_id'],
                                    );

            $result = kernel::single('taoguaninventory_inventorylist')->del_inventory($del_obj_data);
        }
        if($action=='del_item_id'){
            $del_item_data =array(
                                'action'=>'item',
                                'item_id'=>$_GET['item_id'],
                                'inventory_id'=>$_GET['inventory_id'],
                            );
            $result = kernel::single('taoguaninventory_inventorylist')->del_inventory($del_item_data);
        }
        $data = array();
         if($result){
             $data['message'] = '删除成功';
         } else {
             $data['message'] = '删除成功';
         }
         echo json_encode($data);

    }



    /**
    *  预盈亏计算并显示
    */
    function shortage_over($inventory_id,$page=1){
        $data = $_GET;
        $page = intval($_GET['page']);
        $page = $page ? $page : 1;

        $pagelimit = 10;
        $inventory_id = $data['inventory_id'];
        $branch_id = $data['branch_id'];
        $oInventory = $this->app->model('inventory');
        $oInventory_items = $this->app->model('inventory_items');
        if($branch_id){
            kernel::single('taoguaninventory_inventorylist')->refresh_shortage_over($inventory_id,$branch_id);
        }
        $inventory_items = $oInventory_items->getList('product_id,bn,name,spec_info,unit,pos_name,accounts_num,actual_num,shortage_over,price,inventory_id,item_id,accounts_num,is_auto',array('inventory_id'=>$inventory_id),$pagelimit*($page-1), $pagelimit,'is_auto asc,bn desc');
        $total = $oInventory->getInventoryTotal($inventory_id);

        if ($inventory_items){
            foreach ($inventory_items as $k=>$v){
                 //小计
                 $subtotal['accounts_num'] += $v['accounts_num'];
                 $subtotal['actual_num'] += $v['actual_num'];
                 $subtotal['shortage_over'] += $v['shortage_over'];
            }
        }
        $count = $total['count'];
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=shortage_over&inventory_id='.$inventory_id.'&page=%d',
        ));
        $this->pagedata['inventory_item'] = $inventory_items;
        $this->pagedata['subtotal'] = $subtotal;#小计
        $this->pagedata['total'] = $total[0];#总计
        $this->pagedata['pager'] = $pager;
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['inventory_id'] = $inventory_id;
        unset($inventory_item);
        $this->page('admin/inventory/shortage_over.html');
    }

    /**
    * 刷新预盈亏
    */
    function refresh_shortage_over(){
        $this->begin();
        $inventory_id = $_GET['inventory_id'];
        $branch_id = $_GET['branch_id'];
        $opObj  = &app::get('ome')->model('operation_log');
        $result = kernel::single('taoguaninventory_inventorylist')->refresh_shortage_over($inventory_id,$branch_id);
        $opObj->write_log('inventory@taoguaninventory', $inventory_id, '盘点单刷新预盈亏');
        $this->end(true, '成功');
    }



    /**
    * 确认盘点
    */
    function confirm_inventory($inventory_id,$page){
        $inventory_id = intval($inventory_id);
        $page = intval($page);
        $page = $page ? $page : 1;
        $pagelimit = 8;
        $oInventory = $this->app->model('inventory');
        $oInventory_items = $this->app->model('inventory_items');
        $inventory_detail = $oInventory->dump($inventory_id, '*');
        $is_auto = $_GET['is_auto'];
        if(($is_auto=='0') || ($is_auto=='1')){

            $filter = array('inventory_id'=>$inventory_id,'is_auto'=>$is_auto);
            $total = $oInventory->getInventoryTotal($inventory_id,$is_auto);
        }else{
            $filter = array('inventory_id'=>$inventory_id);
            $total = $oInventory->getInventoryTotal($inventory_id);
        }


        //盘点明细
       $inventory_items = $oInventory_items->getlist('name,bn,spec_info,item_id,unit,price,memo,actual_num,shortage_over,accounts_num,product_id,is_auto',$filter, $pagelimit*($page-1), $pagelimit,'is_auto ASC,bn DESC');
       $branch_id =  $inventory_detail['branch_id'];

       $total_price = 0;
       #盈亏总金额
       $total_shortage_over_price = 0;
        if ($inventory_items)
        foreach ($inventory_items as $k=>$v){
            $accounts_num = kernel::single('taoguaninventory_inventorylist')->get_accounts_num($v['product_id'],$branch_id);
            #成本价
            $price = kernel::single('taoguaninventory_inventorylist')->get_price($v['product_id'],$branch_id);
            $total_price += $price;
            $inventory_items[$k]['price'] = $price;
            #盈亏数量
            $shortage_over = $v['actual_num']-$accounts_num;
            #盈亏金额
            $shortage_over_price = $price * $shortage_over;
            $inventory_items[$k]['shortage_over_price'] = $shortage_over_price;
            $total_shortage_over_price  += $shortage_over_price;
            
            $inventory_items[$k]['accounts_num'] = $accounts_num;
            $inventory_items[$k]['shortage_over'] = $shortage_over;
             //小计
             $subtotal['accounts_num'] += $accounts_num;
             $subtotal['actual_num'] += $v['actual_num'];
             $subtotal['shortage_over'] += $shortage_over;
        }
        $total[0]['price'] = $total_price;
        $total[0]['shortage_over_price'] = $total_shortage_over_price;
        $count = $total['count'];

        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=taoguaninventory&ctl=admin_inventorylist&act=confirm_inventory&is_auto='.$is_auto.'&p[0]='.$inventory_id.'&p[1]=%d',
        ));

        $this->pagedata['pager'] = $pager;
        $this->pagedata['is_auto'] = $is_auto;
        $this->pagedata['detail'] = $inventory_detail;
        $this->pagedata['items'] = $inventory_items;
        $this->pagedata['inventory_id'] = $inventory_id;
        $this->pagedata['total'] = $total[0];#小计
        //$this->pagedata['find_id'] = $_GET['find_id'];
        $this->pagedata['count'] = $count;
        
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['view'] = $_GET['view'];
        #需确认条数
        $need_inventoryList = kernel::single('taoguaninventory_inventorylist')->ajax_inventorylist($inventory_id);
        
        $this->pagedata['need_inventoryList'] = json_encode($need_inventoryList);
        $this->pagedata['need_inventorylist_count'] = count($need_inventoryList);
        $this->page('admin/inventory/confirm_inventory.html');
    }

    /**
    *保存盘点确认
    */
    function save_confirm_inventory(){
        set_time_limit(0);
        $data = array();

        # 数据验证 chenping
        if (empty($_GET['inventory_id'])) {
            $data = array(
                'message' => '请选择盘点单！',
                'result' => 'fail',
            );
            echo json_encode($data);exit;
        }

        $oInventory = $this->app->model('inventory');
        $inventory = $oInventory->dump(array('inventory_id'=>$_GET['inventory_id']),'confirm_status,inventory_id');
        if ($inventory['confirm_status'] != 1) {
            $status = $oInventory->schema['columns']['confirm_status']['type'][$inventory['confirm_status']];
            $data = array(
                'message' => '盘点已经'.$status.',不可以盘点',
                'result' => 'fail',
            );
            echo json_encode($data);exit;
        }
        # 更新为盘点中
        $oInventory->update(array('confirm_status'=>'4'),array('inventory_id'=>$inventory['inventory_id']));

        $result = kernel::single('taoguaninventory_inventorylist')->confirm_inventory($_GET,$msg);
        $opObj  = &app::get('ome')->model('operation_log');
        if ($result){
            $opObj->write_log('inventory_modify@taoguaninventory',$inventory['inventory_id'], '盘点单确认');
            $data['message'] = '盘点单确认成功';
            $data['result']='succ';
        }else {
            $opObj->write_log('inventory_modify@taoguaninventory',$inventory['inventory_id'], '盘点单确认失败');

            $data['message'] = '盘点单确认失败';
            $data['result']='fail';
        }

        echo json_encode($data);
    }

    /**
    * 导入
    */
    function import(){
        $this->page('admin/inventory/import.html');
    }

    function batch_cancel(){
        $this->begin();
        $inventory_id = $_POST['inventory_id'];
        $inventoryObj = &app::get('taoguaninventory')->model('inventory');
        if( is_array($inventory_id) ){
            foreach($inventory_id as $k=>$v){
                $inventory = $inventoryObj->dump(array('inventory_id'=>$v),'confirm_status');
                if($inventory['confirm_status']==2 || $inventory['confirm_status']==3){
                    $this->end(false, '不可以操作,请确认盘点单是否已确认或已作废');
                }
             }
             $inventoryObj->dead_inventory($inventory_id);
         }
         $this->end(true, '操作成功');


    }

     /**
    *新建盘点
    */
    function inventory_selectbranch(){
        $brObj = &app::get('ome')->model('branch');
        $branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        $is_super = kernel::single('desktop_user')->is_super();
        if ($is_super){
            $branch_list = $brObj->getList('branch_id,name','',0,-1);
        }else {
           $branch_list = $brObj->getBranchByUser();
        }
        $this->pagedata['date']         = date("Y年m月d日");
        $this->pagedata['op_name']      = kernel::single('desktop_user')->get_name();
        $this->pagedata['branch_list'] = $branch_list;
        $this->page("admin/inventory/add_inventory.html");
    }

    /**
    *批量删除
    */
    function batch_delete(){
        $this->begin();
        $inventory_id = $_POST['inventory_id'];
        $inventoryObj = &app::get('taoguaninventory')->model('inventory');
        if( is_array($inventory_id) ){
            foreach($inventory_id as $k=>$v){
                $inventory = $inventoryObj->dump(array('inventory_id'=>$v),'confirm_status');
                if($inventory['confirm_status']=='2' || $inventory['confirm_status']=='4'){
                    $this->end(false, '盘点单已确认或确认中，不可删除');
                }
             }
             $inventoryObj->batch_delete($inventory_id);
         }
         $this->end(true, '操作成功','javascript:finderGroup["'.$_GET['finder_id'].'"].unselectAll();finderGroup["'.$_GET['finder_id'].'"].refresh();');


    }

    /**
    *
    */
    function ajaxDoConfirminventory(){
        set_time_limit(0);
        $data = $_POST;
        $ajaxParams = trim($data['ajaxParams']);
        if (strpos($ajaxParams, ';')) {

            $params = explode(';', $ajaxParams);
        } else {

            $params = array($ajaxParams);
        }
        $inventory_id = $data['inventory_id'];
        #
        $fail = 0;
        $succ=0;
        $fallinfo = array();
        $result = kernel::single('taoguaninventory_inventorylist')->doajax_inventorylist($data,$params,$fail,$succ,$fallinfo);
        #查询当前是否有单据未盘点成功
        $oInventory_items = $this->app->model('inventory_items');
        $inventory_items = $oInventory_items->count(array('inventory_id'=>$data['inventory_id'],'status'=>'false'));
        $inventory_data = array();
        $inventory_data['inventory_id'] = $data['inventory_id'];
        if($inventory_items==0){
            $inventory_data['confirm_status'] = 2;
        }else{
            $inventory_data['confirm_status'] = 4;
        }
        $inventory_data['confirm_time'] = time();
        $inventory_data['confirm_op'] = kernel::single('desktop_user')->get_name();;
        $this->app->model('inventory')->save($inventory_data);

        echo json_encode(array('total' => count($params), 'succ' => $succ, 'fail' => $fail, 'fallinfo'=>$fallinfo));



    }


    
    /**
    * 过滤盘点条件
    */
    function filter_inventory(){
        $inventory_id = intval($_POST['inventory_id']);
        $oInventory = $this->app->model('inventory');
        $inventory = $oInventory->dump(array('inventory_id'=>$inventory_id),'confirm_status,inventory_id');
        if ($inventory['confirm_status'] != 1 && $inventory['confirm_status']!=4) {

            $data = array(
                'message' => '盘点单已经确认或作废,不可以盘点',
                'result' => 'fail',
            );

        }else{
            $data = array(

                'result' => 'succ',
            );
            
            
        }
        echo json_encode($data);exit;
    }
}

?>
