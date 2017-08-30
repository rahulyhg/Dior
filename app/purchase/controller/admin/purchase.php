<?php
class purchase_ctl_admin_purchase extends desktop_controller{
    var $name = "采购管理";
    var $workground = "purchase_manager";

    function _views(){
        if($_GET['act'] == 'eoList'){
            return false;
        }
        $mdl_po = $this->app->model('po');
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
                1 => array('label'=>app::get('base')->_('待处理'),
                        'filter'=>array(
                               'eo_status|noequal' => '3',#入库状态不是完成已入库
                               'po_status|noequal' => '2'#采购状态不是完成已入库
                        ),
                        'optional'=>false),
                2 => array(
                        'label'=>app::get('base')->_('已完成'),
                        'filter'=>array(
                                'eo_status' =>'3' ,   #入库状态是完成已入库
                                'po_status' =>'4'    #采购状态是完成已入库
                                ),
                        'optional'=>false),
                3 => array(
                        'label'=>app::get('base')->_('已终止'),
                        'filter'=>array(
                                'po_status' =>'2'    #采购状态是完成已入库
                        ),
                        'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_po->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=purchase&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }
    function index(){
        $is_export = kernel::single('desktop_user')->has_permission('purchase_export');#增加采购订单导出权限
        $params = array(
                        'title'=>'采购订单',
                        'actions' => array(
                                array(
                                    'label' => '新建',
                                    'href' => 'index.php?app=purchase&ctl=admin_purchase&act=add',
                                    'target' => '_blank',
                                ),
                                array(
                                    'label' => '导出模板',
                                    'href' => 'index.php?app=purchase&ctl=admin_purchase&act=exportTemplate',
                                    'target' => '_blank',
                                ),
                                /*
                                array(
                                    'label' => '打印样式',
                                    'href' => 'index.php?app=ome&ctl=admin_receipts_print&act=showPrintStyle',
                                    'target'=>'dialog::{width:1000,height:400,title:\'打印样式\'}'
                                ),*/
                        ),
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>$is_export,
                        'use_buildin_import'=>true,
                        'use_buildin_filter'=>true,
                        'finder_cols'=>'column_edit,supplier_id,emergency,name,arrive_time,operator,deposit,purchase_time,check_status,check_time,po_status,po_type,statement,eo_status,branch_id,check_operator',
                        'orderBy' => 'emergency asc,purchase_time desc'
                    );

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                if( isset($_POST['branch_id']) && $_POST['branch_id']){
                    $params['base_filter']['branch_id'] = $_POST['branch_id'];
                }else{
                    $params['base_filter']['branch_id'] = $branch_ids;
                }
                
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }


        $this->finder('purchase_mdl_po', $params);
    }

    function checklist(){
        $params = array(
                        'title'=>'待审核',
                        'base_filter' => array('check_status'=>array(1)),
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'orderBy' => 'emergency asc,purchase_time desc'
                    );
        $this->finder('purchase_mdl_po', $params);
    }

    public function check_auto() {
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        foreach($_POST['po_id'] as $v){
            $this->app->model('po')->update(array('check_status'=>2, 'eo_status'=>1),array('po_id'=>$v,'check_status'=>1));
        }
        $this->end(true, '批量审核成功');
    }

    public function check($po_id, $uncheck = false) {
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        if (empty($po_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        if($uncheck){
            $checkInfo = array(
               'title' => '反审核',
               'action' => 'do_uncheck'
            );
        }else{
           $checkInfo = array(
               'title' => '审核',
               'action' => 'do_check'
            );
        }
        $this->pagedata['checkInfo'] = $checkInfo;
        $poObj = &$this->app->model('po');
        $suObj = &$this->app->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $pObj = &app::get('ome')->model('products');
        $data = $poObj->dump($po_id, '*', array('po_items' => array('*')));

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
        $data['branch_name'] = $br['name'];
        $data['supplier_name'] = $su['name'];
        //到货天数
        $diff_time = $data['arrive_time'] - $data['purchase_time'];
        $data['diff_days'] = floor($diff_time/(24*60*60));
        $this->pagedata['po_items'] = $data['po_items'];
        if ($data['memo']) {
            $data['memo'] = unserialize($data['memo']);
            foreach((array) $data['memo'] as $key =>$v){
                $str = unserialize($v['op_content']);
                if($str){
                    $data['memo'][$key]['op_content'] = $str[0]['op_content'];
                }else{
                    $data['memo'][$key]['op_content'] = $v['op_content'];
                }
            }
        }
        $this->pagedata['po'] = $data;
        $this->singlepage("admin/purchase/purchase_check.html");
    }

    public function do_check() {
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        $mdl_po = $this->app->model('po');
        $aRow = $mdl_po->dump($_POST['po_id'], '*', array('po_items'=>array('product_id,num')));
        if($aRow['check_status']=='2'){
            $this->end(false, '此采购单已审核');
        }
        foreach($aRow['po_items'] as $k=>$v){
            $mdl_po->updateBranchProductArriveStore($aRow['branch_id'], $v['product_id'], $v['num'], '+');
        }
        $payObj = $this->app->model('purchase_payments');
        $pay_bn = $payObj->gen_id();

        $row['payment_bn'] = $pay_bn;
        $row['po_id'] = $aRow['po_id'];
        $row['po_type'] = $aRow['po_type'];
        $row['add_time'] = time();
        $row['supplier_id'] = $aRow['supplier_id'];
        $row['operator'] = kernel::single('desktop_user')->get_name();
        if ($aRow['po_type'] == 'cash'){//现购,生成付款单
            $row['payable'] = $aRow['product_cost']+$aRow['delivery_cost'];
            $row['deposit'] = 0;
            $row['product_cost'] = $aRow['product_cost'];
            $row['delivery_cost'] = $aRow['delivery_cost'];
            $payObj->save($row);
        }elseif ($aRow['po_type'] == 'credit' && $aRow['deposit'] >0) {//赊购,预付款不为0时生成付款单
            $row['payable'] = $aRow['deposit'];
            $row['deposit'] = $aRow['deposit'];
            $row['product_cost'] = 0;
            $row['delivery_cost'] = 0;
            $payObj->save($row);
        }

        $this->app->model('po')->update(array(
            'check_status' => 2,
            'eo_status' => 1,
            'check_time'=> time(),
            'check_operator' => kernel::single('desktop_user')->get_name()
            ),array('po_id'=>$_POST['po_id'],'check_status'=>1));
        $this->end(true, '审核完成');
    }

    public function uncheck($po_id) {
           $this->check($po_id, true);
    }

    public function do_uncheck() {

    }

    function eoList($p=null){
        switch ($p) {
            case 'i':
                $sub_title = '采购入库';
                $this->workground = 'storage_center';
            break;
            default:
                $sub_title = '待入库';
                break;
        }

        $filter['eo_status'] = array('1', '2');
        $params = array(
                        'title'=>$sub_title,
                        'base_filter' => $filter,
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'orderBy' => 'purchase_time desc',
        				'finder_cols'=>'column_edit,supplier_id,emergency,name,arrive_time,operator,deposit,purchase_time,check_status,check_time,po_status,po_type,statement,eo_status,branch_id,check_operator',
                    );

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
        $this->finder('purchase_mdl_po', $params);
    }

    function findProduct($supplier_id=null){

        if ($supplier_id){
            //根据供应商商品
            $oSupplierGoods = app::get('purchase')->model('supplier_goods');
            $oProduct = app::get('ome')->model('products');
            $supplier_goods = $oSupplierGoods->getSupplierGoods($supplier_id);
            $products = $oProduct->getList('product_id', $supplier_goods, 0, -1);
            $product_list = array();
            if ($products)
            foreach ($products as $k=>$v){
                $product_list[] = $v['product_id'];
            }
            $base_filter = array('product_id'=>$product_list);
        }

        # 隐藏商品不显示
        if (!isset($_POST['visibility'])) {
            $base_filter['visibility'] = 'true';
        }elseif(empty($_POST['visibility'])){
            unset($_POST['visibility']);
        }

        $object_method = array('count'=>'count','getlist'=>'getlist');
        if ($_POST['branch_id']) {
            $base_filter['product_group'] = true;
            $object_method = array('count'=>'countAnother','getlist'=>'getListAnother');
        }
        
        $params = array(
            'title'=>'商品列表',
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

    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=CG".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $pObj = &$this->app->model('po');
        $title1 = $pObj->exportTemplate('purchase');
        $title2 = $pObj->exportTemplate('item');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }

    /**
     * 新建采购单
     *
     */
    function add(){
        $suObj = &$this->app->model('supplier');
        $data = $suObj->getList('supplier_id, name','',0,-1);

        $brObj = &app::get('ome')->model('branch');
        $row = $brObj->getList('branch_id, name','',0,-1);

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $brObj->getBranchByUser();
        }
        $this->pagedata['branch_list'] = $branch_list;
        $is_super = 1;
        $this->pagedata['is_super'] = $is_super;


        //获取设置的采购方式
        $po_type = &app::get('ome')->getConf('purchase.po_type');

        if (!$po_type) $po_type = 'credit';
        $this->pagedata['po_type'] = $po_type;

        //获取仓库模式
        //$branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        //if (!$branch_mode){
            //$branch_mode = 'single';
        //}
        //$this->pagedata['branch_mode'] = $branch_mode;
//        if ($branch_mode=='single'){
//            if ($row)
//            foreach ($row[0] as $k=>$v){
//                if ($k=='name'){
//                    $row = $v;
//                }
//                if ($k=='branch_id'){
//                    $branch_id = $v;
//                }
//            }
//        }

        $this->pagedata['supplier'] = $data;
        $operator = kernel::single('desktop_user')->get_name();
        $this->pagedata['operator'] = $operator;

        $this->pagedata['branch'] = $row;
        $this->pagedata['branchid'] = $branch_id;
        $this->pagedata['cur_date'] = date('Ymd',time()).'采购单';
        $this->singlepage("admin/purchase/purchase_add.html");
    }


    /**
     * 从仓库搜索商品
     *
     */
    function findInBranch(){
        $where = ' 1 ';
        if ($_POST['name'] != ''){
            $where .= " AND p.name LIKE '%".$_POST['name']."%' ";
        }
        if ($_POST['branch'] != ''){
            $where .= " AND bp.branch_id='".$_POST['branch']."' ";
        }
        $branchObj = &app::get('ome')->model('branch');
        $poObj = &$this->app->model('po');
        $branch = $branchObj->getList('branch_id,name', '', 0, -1);
        $data = $poObj->findProductsByBranch($where);

        $this->pagedata['goods_name'] = $_POST['name'];
        $this->pagedata['branch_id'] = $_POST['branch_id'];
        $this->pagedata['branch'] = $branch;
        $this->pagedata['data'] = $data;
        $this->display("admin/purchase/purchase_find_in_branch.html");
    }

    function getProducts(){
        $pro_id = $_POST['product_id'];
        $supplier_id = $_POST['supplier_id'];
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
        $filter['visibility'] = 'true';#新建采购,隐藏商品不显示
        $pObj = &app::get('ome')->model('products');
        $pObj->filter_use_like = true;
        $data = $pObj->getList('visibility,product_id,bn,name,price,barcode,spec_info',$filter,0,-1);
        $pObj->filter_use_like = false;

        if (!empty($data)){
            foreach ($data as $k => $item){
                $item['num'] = 0;
                if($supplier_id > 0){
                    $item['price'] = $this->app->model('po')->getPurchsePriceBySupplierId($supplier_id, $item['product_id'], 'desc');
                    if (!$item['price']){
                        $item['price'] = 0;
                    }
                }else{
                    $product = $pObj->dump(array('product_id'=>$item['product_id']),'cost');

                    $item['price'] = $product['price']['cost']['price'];
                }
                $item['price'] = sprintf('%.2f',$item['price']);
                //$arr[] = $v;
                $rows[] = $item;
            }
        }

        echo "window.autocompleter_json=".json_encode($rows);
    }

    function createPurchase($supplier_id,$branch_id,$bn){

        // 商品查询参数
        if($_POST['isSelectedAll']=='_ALL_') {
            $product_ids = &app::get('ome')->model('supply_product')->getList('*',$_POST,0,-1);
            for($i=0;$i<sizeof($product_ids);$i++){
                $product_id[] = $product_ids[$i]['product_id'];
            }
        }else{
            $product_id = $_POST['product_id'];
        }
        $this->pagedata['product_ids'] = implode(',',$product_id);
        // 获取供应商id
        $sql = 'SELECT supplier_id FROM sdb_purchase_supplier_goods AS a
                LEFT JOIN sdb_ome_products AS b ON a.goods_id=b.goods_id
                WHERE b.product_id IN ('.implode(',',$product_id).')
                LIMIT 1
        ';
        $rs = kernel::database()->select($sql);
        if($rs) $supplier_id = $rs[0]['supplier_id'];

        $filter = array('supplier_id'=>$supplier_id,'branch_id'=>$branch_id,'bn'=>$bn);

        $suObj = &$this->app->model('supplier');
        $data = $suObj->getList('supplier_id, name','',0,-1);

        $brObj = &app::get('ome')->model('branch');
        $row = $brObj->getList('branch_id, name','',0,-1);

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $brObj->getBranchByUser();
        }
        $this->pagedata['branch_list'] = $branch_list;
        $is_super = 1;
        $this->pagedata['is_super'] = $is_super;

        //获取设置的采购方式
        $po_type = &app::get('ome')->getConf('purchase.po_type');
        if (!$po_type) $po_type = 'credit';
        $this->pagedata['po_type'] = $po_type;

//        //获取仓库模式
//        $branch_mode = &app::get('ome')->getConf('ome.branch.mode');
//        if (!$branch_mode){
//            $branch_mode = 'single';
//        }
//        $this->pagedata['branch_mode'] = $branch_mode;
//
//        $this->pagedata['supplier'] = $data;
//        if ($branch_mode=='single'){
//            if ($row)
//            foreach ($row[0] as $k=>$v){
//                if ($k=='name'){
//                    $row = $v;
//                }
//                if ($k=='branch_id'){
//                    $branch_id = $v;
//                }
//            }
//        }
        $supplier = $suObj->dump($supplier_id, 'supplier_id,name,arrive_days');
        $filter = array('supplier_id'=>$supplier_id,'branch_id'=>$branch_id,'bn'=>$bn);
        $this->pagedata['filter'] = $filter;


        $operator = kernel::single('desktop_user')->get_name();
        $this->pagedata['operator'] = $operator;
        $this->pagedata['supplier'] = $supplier;
        $this->pagedata['branchid'] = $branch_id;
        $this->pagedata['branch'] = $row;
        $this->pagedata['cur_date'] = date('Ymd',time()).'采购单';
        $this->singlepage("admin/purchase/purchase_create.html");
    }

    function need(){
        $supplierObj = $this->app->model('supplier');
        $data = $supplierObj->getList('supplier_id,name', '', 0, -1);
        $data[] = array('supplier_id'=>'0','name'=>'全部');

//        //获取第一个仓库值与ID
//        $oBranch = &app::get('ome')->model('branch');
//        $branch = $oBranch->getList('branch_id, name','',0,1);
//        $bran['branch_id'] = $branch[0]['branch_id'];
//        $bran['name'] = $branch[0]['name'];

        $brObj = &app::get('ome')->model('branch');
        $row = $brObj->getList('branch_id, name','',0,-1);

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $brObj->getBranchByUser();
        }
        $this->pagedata['branch_list'] = $branch_list;
        $is_super = 1;
        $this->pagedata['is_super'] = $is_super;

        $this->pagedata['supplier'] = $data;
        $this->pagedata['branch'] = $row;
        $this->pagedata['branchid'] = $row[0]['branch_id'];
        $this->page("admin/purchase/requirement.html");
    }

    function getSafeStock($supplier_id,$branch_id,$bn,$product_ids){
        $data['supplier_id'] = $supplier_id;
        $data['bn'] = $bn;
        $data['branch_id'] = $branch_id;
        $data['product_ids'] = $product_ids;

        $oPo = &app::get('purchase')->model('po');
        $data = $oPo->getSafeList($data);
        $pObj = &app::get('ome')->model('products');
        foreach ($data as &$item){
            if($supplier_id > 0){
                $item['price'] = &app::get('purchase')->model('po')->getPurchsePriceBySupplierId($supplier_id, $item['product_id'], 'desc');
                if (!$item['price']){
                    $item['price'] = 0;
                }
            }else{
                $product = $pObj->dump(array('product_id'=>$item['product_id']),'cost');

                $item['price'] = $product['price']['cost']['price'];
            }
            $item['price'] = sprintf('%.2f',$item['price']);
 
       }
        echo json_encode($data);
    }

    function safeStockPreview($page=1){
        $data = utils::addslashes_array($_POST);
        //print_r($data);die;
        $page = $page ? $page : 1;
        $pagelimit = 12;

        //获取第一个仓库值与ID
//        $oBranch = &app::get('ome')->model('branch');
//        $branch = $oBranch->getList('branch_id, name','',0,1);
//        $data['branch_id'] = $branch[0]['branch_id'];
//
//        $bran['branch_id'] = $branch[0]['branch_id'];
//        $bran['name'] = $branch[0]['name'];
        //$data['branch_id'] = $_POST['branch_id'];


        //读取仓库的货品信息
        $oPo = $this->app->model('po');

        $safe_data = $oPo->getSafeStock($data, $pagelimit*($page-1), $pagelimit);

        $count = $safe_data['count'];
        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>'?page=%d'
        ));

        $this->pagedata['pager'] = $pager;
        unset($safe_data['count']);
        $this->pagedata['data'] = $safe_data;
        $this->pagedata['branch'] = $bran;
        $this->pagedata['total_page'] = $total_page;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['count'] = $count;
        $this->pagedata['cur_page'] = $page;
        return $this->display("admin/inventory/safe_stock_div.html");
    }

    function getEditProducts($po_id){
        if ($po_id == ''){
            $po_id = $_POST['p[0]'];
        }
        $pObj = &app::get('ome')->model('products');
        $piObj = &$this->app->model('po_items');
        $rows = array();
        $items = $piObj->getList('product_id,num,price,barcode,bn,name,spec_info,status,out_num,in_num',array('po_id'=>$po_id),0,-1);
        if ($items){
            $product_ids = array();
            foreach ($items as $k => $v){
                if ($v['status'] == '1' || ($v['in_num']+$v['out_num']) <= 0){
                    $items[$k]['delete'] = 1;
                }else {
                    $items[$k]['delete'] = 0;
                }
                $product_ids[] = $v['product_id'];
                $items[$k]['visibility'] = &$product[$v['product_id']]['visibility'];
                unset($items[$k]['status']);
                unset($items[$k]['out_num']);
                unset($items[$k]['in_num']);
            }
            if($product_ids){
                $plist = $pObj->getList('product_id,visibility',array('product_id'=>$product_ids));
                foreach ($plist as $value) {
                    $product[$value['product_id']]['visibility'] = $value['visibility'];
                }
            }
        }
        $rows = $items;

        echo json_encode($rows);
    }

    /**
     * 保存采购单
     *
     */
    function doSave(){
        $this->begin();
        $at = $_POST['at'];
        $pr = $_POST['pr'];
        $type = $_POST['type'];
        $name = $_POST['purchase_name'];
        $emergency = $_POST['emergency'];
        $supplier = $_POST['supplier'];
        $branch = $_POST['branch'];
        $price = $_POST['price'];
        $memo = $_POST['memo'];
        $arrive = $_POST['arrive_days'];
        $operator = $_POST['operator'];
        $d_cost = $_POST['d_cost'];

        if ($at) {
            foreach ($at as $k => $a){
                $ids[] = $k;
                $pr[$k] = number_format($pr[$k], 3, '.', '');

                $pt = bcmul(number_format($a, 3, '.', ''), $pr[$k], 3);
            }
        }

        //判断供应商是否存在
        $oSupplier = $this->app->model('supplier');
        $supplier_ = $oSupplier->dump(array('name'=>$supplier), 'supplier_id');
        
        //日期格式表达式
        //$data_pattrn = '/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/';
        //if(!preg_match($data_pattrn, $arrive)){
            //$this->end(false, '请选择正确的预计到货时间', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        //}

        $poObj = &$this->app->model('po');

        $data['supplier_id'] = $supplier_['supplier_id'];
        $data['operator'] = $operator;//kernel::single('desktop_user')->get_name();
        #采购单创建人
        $data['op_name'] = kernel::single('desktop_user')->get_name();
        $data['po_type'] = $type;
        $data['name'] = $name;
        $data['emergency'] = $emergency;
        $data['purchase_time'] = time();
        $data['branch_id'] = $branch;
        $data['arrive_time'] = $arrive;
        $data['deposit'] = $type=='cash'?0:$price;
        $data['deposit_balance'] = $type=='cash'?0:$price;#预付款
        $data['amount'] = $type=='cash' ? bcadd($total, $d_cost, 3): $total;
        $data['product_cost'] = $total;
        $data['delivery_cost'] = $d_cost;
        if ($memo){
            $op_name = kernel::single('desktop_user')->get_login_name();
            $newmemo = array();
            $newmemo = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$memo);
        }
        $data['memo'] = $newmemo;

        $po_itemObj = &$this->app->model('po_items');
        $pObj = &app::get('ome')->model('products');
        if ($ids)
        foreach ($ids as $i){//插入采购单详情
            $p = $pObj->dump($i, 'barcode,bn,name,spec_info');
            $row['nums'] = $at[$i];
            $row['price'] = $pr[$i];
            $row['bn'] = $p['bn'];
            $row['name'] = $p['name'];
            $data['items'][] = $row;
            $row = null;
        }
        $rs = $poObj->savePo($data);
        if($rs['status'] == 'success'){
            //--生成采购单日志记录
            $log_msg = '生成了编号为:'.$data['po_bn'].'的采购单';
            $opObj = &app::get('ome')->model('operation_log');
            $opObj->write_log('purchase_create@purchase', $data['po_id'], $log_msg);
            $this->end(true, '已完成');
        }else{
            $this->end(false, $rs['msg']!=''?$rs['msg']:'未完成', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        }
    }

    /**
     * 修改采购单
     *
     */
    function editPo($po_id){
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        if (empty($po_id)){
            $this->end(false,'操作出错，请重新操作');
        }

        $poObj = &$this->app->model('po');
        $suObj = &$this->app->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $pObj = &app::get('ome')->model('products');
        $data = $poObj->dump($po_id, '*', array('po_items' => array('*')));

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
        $data['branch_name'] = $br['name'];
        $data['supplier_name'] = $su['name'];
        //到货天数
        $diff_time = $data['arrive_time'] - $data['purchase_time'];
        $data['diff_days'] = floor($diff_time/(24*60*60));
        $this->pagedata['po_items'] = $data['po_items'];
        $data['memo'] = unserialize($data['memo']);
        foreach($data['memo'] as $key =>$v){
            $str = unserialize($v['op_content']);
            if($str){
                $data['memo'][$key]['op_content'] = $str[0]['op_content'];
            }else{
                $data['memo'][$key]['op_content'] = $v['op_content'];
            }
        }
        $this->pagedata['po'] = $data;
        $this->singlepage("admin/purchase/purchase_edit.html");
    }

    function doEdit() {
        $this->begin();
        $po_id = $_POST['po_id'];
        $poObj = &$this->app->model('po');
        $po_itemObj = &$this->app->model('po_items');
        $payObj = &$this->app->model('purchase_payments');
        $data = $poObj->dump($po_id, '*', array('po_items'=>array('*')));

        if ($data['eo_status'] == '3'){
            $this->end(false, '此采购单已完成入库，不允许修改', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
        }
        if ($data['eo_status'] == '4'){
            $this->end(false, '此采购单已取消入库，不允许修改', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
        }
        if ($data['statement'] == '3'){
            $this->end(false, '此采购单已结算，不允许修改', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
        }

        $at = $_POST['at'];
        $pr = $_POST['pr'];
        $d_cost = $_POST['d_cost'];
        $deposit = $_POST['price'];
        $total = 0;
        if(empty($at) || empty($pr)){
            $this->end(false, '采购单中必须有商品', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
        }

        foreach ($data['po_items'] as $v){
            $p_id = $v['product_id'];
            if (empty($at[$p_id])){
                if ($v['status'] != 1){
                    $this->end(false, $v['bn'].':已入库，不能删除', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
                }
                $del_item_id[] = $v;
            }
        }

        if ($at){
            foreach ($at as $k => $a){
                if (!is_numeric($a) || $a < 1 ){
                    $this->end(false, '采购数量必须为数字且大于0', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
                }
                if (!is_numeric($pr[$k]) || $pr[$k] < 0){
                    $this->end(false, '单价必须为数字且大于0', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
                }
                $pi = $po_itemObj->dump(array('po_id'=>$po_id, 'product_id'=>$k));
                if ($pi){
                    if ($a < ($pi['out_num']+$pi['in_num'])){
                        $this->end(false, $pi['bn'].':数量不能小于已入库数量', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
                    }
                    $edit_pi[$k]['item_id'] = $pi['item_id'];
                }
                $edit_pi[$k]['num'] = $a;
                $edit_pi[$k]['price'] = $pr[$k];
                $ids[] = $k;
                $total += $a*$pr[$k];
            }
        }

        if ($data['po_type'] == 'credit'){
            if ($deposit > ($total+$d_cost)){
                $this->end(false, '预付款不能大于总金额', 'index.php?app=purchase&ctl=admin_purchase&act=editPo');
            }
        }

        if ($data['check_status']==2){
            $filter['po_id'] = $po_id;
            $filter['po_type'] = $data['po_type'];
            $pay = $payObj->dump($filter);
        }

        $memo = array();
        $oldmemo = unserialize($data['memo']);
        if ($oldmemo){
            foreach($oldmemo as $k=>$v){
                $memo[] = $v;
            }
        }
        $newmemo = htmlspecialchars($_POST['memo']);
        if ($newmemo){
            $op_name = kernel::single('desktop_user')->get_name();
            $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
        }
        $edit_memo = serialize($memo);

        $poo = array();
        $poo['po_id'] = $po_id;
        $poo['name'] = $_POST['purchase_name'];
        $poo['emergency'] = $_POST['emergency'];
        $poo['operator'] = $_POST['operator'];
        $poo['memo'] = $edit_memo;
        $poo['arrive_time'] = ($_POST['arrive_days']*24*60*60)+time();

        if($data['check_status']==2){
            foreach ($data['po_items'] as $v){
                if (in_array($v['product_id'],$ids)){
                    $poObj->updateBranchProductArriveStore($data['branch_id'], $v['product_id'], $v['num'], '-');
                 }
            }
        }

        if ($del_item_id){
            foreach ($del_item_id as $item){
                $po_itemObj->delete(array('item_id'=>$item['item_id']));
            }
        }

        if ($ids){
            $pObj = &app::get('ome')->model('products');
            foreach ($ids as $i){
                $p = $pObj->dump($i, 'barcode,bn,name,spec_info');
                $row = $edit_pi[$i];
                $row['barcode'] = $p['barcode'];
                $row['po_id'] = $po_id;
                $row['product_id'] = $i;
                $row['price'] = $pr[$i];
                $row['status'] = '1';
                $row['bn'] = $p['bn'];
                $row['name'] = $p['name'];
                $row['spec_info'] = $p['spec_info'];

                $po_itemObj->save($row);
                $row = null;

                if($data['check_status']==2){
                    $poObj->updateBranchProductArriveStore($data['branch_id'], $i, $edit_pi[$i]['num'], '+');
                }
            }
        }

        if ($data['po_type'] == 'cash'){
            if($data['check_status']==2){
                $row['payment_id'] = $pay['payment_id'];
                $row['payable'] = $total+$d_cost;
                $row['deposit'] = $total;
                $row['product_cost'] = $total;
                $row['delivery_cost'] = $d_cost;
            }
            $poo['amount'] = $total+$d_cost;
            $poo['deposit'] = 0;
        }elseif ($data['po_type'] == 'credit') {
            $credit_sObj = &$this->app->model('credit_sheet');
            if($data['check_status']==2){
                $row['payment_id'] = $pay['payment_id'];
                $row['payable'] = $deposit;
                $row['deposit'] = $deposit;
                $row['deposit_balance'] = $deposit;#预付款初始化
                $row['product_cost'] = 0;
                $row['delivery_cost'] = 0;
            }
            $poo['amount'] = $total;
            $poo['deposit'] = empty($deposit)?$data['deposit']:$deposit;
        }

        $poo['delivery_cost'] = $d_cost;
        $poo['product_cost'] = $total;
        $re = $poObj->ExistFinishPurchase($po_id);
        if ($re){
            $poo['eo_status'] = '3';
            if ($data['po_status'] == '1'){
                $poo['po_status'] = '4';
            }
        }

        if($data['check_status']==2){
            $payObj->save($row);
        }
        $poObj->save($poo);

        $eoObj = &$this->app->model('eo');
        $eo_iObj = &$this->app->model('eo_items');
        $eos = $eoObj->getList('eo_id',array('po_id'=>$po_id),0,-1);
        if ($eos){
            foreach ($eos as $it){
                $tmp_num = 0;
                if($data['po_type'] == 'credit'){
                    $cs = $credit_sObj->dump(array('eo_id'=>$it['eo_id']));
                    if ($cs['statement_status'] == 2) continue;
                }
                $eoi = $eo_iObj->getList('*',array('eo_id'=>$it['eo_id']),0,-1);
                if ($eoi){
                    foreach ($eoi as $ei){
                        $num = $ei['entry_num']-$ei['out_num'];
                        $price = $pr[$ei['product_id']];
                        $tmp_num += $num*$price;

                        $eoii['item_id'] = $ei['item_id'];
                        $eoii['purchase_num'] = $at[$ei['product_id']];
                        $eo_iObj->save($eoii);
                        $eoii = null;
                    }
                }
                $eoo['eo_id'] = $it['eo_id'];
                $eoo['amount'] = $tmp_num;

                if($data['po_type'] == 'credit'){
                    $eoo['amount'] = $tmp_num+$d_cost;
                    $css['cs_id'] = $cs['cs_id'];
                    $css['payable'] = $tmp_num+$d_cost;
                    $credit_sObj->save($css);
                }
                $eoObj->save($eoo);
            }
        }

        $log_msg = '修改了编号为:'.$data['po_bn'].'的采购单';
        $opObj = &app::get('ome')->model('operation_log');
        $opObj->write_log('purchase_modify@purchase', $po_id, $log_msg);
        $this->end(true, '已完成');
    }

    /**
     * 入库取消
     *
     *
     */
    function doRefund(){
        $po_id = $_POST['po_id'];
        #$memo = $_POST['memo'];
        #if (!$_POST['memo_flag']) $memo = '';
        $operator = $_POST['operator'];
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        if (empty($po_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        if ($operator == ''){
            $this->end(false,'操作出错，请重新操作');
        }
        $poObj = &$this->app->model('po');
        $po = $poObj->dump($po_id, '*', array('po_items'=>array('*')));
        if ($po['check_status'] != 2){
            $this->end(false,'操作出错，请重新操作');
        }
        if ($po['eo_status']<3){
            //TODO 一期为取消所有未入库的商品，以后会通过POST数据进行入库取消
            //生成退货单与退货明细
            $po_itemObj = &$this->app->model('po_items');
            $returnObj = &$this->app->model('returned_purchase');
            $paymentObj = &$this->app->model('purchase_payments');
            $refundObj = &$this->app->model('purchase_refunds');
            $rp_itemObj = &$this->app->model('returned_purchase_items');

            $return_flag = false;//无任何操作时，不生成退款单标志
            $pay = $paymentObj->dump(array('po_id'=>$po_id), '*');
            if ($po['eo_status'] == '1' && $pay['statement_status'] != '2'){//没有入库并且没有结算付款单
                /*foreach ($po['po_items'] as $item){
                    $num = $item['num']-$item['in_num']-$item['out_num'];
                    $num = $num<0?0:$num;
                    if (($item['status'] == '1' || $item['status'] == '2') && $num != 0){//判断此商品是否可以取消入库
                        $poObj->updateBranchProductArriveStore($po['branch_id'], $item['product_id'],$num,  '-');
                    }
                }*/
                $return_flag = true;
                if ($pay['payment_id']){
                    $paym['payment_id'] = $pay['payment_id'];
                    $paym['statement_status'] = '3';
                    $paymentObj->save($paym);
                }
            }


            //如果采购单已入库或者付款单已结算，生成退货单与退款单
            $return['supplier_id'] = $po['supplier_id'];
            $return['operator'] = $operator;//kernel::single('desktop_user')->get_name();
            $return['po_type'] = $po['po_type'];
            $return['purchase_time'] = $po['purchase_time'];
            $return['returned_time'] = time();
            $return['branch_id'] = $po['branch_id'];
            $return['arrive_time'] = $po['arrive_time'];
            $return['amount'] = 0;
            $return['rp_type'] = 'po';
            $return['object_id'] = $po_id;

            $rp_id = $returnObj->createReturnPurchase($return);//生成退货单

            $po_items = $po['po_items'];//$poObj->getPoItemsByPoId($po_id);
            $money = 0;
            if ($po_items)
            foreach ($po_items as $item){
                $num = $item['num']-$item['in_num']-$item['out_num'];
                $num = $num<0?0:$num;
                if (($item['status'] == '1' || $item['status'] == '2') && $num != 0){//判断此商品是否可以取消入库
                    $row['rp_id'] = $rp_id;
                    $row['product_id'] = $item['product_id'];
                    $row['num'] = $num;
                    $row['price'] = $item['price'];
                    $money += $item['price']*$num;
                    $row['bn'] = $item['bn'];
                    $row['name'] = $item['name'];
                    $row['spec_info'] = $item['spec_info'];

                    $rp_itemObj->save($row);
                    $row = null;
                    $poObj->updateBranchProductArriveStore($po['branch_id'], $item['product_id'],$num,  '-');

                    $r['item_id'] = $item['item_id'];
                    $r['out_num'] = $item['out_num']+$num;
                    $r['status'] = ($r['out_num']+$item['in_num'])>=$item['num']?'3':$item['status'];

                    $po_itemObj->save($r);
                    $r = null;
                }
            }
            $data['rp_id'] = $rp_id;
            $data['amount'] = $money;
            $data['product_cost'] = $money;

            $returnObj->save($data);//更新退货单
            //日志备注
            $log_msg .= '<br/>生成了一张编号为：'.$return['rp_bn'].'的退货单';
            if ($return_flag==false){
                //生成退款单
                $refund['add_time'] = time();
                $refund['po_type'] = $po['po_type'];
                $refund['delivery_cost'] = 0;
                $refund['type'] = 'po';
                $refund['rp_id'] = $rp_id;
                $refund['supplier_id'] = $po['supplier_id'];
                if ($po['po_type'] == 'cash'){
                    $refund['refund'] = $money;
                    $refund['product_cost'] = $money;

                }elseif ($po['po_type'] == 'credit' && $po['deposit_balance'] != 0){
                    $refund['refund'] = $po['deposit_balance'];
                    $refund['product_cost'] = 0;
                }
                $refund_id = $refundObj->createRefund($refund);

                $poo['amount'] = $po['amount'] - $money;
                $poo['product_cost'] = $po['product_cost'] - $money;
                $poo['deposit_balance'] = 0;
            }else {
                $poo['amount'] = 0;
                $poo['product_cost'] = 0;
            }
            $poo['po_id'] = $po_id;
            if ($_POST['memo']) {
                $op_name = kernel::single('desktop_user')->get_name();
                $oldmemo= unserialize($po['memo']);
                if ($oldmemo) {
                    foreach($oldmemo as $k=>$v){
                        $memo[] = $v;
                    }
                }
                #置为一个有意义的终止键
                $memo['doRefund'] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>htmlspecialchars($_POST['memo']));
                $poo['memo'] = serialize($memo);
            }
            
            if ($po['po_status'] == '1'){
                $poo['po_status'] = '2';//入库取消
            }

            if ($po['eo_status'] == '2'){
                $poo['eo_status'] = '3';//已入库
            }elseif ($po['eo_status'] == '1') {
                $poo['eo_status'] = '4';//未入库
            }
//            $poObj->save($poo);
            /**Begin liaoyu message: 修改为update方式**/
            $filter = array('po_id' => $po['po_id'], 'eo_status|noequal' => '3');
            if (array_key_exists('po_id', $poo)) {
                unset($poo['po_id']);
            }
            $result = $poObj->update($poo, $filter);
            if (empty($result)) {
                $this->end(false, '此采购单已完成入库，请走采购退货流程');
            }
            $poo['po_id'] = $po['po_id'];
            /**End liaoyu message: 修改为update方式**/

            //--采购单入库取消日志记录
            if ($refund_id){
               $refund_bn = $refundObj->dump($refund_id,'refund_bn');
               $log_msg = '<br/>生成了一张编号为：'.$refund_bn['refund_bn'].'的退款单';
            }
            $log_msg2 = '对采购单编号为:'.$po['po_bn'].'进行了入库取消<br/>';
            $opObj = &app::get('ome')->model('operation_log');
            $opObj->write_log('purchase_cancel@purchase', $po_id, $log_msg2.$log_msg);

            $this->end(true, '入库取消已完成');
        }else {
            $this->end(false, '此采购单已完成入库，请走采购退货流程');
        }
    }

    /**
     * 保存详情
     *
     */
    function doDetail(){
        $this->begin('index.php?app=purchase&ctl=admin_purchase');
        if (empty($_POST['id'])){
            $this->end(false, '操作出错，请重新操作');
        }
        if ($_POST['memo'] == ''){
            $this->end(true, '操作完成');
        }

        $poObj = &$this->app->model('po');
        $po['po_id'] = $_POST['id'];
        $po['memo'] = $_POST['oldmemo'].'<br/>'.$_POST['memo'].'  &nbsp;&nbsp;('.date('Y-m-d H:i',time()).' by '.kernel::single('desktop_user')->get_name().')';

        $poObj->save($po);
        $this->end(true, '操作成功');
    }

    /*
     * 追加备注 append_memo
     */
    function append_memo(){

        $poObj = &$this->app->model('po');
        $po['po_id'] = $_POST['id'];
        if ($_POST['oldmemo']){
            $oldmemo = $_POST['oldmemo'].'<br/>';
        }
        $memo = $oldmemo.$_POST['memo'].'  &nbsp;&nbsp;('.date('Y-m-d H:i',time()).' by '.kernel::single('desktop_user')->get_name().')';
        $po['memo'] = $memo;
        $poObj->save($po);
        echo $memo;
    }

    /**
     * 打印采购单
     *
     * @param int $po_id
     */
    function printItem($po_id,$type='po'){
        $poObj = &$this->app->model('po');
        $suObj = &$this->app->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $prObj = &app::get('ome')->model('products');
        $poo = $poObj->dump($po_id, '*', array('po_items'=>array('*')));
        #检测货号、规格、商品名是否变化
        foreach($poo['po_items'] as $key=>$product_items){
           $last_product_info = $prObj->dump($product_items['product_id'],'bn,name,spec_info,price');
           #检测货号是否变化
           if(strcasecmp($product_items['bn'],$last_product_info['bn']) != 0){
               $product_items['bn'] = $product_items['bn'].'('.$last_product_info['bn'].')';
           }
           #检测规格是否变化
           if(strcasecmp($product_items['spec_info'],$last_product_info['spec_info']) != 0){
               if(empty($product_items['spec_info'])){
                   #如果原来没有规格值，则直接显示该商品最新的规格值
                   $product_items['spec_info'] = $last_product_info['spec_info'];
               }else{
                   $product_items['spec_info'] = $product_items['spec_info'].'('.$last_product_info['spec_info'].')';
               }
           }
           #检测商品名称是否变化
           if(strcasecmp($product_items['name'],$last_product_info['name'])!=0){
               $product_items['name'] = $product_items['name'].'('.$last_product_info['name'].')';
           }
           $poo['po_items'][$key] = $product_items;
           #增加吊牌价
           $poo['po_items'][$key]['sale_price'] = $last_product_info['price']['price']['price'];
        }
        $su = $suObj->dump($poo['supplier_id'],'name,telphone,addr,operator');
        $bran = $brObj->dump($poo['branch_id'],'name');

        $this->pagedata['type'] = $type;
        $poo['supplier'] = $su['name'];
        $poo['telphone'] = $su['telphone'];//供应商电话
        $poo['addr'] = $su['addr'];//供应商地址
        
        #打印页面显示的是 “采购员” 不是 “供应商的采购员” ExBOY
        #$poo['operator'] = $su['operator'];//供应商的采购员
        
        $poo['branch'] = $bran['name'];
        $poo['memo'] = unserialize($poo['memo']);
        $this->pagedata['po'] = $poo;
        $this->pagedata['time'] = time();
        $this->pagedata['base_dir'] = kernel::base_url();

        # 改用新打印模板机制 chenping
        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],'purchase',$this);
        /*
        $this->_systmpl = &app::get('ome')->model('print_tmpl_diy');
        $this->_systmpl->singlepage('purchase','admin/purchase/purchase_print',$this->pagedata);
        $this->display("admin/prints.html");
        */
    }

    function cancel($po_id, $type='confirm'){

        //获取采购单供应商经办人/负责人
        $oPo = $this->app->model('po');
        $po = $oPo->dump($po_id, 'supplier_id');
        $oSupplier = &$this->app->model('supplier');
        $supplier = $oSupplier->dump($po['supplier_id'], 'operator');
        //if (!$supplier['operator']) $supplier['operator'] = '未知';

        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();

        //print_r($po_id);
        $this->pagedata['type'] = $type;
        $this->pagedata['id'] = $po_id;
        $this->display("admin/purchase/purchase_cancel.html");
    }

    function addSame($po_id){
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        if (empty($po_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        $suObj = &$this->app->model('supplier');
        $supp = $suObj->getList('supplier_id, name','',0,-1);

        $brObj = &app::get('ome')->model('branch');
        $row = $brObj->getList('branch_id, name','',0,-1);

        $poObj = &$this->app->model('po');
        $pObj = &app::get('ome')->model('products');
        $data = $poObj->dump($po_id, '*', array('po_items' => array('*')));

        //当前供应商
        $supplier_detail = $suObj->dump($data['supplier_id'], 'supplier_id,name,arrive_days');

        //获取设置的采购方式
        $po_type = &app::get('ome')->getConf('purchase.po_type');
        if (!$po_type) $po_type = 'credit';
        $this->pagedata['po_type'] = $po_type;

        //获取仓库模式
        //$branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        //if (!$branch_mode){
            //$branch_mode = 'single';
        //}

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $brObj->getBranchByUser();
        }
        $this->pagedata['branch_list'] = $branch_list;
        $is_super = 1;
        $this->pagedata['is_super'] = $is_super;

        $diff_time = $data['arrive_time'] - $data['purchase_time'];
        $data['diff_days'] = floor($diff_time/(24*60*60));//print_r($data);die;

        $this->pagedata['branch_mode'] = $branch_mode;
        $this->pagedata['supplier'] = $supp;
        $this->pagedata['supplier_detail'] = $supplier_detail;
        $this->pagedata['branch'] = $row;
        $this->pagedata['po_items'] = $data['po_items'];
        $this->pagedata['po'] = $data;
        $this->pagedata['cur_date'] = date('Ymd',time()).'采购单';
        $this->singlepage("admin/purchase/purchase_addsame.html");
    }

    function doSame(){
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        $at = $_POST['at'];
        $pr = $_POST['pr'];
        $type = $_POST['type'];
        $name = $_POST['purchase_name'];
        $emergency = $_POST['emergency'];
        $supplier = $_POST['supplier'];
        $branch = $_POST['branch'];
        $price = $_POST['price'];
        $memo = $_POST['memo'];
        $arrive = ($_POST['arrive_days']*24*60*60)+time();
        $operator = $_POST['operator'];
        $d_cost = $_POST['d_cost'];
        $total = 0;

        if(empty($at) || empty($pr)){
            $this->end(false, '采购单中必须有商品', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        }
        if ($at){
            foreach ($at as $k => $a){
                if(!is_numeric($a) || $a < 1 ){
                    $this->end(false, '采购数量必须为数字且大于0', 'index.php?app=purchase&ctl=admin_purchase&act=add');
                }
                if (!is_numeric($pr[$k]) || $pr[$k] < 0 ){
                    $this->end(false, '单价必须为数字且大于0', 'index.php?app=purchase&ctl=admin_purchase&act=add');
                }
                $ids[] = $k;
                $total += $a*$pr[$k];
            }
        }

        if ($type == 'credit'){
            if ($_POST['price'] == ''){
                $price = 0;
            }
        }

        if ($branch == ''){
            $this->end(false, '请选择仓库', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        }
        if ($price != '' && !is_numeric($price)){
            $this->end(false, '预付款必须为数字', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        }
        if ($price > $total){
            $this->end(false, '预付款金额不得大于商品总额', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        }
        //判断供应商是否存在
        $oSupplier = $this->app->model('supplier');
        $supplier = $oSupplier->dump(array('name'=>$supplier), 'supplier_id');
        if (!$supplier['supplier_id']){
            $this->end(false, '输入的供应商不存在！', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        }
        $supplier = $_POST['supplier_id'];
        if($arrive == ''){
            $this->end(false, '请输入预计到货天数', 'index.php?app=purchase&ctl=admin_purchase&act=add');
        }
        $poObj = &$this->app->model('po');
        $po_bn = $poObj->gen_id();
        $data['po_bn'] = $po_bn;
        $data['supplier_id'] = $supplier;
        $data['operator'] = $operator;//kernel::single('desktop_user')->get_name();
        $data['po_type'] = $type;
        $data['name'] = $name;
        $data['emergency'] = $emergency;
        $data['purchase_time'] = time();
        $data['branch_id'] = $branch;
        $data['arrive_time'] = $arrive;
        $data['deposit'] = $type=='cash' ? 0 : $price;
        $data['deposit_balance'] = $type=='cash' ? 0 : $price;

        $data['amount'] = $type=='cash'?$total+$d_cost:$total;
        $data['delivery_cost'] = $d_cost;
        $data['product_cost'] = $total;
        if ($memo){
            $op_name = kernel::single('desktop_user')->get_login_name();
            $newmemo = array();
            $newmemo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$memo);
        }
        $data['memo'] = serialize($newmemo);

        $rs = $poObj->save($data);
        if ($rs){
            $po_id = $data['po_id'];
            $po_itemObj = &$this->app->model('po_items');
            $pObj = &app::get('ome')->model('products');
            if ($ids){
                foreach ($ids as $i){//插入采购单详情
                    $p = $pObj->dump($i, 'barcode,bn,name,spec_info');
                    $row['barcode'] = $p['barcode'];
                    $row['po_id'] = $po_id;
                    $row['product_id'] = $i;
                    $row['num'] = $at[$i];
                    $row['in_num'] = 0;
                    $row['out_num'] = 0;
                    $row['price'] = $pr[$i];
                    $row['status'] = '1';
                    $row['bn'] = $p['bn'];
                    $row['name'] = $p['name'];
                    $row['spec_info'] = $p['spec_info'];

                    $po_itemObj->save($row);
                    $row = null;
                }
            }
            //--生成采购单日志记录
            $payment_log = $type=='cash' ? '现款' : '预付款';
            $log_msg = '生成了编号为:'.$po_bn.'的采购单';
            $opObj = &app::get('ome')->model('operation_log');
            $opObj->write_log('purchase_create@purchase', $po_id, $log_msg);

            $this->end(true, '已完成');
        }
        $this->end(true, '未完成', 'index.php?app=purchase&ctl=admin_purchase&act=add');
    }


    /**
    根据条码查询商品详情
    */
    function getProduct(){
        
        $pro_barcode= trim($_POST['barcode']);
        $supplier_id = $_POST['supplier_id'];
        
        if($pro_barcode){
            $filter = array(
               'barcode'=>$pro_barcode
           );
        
        $pObj = &app::get('ome')->model('products');
        
        $data = $pObj->getList('visibility,product_id,bn,name,price,barcode,spec_info',$filter,0,-1);
       

        if (!empty($data)){
            foreach ($data as $k => $item){
                $item['num'] = 1;
                if($supplier_id > 0){
                    $item['price'] = $this->app->model('po')->getPurchsePriceBySupplierId($supplier_id, $item['product_id'], 'desc');
                    if (!$item['price']){
                        $item['price'] = 0;
                    }
                }else{
                    //
                    $product = $pObj->dump(array('product_id'=>$item['product_id']),'cost');

                    $item['price'] = $product['price']['cost']['price'];
                }
                
                $rows[] = $item;
            }
            echo json_encode($rows);
        }
        }
    }


    /*
     * 供应商查找 
     */
    function getSupplier(){
        
        $name = $_GET['name'];
        if ($name){
            $supplier = $this->app->model('supplier');
            $data = $supplier->getSupplier($name);
            
            echo "window.autocompleter_json=".json_encode($data);
        }
    }
    
   /*
     * 供应商查找 by id
     */
    function getSupplierById(){
  
        $supplier_id = $_POST['id'];
        if ($supplier_id){
            $supplier = $this->app->model('supplier');
            $data = $supplier->dump(array('supplier_id'=>$supplier_id), 'supplier_id,name');

            //echo json_encode($data);
            echo "{id:'".$data['supplier_id']."',name:'".$data['name']."'}";
        }
    }
    #取消采购单
    function canclePo($po_id){
        if(!$po_id){
            die("采购单据号传递错误！");
        }
        $this->pagedata['po_id'] = $po_id;
        $this->display("admin/purchase/do_cancel.html");
    }
    function doCanclePo($po_id){
       if(!$po_id){
           die("采购单据号传递错误！");
       }
        $poObj = &$this->app->model('po');
        $this->begin('index.php?app=purchase&ctl=admin_purchase&act=index');
        if( $poObj->delete(array('po_id'=>$po_id))){
            $this->end(true, $this->app->_('取消成功'));
        }else{
            $this->end(false, $this->app->_('取消失败'));
        } 
    }
        
}