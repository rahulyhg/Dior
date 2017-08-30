<?php
class taoguaninventory_ctl_admin_inventory extends desktop_controller{

    var $name = "盘点表管理";
    var $workground = "storage_center";

    /*
     *
     */
    function index(){
        $params = array(
                        'title'=>'盘点导入',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>true,
                        'use_buildin_filter'=>true,
						'orderBy'=>'inventory_id DESC',
                        //'finder_aliasname'=>'inventory_record_finder',
                        //'finder_cols'=>'inventory_date,inventory_name,inventory_checker,import_status,update_status,is_create,confirm_status',
                    );
        $this->finder('taoguaninventory_mdl_inventory', $params);
    }

    /*
     * 盘点导入
     */
    function import(){
        $str = '';
        /*if ($instance = kernel::service('ome.service.getbranchview')){
            $branch_id = $instance->getBranchView($_GET['branch_id'], 'index.php?app=taoguaninventory&ctl=admin_inventory&act=import', '盘点导入', 'GET');
        }
        if ($branch_id){
            $oBranch = &app::get('ome')->model('branch');
            $branch = $oBranch->dump($branch_id);
            $str = "(".$branch['name'].")";
        }*/
        $params = array(
                        'title'=>'盘点确认'.$str,
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>true,
                        'use_buildin_filter'=>true,
						'orderBy'=>'inventory_id DESC',
                        //'finder_aliasname'=>'inventory_record_finder',
                        //'finder_cols'=>'inventory_date,inventory_name,inventory_checker,import_status,update_status,is_create,confirm_status',
                    );
        //if ($branch_id) $params['base_filter']['branch_id'] = $branch_id;
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

        $this->finder('taoguaninventory_mdl_inventory', $params);
    }

    /*
    * 盘点导出,$page页码， $f1-5为过滤条件
    */
    function export(){

        //$page = $page ? $page : 1;
        //$pagelimit = 10;

        //获取仓库模式
//        $branch_mode = &app::get('ome')->getConf('ome.branch.mode');
//        if (!$branch_mode){
//            $branch_mode = 'single';
//        }
//        if ($branch_mode=='single'){
//            //获取第一个仓库值与ID
//            $oBranch = &app::get('ome')->model('branch');
//            $branch   = $oBranch->getList('branch_id, name','',0,1);
//            $data['branch_id'] = $branch[0]['branch_id'];
//        }

        $oBranch = &app::get('ome')->model('branch');
        $branch   = $oBranch->getList('branch_id, name','',0,-1);
        #获取品牌列表
        $oBrand = &app::get('ome')->model('brand');
        $brand_list   = $oBrand->getList('brand_id,brand_name','',0,-1);
        $this->pagedata['brand_list'] = $brand_list;
        unset($brand_list);
        #获取商品类型列表
        $objGtype = &app::get('ome')->model('goods_type');
        $this->pagedata['gtype'] = $objGtype->getList('*','',0,-1);
        unset($objGtype);
        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $oBranch->getBranchByUser();
        }
        $this->pagedata['branch_list']   = $branch_list;
        $this->pagedata['is_super']   = $is_super;

        //读取仓库的货品信息
        //$oInventory = $this->app->model('inventory');
        //$inventory_detail = $oInventory->getProduct($data, $pagelimit*($page-1), $pagelimit);

        $this->pagedata['branch'] = $branch;
        $this->pagedata['op_name'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['inventory_name'] = date('m月d日',time()).'盘点表';
        $this->page("admin/inventory/export.html");
    }

    /*
    * 盘点导出，Json数据返回，为解决避免一次性抛出大量数据而改用动态翻页方法
    */
    function inventoryPreview($page=1){
        $data = $_POST;
        $page = $page ? $page : 1;
        $pagelimit = 12;
        $data['branch_id'] = $_POST['branch_id'];
        //读取仓库的货品信息
        $oInventory = $this->app->model('inventory');
        $export_type = $data['export_type'];
        //getPosList
        if($export_type==1){
            $inventory_detail = $oInventory->getProduct($data, $pagelimit*($page-1), $pagelimit);
        }else{
            $inventory_detail = $oInventory->getPosList($data, $pagelimit*($page-1), $pagelimit);
        }
        $count = $inventory_detail['count'];

        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>'?page=%d'
        ));
        $this->pagedata['pager'] = $pager;
        unset($inventory_detail['count']);
        $this->pagedata['inventory'] = $inventory_detail;
        $this->pagedata['total_page'] = $total_page;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['count'] = $count;
        $this->pagedata['cur_page'] = $page;
        return $this->display("admin/inventory/inventory_items_div.html");
    }

   /*
     * 盘点损益确认finder
     */
    function confirm($confirm_status=null){
        $this->workground = "finance_center";
        $base_filter['confirm_status'] = $confirm_status;
        $params = array(
                        'title'=>'盘点损益确认',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>false,
                        'base_filter' => $base_filter,
                        //'finder_aliasname'=>'inventory_confirm_finder',
                        //'finder_cols'=>'inventory_date,inventory_name,confirm_status,inventory_checker,branch_name,inventory_type,warehousing_dept',
                    );
        $this->finder('taoguaninventory_mdl_inventory', $params);
    }

    /*
     * 盘点损益确认操作:confirm_detail
     */
    function confirm_detail($inventory_id=null, $page=1){

        $inventory_id = intval($inventory_id);
        $page = intval($page);
        $page = $page ? $page : 1;
        $pagelimit = 12;

        if ($_GET['view']=='true' and $_GET['from']=='counterTables'){
            $this->workground = "invoice_center";
        }
        elseif ($_GET['view']=='true' and $_GET['from']<>'counterTables'){
            $this->workground = "storage_center";
        }
        else {
            $this->workground = "finance_center";
        }
        $oInventory = $this->app->model('inventory');
        $oInventory_items = $this->app->model('inventory_items');
        $inventory_detail = $oInventory->dump($inventory_id, '*');

        //盘点明细
        $inventory_items = $oInventory_items->getList('*', array('inventory_id'=>$inventory_id), $pagelimit*($page-1), $pagelimit);
        //总计
        $total = $oInventory->getInventoryTotal($inventory_id);
        $inventory_detail['difference'] = $total[0]['difference'];
        /*更新盘点表差异金额*/
        $inv_up_data = array();
        $inv_up_data['difference'] = $total[0]['difference'];
        $inv_up_data['inventory_id'] = $inventory_id;
        $oInventory->save($inv_up_data);
        /**/
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
            'link'=>'index.php?app=taoguaninventory&ctl=admin_inventory&act=confirm_detail&p[0]='.$inventory_id.'&p[1]=%d&view='.$_GET['view'].'&from='.$_GET['from'],
        ));
        $this->pagedata['pager'] = $pager;
        $this->pagedata['detail'] = $inventory_detail;
        $this->pagedata['items'] = $inventory_items;
        $this->pagedata['inventory_id'] = $inventory_id;
        $this->pagedata['subtotal'] = $subtotal;#小计
        $this->pagedata['total'] = $total[0];#总计
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['view'] = $_GET['view'];
        $this->page("admin/inventory/confirm.html");
    }

    /*
     * 确认操作
     */
    function doconfirm(){

        $this->workground = "finance_center";
        $oInventory = $this->app->model('inventory');
        $data = $_POST;
        $inventory_id = intval($data['inventory_id']);

        if ($data['doSubmit']=='true'){
            $gotourl = 'index.php?app=taoguaninventory&ctl=admin_inventory&act=doconfirm&p[0]='.$inventory_id;
            $this->begin($gotourl);
            $datas['inventory_id'] = $inventory_id;
            $datas['confirm_status'] = '2';
            $datas['confirm_op'] = kernel::single('desktop_user')->get_name();
            $datas['confirm_time'] = time();
            $result = $oInventory->confirm($datas);

            $endurl = 'index.php?app=taoguaninventory&ctl=admin_inventory&act=confirm&p[0]=1';
            if ($result) $msg = '成功';else $msg = '失败';
            $this->end($result, '确认'.$msg, $endurl);
        }
    }

   /*
     * 损益汇总表
     */
    function counterTables($page=1, $begin_date=null, $end_date=null){

        $page = intval($page);
        $page = $page ? $page : 1;
        $pagelimit = 8;
        $begin_date = $_POST['begin_date'] ? $_POST['begin_date'] : $begin_date;
        $end_date = $_POST['end_date'] ? $_POST['end_date'] : $end_date;
        if ($begin_date) {
            $begin_date = strtotime($begin_date);
            $begin_date = date("Y-m-d",$begin_date);
            $filter['begin_date'] = $begin_date;
        }
        if ($end_date) {
            $end_date = strtotime($end_date);
            $end_date = date("Y-m-d",$end_date);
            $filter['end_date'] = $end_date;
        }
        $this->workground = "invoice_center";
        $oInventory = $this->app->model('inventory');

        //盘点明细
        $inventory_list = $oInventory->getInventoryList('inventory_id,inventory_date,inventory_checker,difference', $filter, $pagelimit*($page-1), $pagelimit);

        //总计
        $total = $oInventory->getTotal($begin_date, $end_date);

        if ($inventory_list)
        foreach ($inventory_list as $k=>$v){
             //小计
             $subtotal['total_shortage_over'] += $v['difference'];
        }
        $count = $total['count'];
//        $this->pagedata['pager'] = array(
//            'current'=>$page,
//            'total'=>ceil($count/$pagelimit),
//            'link'=>'index.php?app=taoguaninventory&ctl=admin_inventory&act=counterTables&p[0]=%d&p[1]='.$begin_date.'&p[2]='.$end_date,
//        );
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=taoguaninventory&ctl=admin_inventory&act=counterTables&p[0]=%d&p[1]='.$begin_date.'&p[2]='.$end_date,
        ));
        $this->pagedata['pager'] = $pager;
        $this->pagedata['list'] = $inventory_list;
        $this->pagedata['subtotal'] = $subtotal;#小计
        $this->pagedata['total'] = $total[0];#总计
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->pagedata['begin_date'] = $begin_date;
        $this->pagedata['end_date'] = $end_date;
        $this->page("admin/inventory/counterTables.html");
    }

    function getProduct(){
        #实时检测盘点状态,是否属于未完成
        if(isset($_POST['inventory_id'])){
            $ivObj = &$this->app->model('inventory');
            $confirm_status = $ivObj->dump($_POST['inventory_id'],'confirm_status'); 
            if($confirm_status['confirm_status'] != '1'){
                #不属于未确认的盘点单，不能加入
                echo json_encode(array('enable'=> 'false'));exit;
            }
        }
        if ($_POST['barcode']){
            $barcode = trim($_POST['barcode']);
            $branch_id = $_POST['branch_id'];
            $selecttype= $_POST['selecttype'];
            if (!$branch_id) exit('false');
            $ivObj = &$this->app->model('inventory');
            if($selecttype=='barcode'){
                $data = $ivObj->getProductbybarcode($branch_id, $barcode);
            }else if($selecttype=='bn'){
                $data = $ivObj->getProductbybn($branch_id, $barcode);
            }
            if (!$data) exit('false');

            $data['asso_pos'] = $ivObj->getProductPos($data['product_id']);
            if(!$data['num']) $data['num'] = 0;

            echo json_encode($data);
        }else {
            exit('false');
        }
    }

    function exsitPosition(){
        if ($_POST['pos_name']){
            $pos_name = trim($_POST['pos_name']);
            $branch_id = $_POST['branch_id'];
            if (!$branch_id) exit("false");
            $bpObj = &app::get('ome')->model('branch_pos');
            $bp = $bpObj->dump(array('store_position'=>$pos_name,'branch_id'=>$branch_id),'pos_id');
            if (!$bp) exit("false");
            echo json_encode($bp);
        }else {
            echo "false";
        }
    }

    function toInventory(){
        $branch_id = $_POST['branch_id'];
        if (!$branch_id) $branch_id = $_GET['branch_id'];
        if (!$branch_id){
            $this->begin("index.php?app=taoguaninventory&ctl=admin_inventory&act=online");
            $this->end(false,'请选择仓库');
        }else {
            $brObj = &app::get('ome')->model('branch');
            $branch = $brObj->dump($branch_id);

            $this->pagedata['branch_id']    = $branch_id;
            $this->pagedata['branch_name']  = $branch['name'];
            $this->pagedata['op_name']      = kernel::single('desktop_user')->get_name();
            $this->pagedata['date']         = date("Y年m月d日");
            $this->page("admin/inventory/inventory_online.html");
        }
    }

    function online(){
        $brObj = &app::get('ome')->model('branch');
        $branch_list = $brObj->getBranchByUser();
        $branch_mode = &app::get('ome')->getConf('ome.branch.mode');

        $is_super = kernel::single('desktop_user')->is_super();

        if ($is_super){
            $branch_rows = $brObj->getList('branch_id,name','',0,-1);
            if (count($branch_rows) == 1){

                $this->pagedata['branch_id']    = $branch_rows[0]['branch_id'];
                $this->pagedata['branch_name']  = $branch_rows[0]['name'];
                $this->pagedata['op_name']      = kernel::single('desktop_user')->get_name();
                $this->pagedata['date']         = date("Y年m月d日");
                $this->page("admin/inventory/inventory_online.html");
            }else {
                $this->pagedata['branch_list'] = $branch_rows;
                $this->page("admin/inventory/online.html");
            }
        }else {
            if (count($branch_list) == 1){

                $this->pagedata['branch_id']    = $branch_list[0]['branch_id'];
                $this->pagedata['branch_name']  = $branch_list[0]['name'];
                $this->pagedata['op_name']      = kernel::single('desktop_user')->get_name();
                $this->pagedata['date']         = date("Y年m月d日");
                $this->page("admin/inventory/inventory_online.html");
            }else {
                $this->pagedata['branch_list'] = $branch_list;
                $this->page("admin/inventory/online.html");
            }
        }
    }

    /*
     * 盘点更新
     */
    function updateBranchProductPos(){
        $this->begin("index.php?app=taoguaninventory&ctl=admin_inventory&act=updateBranchProductPos");
        if (!$_POST['branch_id']){
            $this->end(false, '无仓库信息');
        }
        if (!$_POST['product_id']){
            $this->end(false, '无商品信息');
        }

        if ($_POST['number'] == 0 || !empty($_POST['number'])){
            if (!is_numeric($_POST['number']) || intval($_POST['number']) < 0){
                $this->end(false, '请输入自然数');
            }
        }

    	if (kernel::single('taoguaninventory_inventory')->save_inventory($_POST,$msg)){
            $this->end(true, '盘点完成', 'index.php?app=taoguaninventory&ctl=admin_inventory&act=toInventory&branch_id='.$_POST['branch_id']);
        }else {
            //$msg['delivery_bn'] = $dly['delivery_bn'];
            $this->end(false, '盘点失败', '', array('msg'=>$msg));
        }

    }

    function existDeletePosRelation($branch_id=0, $product_id=0){
        if ($_POST){
            $branch_id = $_POST['branch_id'];
            $product_id = $_POST['product_id'];
        }
        $bppObj = &app::get('ome')->model('branch_product_pos');
        $invObj = &$this->app->model('inventory');
        $bpp = $bppObj->getList('pos_id,store',array('product_id'=>$product_id,'branch_id'=>$branch_id),0,-1);
        if ($bpp){
            foreach ($bpp as $v){
                if ($invObj->existPosNotProcess($v['pos_id'])){
                    exit('false');
                }
            }
        }
        echo 'true';
    }

	function save_inventory_confirm(){
        $this->begin('index.php?app=taoguaninventory&ctl=admin_inventory');
        $result = kernel::single('taoguaninventory_inventory')->check_inventory($_POST,$msg);

    	if ($result == 'success'){
            $this->end(true, '盘点确认成功', 'index.php?app=taoguaninventory&ctl=admin_inventory&act=import');
        }else {
            //$msg['delivery_bn'] = $dly['delivery_bn'];
            $this->end(false, '盘点确认失败:'.$result, '', array('msg'=>$msg));
        }
    }
}

?>
