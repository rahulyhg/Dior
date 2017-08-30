<?php
class console_ctl_admin_stockdump extends desktop_controller{

    var $name = "库内转储";
    var $workground = "console_center";
    public function index(){
        
        $this->title = '库内转储';
        $params = array(
            'title'=>$this->title,
            'base_filter' =>$base_filter,
            'actions' => array(
                array(
                    'label' => '新建转储单',
                    'icon' => 'add.gif',
                    'href' => 'index.php?app=console&ctl=admin_stockdump&act=add',
                    'target' => '_blank',
                ),
                array(
                        'label' => '导出模版',
                        'icon' => 'add.gif',
                        'href' => 'index.php?app=console&ctl=admin_stockdump&act=export_template',
                        'target' => '_blank',
                    ),
            ),
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
        );
        
        $branch_id= &app::get('ome')->model('branch')->getBranchByUser(true);
        if($branch_id){
            $where_branch_id = '('.implode(',', $branch_id).')';
            $params['base_filter']['filter_sql'] = "(from_branch_id in ".$where_branch_id." or to_branch_id in ".$where_branch_id.")";
        }
        $this->finder('console_mdl_stockdump',$params);
    }




   /*
    * 新建调拨单
    */
    public function add(){
        $OBranch = &app::get('ome')->model('branch');

        $branch_id= $OBranch->getBranchByUser(true);
        if($branch_id){
            $where= " AND wb.branch_id in(".implode(',', $branch_id).")";
        }
        #获取非自有WMS_id
        $wms_list = kernel::single('console_goodssync')->get_wms_list('selfwms','notequal');
        if($wms_list){
            $sql = "SELECT wb.branch_bn,wb.name,wb.branch_id FROM sdb_ome_branch as wb  WHERE wb.wms_id != ''".$where." AND wb.wms_id in(".implode(',',$wms_list).")";
            $branch = kernel::database()->select($sql);
        }
        $OProducts= &app::get('ome')->model('products');
        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['branch'] = $branch ;
        $this->singlepage("admin/stockdump/add.html");
    }

    /*
     * 库存入库单保存
     */
    public function do_save(){
        $url = 'index.php?app=console&ctl=admin_stockdump&act=add';
        $this->begin($url);
        if(empty($_POST['product_id'])){
            $this->end(false,'请添加转储商品');
        }

        $oStockdump = $this->app->model('stockdump');
        $from_branch_id = $_POST['from_branch_id'];
        $to_branch_id = $_POST['to_branch_id'];
        $memo = $_POST['memo'];
        $num = $_POST['num'];
        $ckid = $_POST['ckid'];
        $product_id = $_POST['product_id'];
        $appro_price = $_POST['to_stock_price'];
        
        $op_name = kernel::single('desktop_user')->get_login_name();
        $options = array(
            #'type' => 600,
            #'otype' => 2,
            'op_name' => $op_name,
            #'in_status' => 8,
            'from_branch_id' => $from_branch_id,
            'to_branch_id' => $to_branch_id,
            'memo' => $memo,
        );
        if(empty($ckid)){
           $this->end(false,'请勾选您需要操作的商品',$url);
        }

        //选择商品数量判断
        $spmn = console_stock::SELECT_PRODUCT_MAX_NUM;
        if(count($ckid)>$spmn){
            $this->end(false,'选择转储商品最大数量不能大于'.$spmn.'!');
        }

        if($from_branch_id=='' && $to_branch_id==''){
            $this->end(false,'调出仓库和调入仓库必须选择',$url);
        }
        $pStockObj = kernel::single('console_stock_products');

        foreach($ckid as $k=>$v){
           if(intval($num[$v])<=0){
               $this->end(false,($k+1).'行数量应大于0',$url);
           }
          if(!is_numeric($appro_price[$v]) || intval($appro_price[$v])<0){
               $this->end(false,($k+1).'行金额格式错误',$url);
           }

           //判断选择商品库存是否充足
           $usable_store = $pStockObj->get_branch_usable_store($from_branch_id,$product_id[$v]);
           if($usable_store < $num[$v]){
                $this->end(false,($k+1).'行仓库可用库存不足!','index.php?app=console&ctl=admin_stockdump&act=add');
           }
           
           $adata[$k] = array(
               'product_id'=>$product_id[$v],
               'num'=>$num[$v],
               'appro_price'=>$appro_price[$v],
           );
        }

        $approResult = $oStockdump->to_savestore($adata,$options);
        
        if($approResult){
            kernel::single('console_iostockdata')->notify_stockdump($approResult['stockdump_id'],'create');

            $this->end(true,'转储单保存成功!');
        }else{
            $this->end(false,'转储单保存未成功!');
        }
    }

    /**
    * 取消转储单
    * @access public
    * @param String $stockdump_id 转储单编号
    * @return 
    */
    function do_cancel($stockdump_id){
        $oStockdump = $this->app->model('stockdump');
        if (empty($stockdump_id)){
            $result['rsp'] = 'fail';
        }else{
            $result = kernel::single('console_iostockdata')->notify_stockdump($stockdump_id,'cancel');
            
        }
        die(json_encode($result));
    }

    public function do_save_operation(){
        $this->begin('index.php?app=console&ctl=admin_stockdump&act=index');
        $stockdump_bn = $_POST['stock_bn'];
        
        $approObj = app::get('console')->model('stockdump');
        $app_detail = $approObj->getList('self_status,in_status',array('stockdump_bn'=>$stockdump_bn),0,1);
        if ($app_detail[0]['self_status'] != '1' || !in_array($app_detail[0]['in_status'],array('0'))){
            $this->end(true,'转储单取消成功!'); 
        }
        $type = $approObj->update(array('self_status'=>'0','response_time'=>time()),array('stockdump_bn'=>$stockdump_bn));
        
        $stock_save = kernel::single('console_stock');
        $clear = $stock_save->clear_stockout_store_freeze($stockdump_bn);
        if($type){
            $this->end(true,'转储单取消成功!');
        }else{
            $this->end(false,'转储单取消失败!');
        }
    }


    public function more_items(){
        $finder_id = $_GET['_finder']['finder_id'];
        $appr_id = $_GET['apprid'];
        $render = app::get('console')->render();
        $pObj = &app::get('ome')->model('products');
        $itemObj = &app::get('console')->model('stockdump_items');
        $omeObj = &app::get('ome')->render();
        $page = $_GET['page'] ? $_GET['page'] : 1;
        $pagelimit = 11;
        $offset = ($page-1)*$pagelimit;
        $sql = "SELECT COUNT(*) FROM `sdb_console_stockdump_items` WHERE appropriation_id =".$appr_id;
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

    

    public function get_wms_branch(){
        $branch_id = $_POST['branch_id'];
        $branch_model = &app::get('ome')->model('branch');
       
        $sql = "SELECT wb.wms_id,wb.branch_bn FROM `sdb_ome_branch` as wb 
        
        WHERE wb.branch_id = ".$branch_id;
        $wms_info = kernel::database()->select($sql);
        $bn_array = array();    
        $bn_str = '';    
        $bn = $branch_model->getList('branch_bn',array('wms_id'=>$wms_info[0]['wms_id']));
        $bn_array[] = 0;
        foreach($bn as $v){
            if( $v['branch_bn'] == $wms_info[0]['branch_bn'] ) continue;
            $bn_array[] = $v['branch_bn'];
        }
        
        $where = '';
        
        $where .= ' AND branch_bn in(\''.join('\',\'',$bn_array).'\')';
        

        $obranch_id= $branch_model->getBranchByUser(true);
        if($obranch_id){
            foreach($obranch_id as $k=>&$v){
                if($v == $branch_id) unset($obranch_id[$k]);
            }
            $where .= " AND branch_id in(".implode(',', $obranch_id).")";
        }
        $sql = "SELECT * FROM sdb_ome_branch WHERE 1=1 ".$where;
        $branch_info = kernel::database()->select($sql);
        
        $str = '<select id="to_branch_id" class=" x-input-select inputstyle" vtype="required" name="to_branch_id" >';
        $str .= '<option></option>';
        foreach($branch_info as $v){
            $str .= '<option value='.$v['branch_id'].'>'.$v['name'].'</option>';
        }
        $str .= '</select> <span style="color:red">*</span> ';

        echo $str;
    }

    
    /*下载导入模版*/
    public function exportTemplate(){
        $filename = "转储单".date('Y-m-d').".csv";
        $encoded_filename = urlencode($filename);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);
        $ua = $_SERVER["HTTP_USER_AGENT"];
        header("Content-Type: text/csv");
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $pObj = &$this->app->model('stockdump');
        $title1 = $pObj->exportTemplate('title');
        echo '"'.implode('","',$title1).'"';
        echo "\n";
        $title2 = $pObj->exportTemplate('items');
        echo '"'.implode('","',$title2).'"';
    }
    
    function remind_setting()
    {
        $this->begin('index.php?app=omestorage&ctl=admin_stockdump&act=remind_setting');
        if(isset($_POST['remind_setting_days'])){
            if ($_POST['remind_setting_days'] &&!preg_match("/^[0-9]*[1-9][0-9]*$/",$_POST['remind_setting_days'])) {
                $this->end(false,app::get('omestorage')->_('超时提醒设置请输入正整数'));
            }
            if(empty($_POST['remind_setting_days'])) $_POST['remind_setting_days'] = 'nosetting';
            $this->app->setConf("stockdump_remind_setting_days",$_POST['remind_setting_days']);
            $this->end(true,"设置成功！");
        }
        $stockdump_remind_setting_days = $this->app->getConf("stockdump_remind_setting_days");
        if($stockdump_remind_setting_days == 'nosetting') 
            $this->pagedata['days'] = '';
        elseif(empty($stockdump_remind_setting_days))
            $this->pagedata['days'] = 3;
        else
            $this->pagedata['days'] = $stockdump_remind_setting_days;
        $this->pagedata['action_url'] = "index.php?app=omestorage&ctl=admin_stockdump&act=remind_setting";
        $this->pagedata['msg'] = "天,仍未开始转储,则发出超时提醒";
        $this->page("admin/remind_setting.html");
    }

    function findProduct(){
    
        $base_filter = isset($_GET['product_type']) ? array('type'=>$_GET['product_type']) : array();
        
        $params = array(
                        'title'=>'商品列表',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'base_filter' => $base_filter,
                    );
        
        $this->finder('ome_mdl_products', $params);

    }

    function get_proinfo(){
        $filter = array();
        if($_POST['product_id'][0] != '_ALL_'){
            $filter['product_id'] = $_POST['product_id'];
        }
        $oProducts = &app::get('ome')->model('products');
        $oBranch = &app::get('ome')->model('branch');
        $oBranch_product = &app::get('ome')->model('branch_product');

        $oProducts->filter_use_like = true;
        $product = $oProducts->getlist('bn,name,unit,product_id,spec_info,price',$filter);
        
        foreach($product as $k=>$v){
            $product[$k]['price'] = $product[$k]['price'] ? $product[$k]['price'] : '0';
            $product[$k]['spec_value']=$product[$k]['spec_info'];

            /*获取和此商品建立过关系的仓库*/
            $branch_product = array();
            $branch_product = $oBranch_product->getlist('*',array('product_id'=>$v['product_id']));
            foreach($branch_product as $bk=>$bv){
                $branch = $oBranch->dump($bv['branch_id'],'name');
                $branch_product[$bk]['branch_name'] = $branch['name'];
                $branch = array();
            }
            $product[$k]['branch_product'] = $branch_product;
        }
        
        echo json_encode($product);
    }

    public function get_products(){
        $name = $_GET['name'];
        $bn = $_GET['bn'];
        if($_GET['name']){
            $filter['name'] = $_GET['name'];
        }
        if($_GET['bn']){
            $filter['bn'] = $_GET['bn'];
        }
        if($filter){
            $productObj = &app::get('ome')->model('products');
            $branchObj = &app::get('ome')->model('branch');
            $branchProductObj = &app::get('ome')->model('branch_product');
            $productObj->filter_use_like = true;
            $products = $productObj->getlist('*',$filter,0,10);
            foreach($products as $key=>$product){
                $branch_product = $branchProductObj->getlist('*',array('product_id'=>$product['product_id']));

                foreach($branch_product as $k=>$v){
                    $branch = $branchObj->dump($v['branch_id'],'name');
                    $branch_product[$k]['branch_name'] = $branch['name'];
                }

                $data[$key]['name'] = $product['name'];
                $data[$key]['id'] = $product['product_id'];
                $data[$key]['bn'] = $product['bn'];
                $products[$key]['branch_product'] = $branch_product;
            }
        }
       
        echo "window.autocompleter_json=".json_encode($products);
    }

    /**
    * 取消出入库单确定框
    * @access public
    * @param Number $stock_id 入库单ID
    * @param String $stock_bn 入库单编号
    * @param String $type 出入库,stockin入库;stockout出库
    * @return 
    */
    function cancel($stock_id,$stock_bn){
        $this->pagedata['stock_id'] = $stock_id;
        $this->pagedata['stock_bn'] = $stock_bn;
        
        $title = '转储单';
        $ctl = 'admin_stockdump';
        $this->pagedata['title'] = '转储单';
        $this->pagedata['ctl'] = $ctl;
        $this->display('admin/stockdump/cancel_confirm.html');
    }

     /**
    * 转储单查异查看
    * @access public
    * @param Number $stock_id 入库单ID
    * @param String $stock_bn 入库单编号
    * @param String $type 出入库,stockin入库;stockout出库
    * @return 
    */
    function difference($stockdump_id){
        $branchObj = &app::get('ome')->model('branch');
        
        $pObj = &app::get('ome')->model('products');
        $oStockdump = &app::get('console')->model('stockdump');
              
        $oStockdump_items = &app::get('omestorage')->model('stockdump_items');
        $items = $itemObj->db->select("SELECT * from sdb_console_stockdump_items where stockdump_id=".intval($stockdump_id)." and (`in_nums`!=`num` or `defective_num`!=0)");
        if (!$items){header('Content-Type:text/html; charset=utf-8');
                 echo "该单没有差异！";exit;}
        foreach ($items as $key => $item){
            //将商品的显示名称改为后台的显示名称
            $product_name = $pObj->getList('name,spec_info,unit',array('bn'=>$items['bn']));
            $items[$key]['product_name'] = $product_name[0]['name'];
            $items[$key]['spec_info'] = $product_name[0]['spec_info'];
            $items[$key]['unit'] = $product_name[0]['unit'];
        
        }
        $finder_id = $_GET['_finder']['finder_id'];

        $this->pagedata['items'] = $items;
        $this->pagedata['finder_id'] = $finder_id;
        $this->pagedata['appr_id'] = $appr_id;
        
        $this->singlepage('admin/stockdump/stockin_diff_item.html');
    }

    public function do_save_confirm_type(){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        
        $stockdump_bn = $_GET['stockdump_bn'];
        $finder_id = $_GET['finder_id'];
        
        $stock_save = kernel::single('console_receipt_stockdump');
        $result = $stock_save->confirm_stock($stockdump_bn);
        
        if($result['rsp'] == 'succ'){
            $this->end(true,'转储单确认成功!');
        }else{
            $this->end(false,$result['msg']);    
        }
    }

    
    /**
     * 转储导出模板
     * @param 
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function export_template()
    {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=转储单".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $stoObj = &app::get('console')->model('stockdump');
        $title1 = $stoObj->exportTemplate('title');
        $title2 = $stoObj->exportTemplate('items');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }
    
}