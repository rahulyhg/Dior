<?php
class ome_finder_return_product{
    var $detail_basic = "售后服务详情";

    function __construct($app)
    {
        $this->app = $app;
        if($_GET['app']!='ome'){
            
            unset($this->column_edit);
        }
    }

    function detail_basic($return_id){
        $render         = app::get('ome')->render();
        $oProduct       = &app::get('ome')->model('return_product');
        $oProduct_items = &app::get('ome')->model('return_product_items');
        $oReship_item   = &app::get('ome')->model('reship_items');
        $oOrder         = &app::get('ome')->model('orders');
        $oBranch        = &app::get('ome')->model('branch');
        $oReship   = &app::get('ome')->model('reship');
        $oDly_corp   = &app::get('ome')->model('dly_corp');

        if ($_POST['delivery_id']){
            foreach($_POST['item_id'] as $key => $val){
                $item = array();
                $item['item_id'] = $val;
                $branch_id = $_POST['branch_id'.$val];
                $item['branch_id'] = $branch_id;
                $oProduct_items->save($item);
           }
           $return_product['return_id'] = $return_id;
           $return_product['delivery_id'] = $_POST['delivery_id'];
           $oProduct->save($return_product);
        }
        $product_detail = $oProduct->product_detail($return_id);
        $is_archive = kernel::single('archive_order')->is_archive($product_detail['source']);
        if ($is_archive || $product_detail['archive']=='1') {
            $oOrder         = &app::get('archive')->model('orders');
            $order_detail = $oOrder->dump(array('order_id'=>$product_detail['order_id']));
        }else{
            $order_detail = $oOrder->dump($product_detail['order_id']);
        }
        $reshipinfo = $oReship->dump(array('return_id'=>$return_id),'return_logi_name,return_logi_no');
        if($reshipinfo){
            $corpinfo = $oDly_corp->dump($reshipinfo['return_logi_name'],'name');
            $product_detail['process_data']['shipcompany'] = $product_detail['process_data']['shipcompany']?$product_detail['process_data']['shipcompany']:$corpinfo['name'];
            $product_detail['process_data']['shiplogino'] = $product_detail['process_data']['logino']?$product_detail['process_data']['logino']:$reshipinfo['return_logi_no'];
        }

        $order_id = $product_detail['order_id'];
        if (!$product_detail['delivery_id']){
            $product_items = array();
            if ($product_detail['items'])
               foreach($product_detail['items'] as $k=>$v){
                $refund = $oReship_item->Get_refund_count($order_id,$v['bn']);
                $v['effective']=$refund;
                $v['branch']=$oReship_item->getBranchCodeByBnAndOd($v['bn'],$order_id);
                $product_items[] = $v;
            }
            //获取仓库模式
            $branch_mode = &app::get('ome')->getConf('ome.branch.mode');
            $render->pagedata['branch_mode'] = $branch_mode;
            $product_detail['items'] = $product_items;
        }

        //增加售后服务详情显示前的扩展
        foreach(kernel::servicelist('ome.aftersale') as $o){
            if(method_exists($o,'pre_detail_display')){
                $o->pre_detail_display($product_detail);
            }
        }
        if (!is_numeric($product_detail['attachment'])){
            $render->pagedata['attachment_type'] = 'remote';
        }
        if ($product_detail['source']=='matrix') {
            $plugin_html_show = kernel::single('ome_aftersale_service')->return_product_detail($product_detail);
        
            $render->pagedata['plugin_html_show'] = $plugin_html_show;
            //售后拒绝按钮
            $return_button = kernel::single('ome_aftersale_service')->return_button($return_id,'5');
            $render->pagedata['return_button'] = json_encode($return_button);    
        }
        
        $pcount = $oProduct->count(array('order_id'=>$product_detail['order_id']));
        if($pcount > 1){
           $render->pagedata['is_return_order'] = true;
        }else{
           $render->pagedata['is_return_order'] = false;
        }
        $choose_type_flag = 1;
        $shop_id = $product_detail['shop_id'];
        $router = kernel::single('ome_aftersale_request');
        if ($product_detail['source']=='matrix') {
            if (!$router->setShopId($shop_id)->choose_type()) {
                $choose_type_flag = 0;
            }
        }
        
        $render->pagedata['choose_type_flag'] = $choose_type_flag;
        $render->pagedata['product'] = $product_detail;
        $render->pagedata['order'] = $order_detail;

        return $render->fetch('admin/return_product/detail/basic.html');
    }

    public $detail_member_info = '会员信息';
    public function detail_member_info($return_id)
    {
        $render = $this->app->render();

        $preturnModel = $this->app->model('return_product');
        $return_detail = $preturnModel->select()->columns('member_id,delivery_id')->where('return_id=?',$return_id)->instance()->fetch_row();
        $render->pagedata['memberInfo'] = $this->app->model('members')->select()->columns('uname,tel,zip,email,mobile')
                                            ->where('member_id=?',$return_detail['member_id'])
                                            ->instance()->fetch_row();

        $oProduct_delivery = $this->app->model('delivery');
        $render->pagedata['delivery']=$oProduct_delivery->dump($return_detail['delivery_id'],'ship_area,ship_name,ship_addr,ship_zip,ship_tel,ship_email,ship_mobile');
        
        return $render->fetch('admin/return_product/detail/member_info.html');
    }

    public $detail_operation_log = '操作日志';
    public function detail_operation_log($return_id)
    {
        $render = $this->app->render();

        $preturnModel = $this->app->model('return_product');
        $return_detail = $preturnModel->select()->columns('return_bn')->where('return_id=?',$return_id)->instance()->fetch_row();

        $opLogModel = $this->app->model('operation_log');
        $logFilter = array('obj_type'=>'return_product@ome','obj_id'=>$return_id,'obj_name'=>$return_detail['return_bn']);

        $render->pagedata['logs'] = $opLogModel->read_log($logFilter,0,20,'log_id');

        return $render->fetch('admin/return_product/detail/operation_log.html');
    }

    var $column_edit = "操作";
    var $column_edit_width = "200";
    function column_edit($row){

        if(!kernel::single('desktop_user')->is_super()){
            $returnLib = kernel::single('ome_return');

            $has_permission = $returnLib->chkground('aftersale_center','','aftersale_return_edit');
            if (!$has_permission) {
                return false;
            }

        }

        if($row['status'] == '1'||$row['status'] == '2'){
           return '<a target="dialog::{width:700,height:400,title:\'编辑售后服务单号:'.$row['return_bn'].'\'}" href="index.php?app=ome&ctl=admin_return&act=edit&p[0]='.$row['return_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">编辑</a>  ';
        }
    }
    
    var $addon_cols = 'archive,source,order_id,delivery_id';
    var $column_order_id='订单号';
    var $column_order_id_width='100';
    function column_order_id($row)
    {
        $archive = $row[$this->col_prefix . 'archive'];
        $source = $row[$this->col_prefix . 'source'];
        $order_id = $row[$this->col_prefix . 'order_id'];
        if ($archive == '1' || in_array($source,array('archive'))) {
            $orderObj = app::get('archive')->model('orders');
            $filter = array('order_id'=>$order_id);
        }else{
            $orderObj = app::get('ome')->model('orders');
            $filter = array('order_id'=>$order_id);
        }

        $order = $orderObj->dump($filter,'order_bn');

        return $order['order_bn'];
    }
}
?>