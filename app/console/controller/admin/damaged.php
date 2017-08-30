<?php
/*
**@author Kris wang 406048119@qq.com
**报残控制器
*/
class console_ctl_admin_damaged extends desktop_controller{
    public function index(){
        $branchMdl = &app::get('ome')->model('branch');
        $branch_list = $branchMdl->getList('branch_id,name',array('type'=>array('main','aftersale')));
        $this->pagedata['branch_list'] = $branch_list;
        $this->page('admin/damaged.html');
    }

    public function add_products(){
        $res = array('res'=>'succ');
        $product_bn = trim($_POST['product_bn']);
        $branch_id = intval($_POST['branch_id']);
        $productMdl = &app::get('ome')->model('products');
        $bpMdl = &app::get('ome')->model('branch_product');
        $product_data_tmp = $productMdl->getList('product_id,name',array('bn'=>$product_bn));
        $product_id = $product_data_tmp[0]['product_id'];
        $product_data = $bpMdl->getList('store as num',array('product_id'=>$product_id,'branch_id'=>$branch_id));

        if(empty($product_data)){
            $res['res'] = 'false';
            $res['msg'] = '货号不存在';
            echo json_encode($res);exit;
        }
        $res['data']['bn'] = $product_bn;
        $res['data']['product_id'] = $product_data_tmp[0]['product_id'];
        $res['data']['name'] = $product_data_tmp[0]['name'];
        $res['data']['num'] = $product_data[0]['num'];
        $res['data']['price'] = 0;
        echo json_encode($res);exit;
    }

    public function do_action(){
        $url = 'index.php?app=console&ctl=admin_damaged&act=index';
        $this->begin($url);
        $bpMdl = &app::get('ome')->model('branch_product');
        $productsMdl = &app::get('ome')->model('products');
        $branch_id = $_POST['branch_id'];
        if(empty($branch_id)){
            $this->end(false,'请选择仓库');
        }
        #判断仓库有没有相关的残仓
        $damaged_branch = kernel::single('console_iostockdata')->getDamagedbranch($branch_id);
        if(!$damaged_branch){
            $this->end(false,'所选仓库没有对应残仓，无法进行报残操作');
        }
        $damaged_branch_id = $damaged_branch['branch_id'];
        #判断库存数 与 报残数的 大小
        $in = $out =array();
        $iostock = kernel::single('ome_iostock');
        $type_out = ome_iostock::DAMAGED_LIBRARY;
        $type_in = ome_iostock::DAMAGED_STORAGE;
        foreach($_POST['num'] as $product_id=>$num){
            if(!is_int(intval($num)) || $num <= 0)  $this->end(false,'请输入大于零的正整数');
            //todo 只判断实际库存
            $data = $bpMdl->getList('store',array('branch_id'=>$branch_id,'product_id'=>$product_id));
            if($data[0]['store'] < $num){
                $this->end(false,$_POST['name'][$product_id].' 库存数小于报残数量，请重新确认');
            }
            #入库数据
            $in[$product_id]['bn'] = $_POST['bn'][$product_id];
            $in[$product_id]['branch_id'] = $damaged_branch_id;
            $in[$product_id]['nums'] = $_POST['num'][$product_id];
            $in[$product_id]['operator'] = kernel::single('desktop_user')->get_name();
            $in[$product_id]['iostock_price'] = $_POST['price'][$product_id];
            $in[$product_id]['type_id'] = $type_in;
            #出库数据
            $out[$product_id]['bn'] = $_POST['bn'][$product_id];
            $out[$product_id]['branch_id'] = $branch_id;
            $out[$product_id]['nums'] = $_POST['num'][$product_id];
            $out[$product_id]['operator'] = kernel::single('desktop_user')->get_name();
            $out[$product_id]['iostock_price'] = $_POST['price'][$product_id];
            $out[$product_id]['type_id'] = $type_out;
        }
        #生成 残损出入库单
        #1出库  0 入库
        $iostock_bn_out =  $iostock->get_iostock_bn($type_out);
        if(!$iostock->set($iostock_bn_out,$out,$type_out,$msg,'0')){
            $this->end(false,'残损出库操作失败');
        }
        $iostock_bn_in =  $iostock->get_iostock_bn($type_in);
        if(!$iostock->set($iostock_bn_in,$in,$type_in,$msg,'1')){
            $this->end(false,'残损入库操作失败');
        }
        $this->end(true,$url);
    }
}