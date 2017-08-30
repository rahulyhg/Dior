<?php
class purchase_ctl_admin_stock extends desktop_controller{
    var $name = "入库管理";
    var $workground = "storage_center";
     /*
    * 新建调拨单
    */
    function addtransfer(){
        $OBranch = &app::get('ome')->model('branch');
        $branch  = $OBranch->getList('branch_id, name','',0,-1);
        $OProducts= &app::get('ome')->model('products');

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $OBranch->getBranchByUser();
        }
        $this->pagedata['branch_list']   = $branch_list;
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['branch'] = $branch ;
        $this->singlepage("admin/stock/transfer.html");
    }

    /*调拨理货*/
    public function transferTidy(){
        $branchObj = &app::get('ome')->model('branch');
        $branchs = $branchObj->getList();
        if($this->app->getConf('ome.branch.mode')=='single' || count($branchs)<=1 || $_POST['branch_id'] || $_GET['branch_id']){
            $branch_id = isset($_POST['branch_id'])?$_POST['branch_id']:$branchs[0]['branch_id'];
            $branch_id = isset($_GET['branch_id'])?$_GET['branch_id']:$branch_id;
            $branch = $branchObj->dump(array('branch_id'=>$branch_id), '*');
            $this->pagedata['branch'] = $branch;
            $this->pagedata['op_name'] = kernel::single('desktop_user')->get_name();
            $this->pagedata['curTime'] = date("Y-m-d",time());
            $this->page("admin/stock/transfer_tidy.html");
        }else{
            $this->pagedata['branchs'] = $branchs;
            $this->page("admin/stock/transfer_branch.html");
        }
    }

    public function do_transfer(){
        $this->begin();
        $branch_id = $_POST['branch_id'];
        $from_pos_id = $_POST['from_pos_id'];
        $to_pos_id = $_POST['to_pos_id'];
        $product_id = $_POST['product_id'];

        $productPosObj = &app::get('ome')->model('branch_product_pos');
        $productPos = $productPosObj->dump(array('pos_id'=>$from_pos_id,'product_id'=>$product_id,'branch_id'=>$branch_id),'*');
        $num = isset($productPos['store'])?$productPos['store']:0;

        $memo = $_POST['memo'];
        $op_name = kernel::single('desktop_user')->get_name();

        $adata[] = array(
            'from_pos_id'=>$from_pos_id,
            'to_pos_id'=>$to_pos_id,
            'from_branch_id'=>$branch_id,
            'to_branch_id'=>$branch_id,
            'product_id'=>$product_id,
            'num'=>$num);
        if($to_pos_id!=''){
               $productPosObj->create_branch_pos($product_id,$branch_id,$to_pos_id);
        }
        $oAppropriation = &$this->app->model('appropriation');
        $appropriation['appropriation_id'] = $oAppropriation->getDataByBranch($op_name,$branch_id);
        $appropriation['type'] = '1';
        $oAppropriation->to_savestore($adata,$memo,$op_name,$appropriation);
        if($productPos['default_pos'] && $productPos['default_pos']=='true'){
            $productPosObj->update(array('default_pos'=>'false'),array('product_id'=>$product_id,'pos_id'=>$from_pos_id));
            $productPosObj->update(array('default_pos'=>'true'),array('product_id'=>$product_id,'pos_id'=>$to_pos_id));
        }
        $this->end(true,'理货成功!','index.php?app=purchase&ctl=admin_stock&act=transferTidy&branch_id='.$branch_id);
    }

    /*
     * 调拔单保存
     */
    function do_save(){

        $this->begin();
        $oAppropriation = &$this->app->model('appropriation');
        $oBranch_product = &app::get('ome')->model('branch_product');
        $oBranch_product_pos = &app::get('ome')->model('branch_product_pos');
        $from_branch_id = $_POST['from_branch_id'];
        $to_branch_id = $_POST['to_branch_id'];
        $from_pos = $_POST['from_pos'];
        $to_pos = $_POST['to_pos'];
        $memo = $_POST['memo'];
        $num = $_POST['num'];
        $ckid = $_POST['ckid'];
        $product_id = $_POST['product_id'];
        if(empty($ckid)){
           $this->end(false,'请勾选您需要操作的商品','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
        }
        foreach($ckid as $k=>$v){
//            if($from_pos[$v]!=''){
//                $from_pos_id = $oBranch_product->Get_pos_id($from_branch_id[$v],$from_pos[$v]);
//                if (!$from_pos_id){
//                    $this->end(false,'调出货位'.$from_pos[$v].'不存在');
//                }
//            }
            $from_pos_id = $from_pos[$v];
            if($to_pos[$v]!=''){
                $to_pos_id = $oBranch_product->Get_pos_id($to_branch_id[$v],$to_pos[$v]);
                if (!$to_pos_id){
                    $this->end(false,'调入货位'.$to_pos[$v].'不存在');
                }
            }
           if(intval($num[$v])==0){
               $this->end(false,'调拨数量不可为0','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
           }
           if($from_branch_id[$v]=='' && $to_branch_id[$v]==''){
               $this->end(false,'调出仓库和新仓库必须有一个选择','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
           }
            if($from_pos[$v]=='' && $to_pos[$v]==''){
               $this->end(false,'调出货位和新货位必须有一个选择','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
           }
           //判断调出仓库与货位 是否与 调入仓库与货位一致
           $frombranch = $from_branch_id[$v].$from_pos[$v];
           $tobranch = $to_branch_id[$v].$to_pos[$v];
           //if($frombranch==$tobranch){
               //$this->end(false,'调出与调入不能在同一仓库的货位！','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
           //}

           if($from_branch_id[$v]!=''){
               $branch_product = $oBranch_product->dump(array('branch_id'=>$from_branch_id[$v],'product_id'=>$product_id[$v]),'*');

               if(empty($branch_product)){

                   $this->end(false,'调出仓库和商品关系未建立,不可以调拔!','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
               }
               if($from_pos_id!=''){
                   $Branch_product_pos = $oBranch_product_pos->dump(array('product_id'=>$product_id[$v],'pos_id'=>$from_pos_id),'*');

                   if(empty($Branch_product_pos)){

                        $this->end(false,$from_pos_id.'调出货位和商品关系未建立,不可以调拔!'.$product_id[$v],'index.php?app=purchase&ctl=admin_stock&act=addtransfer');
                    }
//                    /*数量不够*/
                   $pos_total = $oBranch_product_pos->dump(array('product_id'=>$product_id[$v],'pos_id'=>$from_pos_id,'branch_id'=>$from_branch_id),'store');

                   if($to_pos_id!=''){
                       if($pos_total['store']<$num[$v]){
                            $this->end(false,'调出货位所剩数量不足以本次调拔!','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
                       }
                   }
               }
           }

           if($to_branch_id[$v]!=''){

               $oBranch_product_pos->create_branch_pos($product_id[$v],$to_branch_id[$v],$to_pos_id);

//               $branch_product = $oBranch_product->dump(array('branch_id'=>$to_branch_id[$v],'product_id'=>$product_id[$v]));
//
//               if(empty($branch_product)){
//                   $this->end(false,'调入仓库和商品关系未建立,不可以调拔!','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
//               }
//
//               $Branch_product_pos = $oBranch_product_pos->dump(array('product_id'=>$product_id[$v],'pos_id'=>$to_pos_id));
//               if(empty($Branch_product_pos)){
//                   $this->end(false,'调入货位和商品关系未建立,不可以调拔!','index.php?app=purchase&ctl=admin_stock&act=addtransfer');
//               }
               /*数量不够*/
           }
           $adata[] = array('from_pos_id'=>$from_pos_id,'to_pos_id'=>$to_pos_id,'from_branch_id'=>$from_branch_id[$v],'to_branch_id'=>$to_branch_id[$v],'product_id'=>$product_id[$v],'num'=>$num[$v]);
        }
        $oAppropriation->to_savestore($adata,$memo,$_POST['op_name']);
        $this->end(true,'调拔成功!','index.php?app=purchase&ctl=admin_appropriation');
    }

    function get_proinfo(){
        $product_id = $_POST['product_id'];
        $oProducts = &app::get('ome')->model('products');
        $oBranch = &app::get('ome')->model('branch');
        $product = $oProducts->dump($product_id,'bn,name,unit,product_id,spec_info');
        $product['spec_value']=$product['spec_info'];
        /*获取和此商品建立过关系的仓库*/
        $oBranch_product = &app::get('ome')->model('branch_product');
        $branch_product = $oBranch_product->getlist('*',array('product_id'=>$product_id));

        foreach($branch_product as $k=>$v){
            $branch = $oBranch->dump($v['branch_id'],'name');
            $branch_product[$k]['branch_name'] = $branch['name'];
        }
        $product['branch_product'] = $branch_product;
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
        //error_log(var_export($products,true),3,__FILE__.".log");
        echo "window.autocompleter_json=".json_encode($products);
    }

    public function exsitPosition(){
        if ($_POST['pos_name']){
            $pos_name = trim($_POST['pos_name']);
            $branch_id = $_POST['branch_id'];
            $product_id = $_POST['product_id'];
            if (!$branch_id) exit("false");
            $bpObj = &app::get('ome')->model('branch_pos');
            $bp = $bpObj->dump(array('store_position'=>$pos_name,'branch_id'=>$branch_id),'pos_id');
            if (!$bp) exit("false");
            $productPosObj = &app::get('ome')->model('branch_product_pos');
            $product = $productPosObj->dump(array('product_id'=>$product_id,'branch_id'=>$branch_id,'pos_id'=>$bp['pos_id']),'store');
            $bp['store'] = $product['store'];
            echo json_encode($bp);
        }else {
            echo "false";
        }
    }

    public function getProduct(){
        if(!isset($_POST['branch_id'])){
            exit('false');
        }
        $ivObj = &$this->app->model('inventory');
        $productObj = &app::get('ome')->model('products');

        $branch_id = $_POST['branch_id'];

        if (isset($_POST['barcode'])) {
            $barcode = trim($_POST['barcode']);
            $product = $productObj->dump(array('barcode'=>$barcode),'*');
            if (!$product) exit('false');
            $data = $ivObj->getBranchProduct($branch_id, $barcode);
        }elseif (isset($_POST['bn'])) {
            $bn = trim($_POST['bn']);
            $product = $productObj->dump(array('bn'=>$bn),'*');
            if (!$product) exit('false');
            $data = $ivObj->getBnProduct($branch_id, $bn);
        }else{
            exit('false');
        }
        $product['branch'] = $data;
        echo json_encode($product);
    }

    public function exsitProduct() {
        
        if(!isset($_POST['branch_id'])){
            exit('false');
        }
        $ivObj = &$this->app->model('inventory');
        $productObj = &app::get('ome')->model('products');

        $branch_id = $_POST['branch_id'];

        if (isset($_POST['barcode'])) {
            $barcode = trim($_POST['barcode']);
            $barcode = str_replace(array('%2B','%26'), array('+','&'), $barcode);
            $product = $productObj->dump(array('barcode'=>$barcode),'*');
        }elseif (isset($_POST['bn'])) {
            $bn = trim($_POST['bn']);
            $bn = str_replace(array('%2B','%26'), array('+','&'), $bn);
            $product = $productObj->dump(array('bn'=>$bn),'*');

        }else{
            exit('false');
        }

        if (!$product) exit('false');
        echo json_encode($product);
    }

    /**
     * 保存账户的选择 
     *
     * @param  void
     * @return void
     * @author 
     **/
    public function savePosSetting()
    {
        $pos_setting = $_POST['setting'];
        kernel::single('desktop_user')->set_conf('pos.setting',$pos_setting);
        exit('true');
    }



}


