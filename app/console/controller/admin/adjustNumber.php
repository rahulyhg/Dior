<?php
class console_ctl_admin_adjustNumber extends desktop_controller{

    public function exportTemplate(){

        $filename = "调账模板".date('Y-m-d').".csv";
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
        
        $adjustObj = &$this->app->model('adjustNumber');        
        $title = $adjustObj->exportTemplate('adjust');
        echo '"'.implode('","',$title).'"';
    }
    
    public function import(){
        $this->finder('console_mdl_adjustNumber',array(
            'title'=>'批量导入',
            'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_filter'=>true,
            'use_buildin_export'=>true,
            'orderBy' =>'goods_id DESC'
        ));
    }
    

    function adjust(){
        $oProduct = app::get('ome')->model('products');
        $oBranch  = app::get('ome')->model('branch');
        $oBranchPro = app::get('ome')->model('branch_product');
        if($_POST){
            $data = $_POST;
            $this->begin('index.php?app=console&ctl=admin_adjustNumber&act=adjust');
            $data['branch'] == '' ? $this->end(false,'仓库名称不能为空') : '';
            if($data['searchtype'] == 'bn'){ //按货号查
                $data['product_bn'] == '' ? $this->end(false,'货号必填') : '';
                //判断货品名称是否存在
                $products = $oProduct->getList('product_id,bn,name,type',array('bn'=>trim($data['product_bn'])));
                if(empty($products[0]['product_id'])){
                    $this->end(false,'该货品不存在');
                }
            }else if($data['searchtype'] == 'name'){ //按货品名称查
                $data['product_name'] == '' ? $this->end(false,'货品名称必填') : '';
                //判断货品名称是否存在
                $products = $oProduct->getList('product_id,bn,name,type',array('name'=>trim($data['product_name'])));
                if(empty($products[0]['product_id'])){
                    $this->end(false,'该货品不存在');
                }                
            }
            $this->endonly(true);
            
            $branchPro = array();
            if($products[0]['type'] == 'normal'){#只针对普通商品
                foreach($products as $key=>$value){
                    $branchPro[] = $oBranchPro->dump(array('branch_id'=>$data['branch'],'product_id'=>$value['product_id']),'branch_id,store,store_freeze');

                    if(empty($branchPro[$key])){
                        $branchPro[$key]['branch_id'] = $data['branch'];
                        $branchPro[$key]['store'] = 0;
                        $branchPro[$key]['store_freeze'] = 0;
                    }
                    $branchPro[$key]['product_name'] = $value['name'];
                    $branchPro[$key]['product_id'] = $value['product_id'];
                    $branchPro[$key]['product_bn'] = $value['bn'];
                    $branch_name = $oBranch->dump($data['branch'],'name');
                    $branchPro[$key]['branch_name']  = $branch_name['name'];
                }
            }
            $this->pagedata['pickList'] = $branchPro;
            $this->pagedata['products'] = $products[0];
        }
         // 获取操作员管辖仓库
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $branch_id = $branch_ids;
            }else{
                $branch_id = 'false';
            }
        }
        //$user_branch = kernel::single("ome_userbranch");
        //$branch_id= $user_branch->get_user_branch_id();
        if($branch_id) $branch  = $oBranch->getList('branch_id, name',array('branch_id'=>$branch_id),0,-1); //获取改管理员权限下仓库
        else $branch  = $oBranch->getList('branch_id, name','',0,-1); //获取所有仓库仓库
        $this->pagedata['branch'] = $branch ;
        $this->pagedata['searchtype'] = array(array('type_value'=>'bn','type_name'=>'货号'),array('type_value'=>'name','type_name'=>'货品名称'));
        $filetype = kernel::single('omeio_public_config')->get_filetype();
        $this->pagedata['filetype'] = $filetype;
        $this->page('admin/stock/adjust.html');
    }

    function do_adjust(){
        
        $key = $_POST['select'];
        $data['product_id'] = $_POST['product_id'][$key];
        $data['branch_id']  = $_POST['branch_id'][$key];
        $data['to_nums']    = $_POST['to_nums'][$key];
        $oProduct = app::get('ome')->model('products');
        $oBranch  = app::get('ome')->model('branch');
        $oBranchPro = app::get('ome')->model('branch_product');

       
        $operator = kernel::single('desktop_user')->get_name();
        
        $this->begin('index.php?app=console&ctl=admin_adjustNumber&act=adjust');
        $product = $oProduct->dump($data['product_id'],'product_id,bn,store');
        
        if(!isset($data['to_nums'])){
            $this->end(false,'请输入调整到的数量');
        }elseif($data['to_nums'] < 0){
            $this->end(false,'数量应为正整数');
        }

        
        $branchPro_info = $oBranchPro->dump(array('branch_id'=>$data['branch_id'],'product_id'=>$data['product_id']),'store,store_freeze');
        
        if(empty($branchPro_info)){
            $branchPro_info['store'] = 0;
            $branchPro_info['store_freeze'] = 0;
            $branchPro_info['cost'] = 0;
        }
        $store = $branchPro_info['store']-$branchPro_info['store_freeze'];
        if(($diff_nums = $store-$data['to_nums']) == 0){
            $this->end(false,'原有数量与修改数量相同，请确认信息');
        }
        
        $type = $diff_nums < 0 ? "IN" : "OUT";

        if( $type == 'IN'){
            $adjustLib = kernel::single('siso_receipt_iostock_adjustnumberin');
        }else{
            $adjustLib = kernel::single('siso_receipt_iostock_adjustnumberout');


        }
        $iostockData[0]['bn']               = $product['bn'];
        $iostockData[0]['iostock_price']    = $branchPro_info['cost'];
        $iostockData[0]['branch_id']        = $data['branch_id'];

        $iostockData[0]['operator']         = $operator;
        $iostockData[0]['oper']             = $operator;
        $iostockData[0]['nums']             = abs($diff_nums);
        $iostockData[0]['memo']             = $_POST['memo'];
        $result = $adjustLib->create($iostockData, $createdata, $msg);
        if (!$result) {
            $this->end(true,'调整库存失败');
        }
        
        $this->end(true,'调整库存成功');
    }

    
    function index(){
        $this->finder('console_mdl_adjustNumber');
    }
    
}