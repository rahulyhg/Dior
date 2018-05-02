<?php
class ome_ctl_admin_return extends desktop_controller {
    var $name = "售后服务";
    var $workground = "aftersale_center";

    function index() {
        
        $action =  array(array(
                    'label' => '新建售后服务',
                    'href' => 'index.php?app=ome&ctl=admin_return&act=add_return',
                    'target' => "dialog::{width:700,height:490,title:'新建售后服务'}",
                  ));
        switch (intval($_GET['view'])) {
            case '0':
            case '1':
            case '2':
                $action[] = array(
                    'label' => '批量同意退货',
                    'submit' => 'index.php?app=ome&ctl=admin_return&act=batch_syncUpdate&status_type=agree',
                    'target' => "dialog::{width:700,height:490,title:'批量同意退货'}",
                  );
                 $action[] = array(
                    'label' => '批量拒绝退货',
                    'submit' => 'index.php?app=ome&ctl=admin_return&act=batch_syncUpdate&status_type=refuse',
                    'target' => "dialog::{width:700,height:490,title:'批量拒绝退货'}",
                  );
                break;
            default:
                break;
        }
        $base_filter = array('is_fail'=>'false');
        $params = array(
            'title'=>'售后服务',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'actions' => $action,
            'base_filter'=>$base_filter,
        );

        # 权限判定
        if(!$this->user->is_super()){
            $returnLib = kernel::single('ome_return');
           foreach ($params['actions'] as $key=>$action) {
               $url = parse_url($action['href']);
               parse_str($url['query'],$url_params);
                $has_permission = $returnLib->chkground($this->workground,$url_params);
                if (!$has_permission) {
                    unset($params['actions'][$key]);
                }
           }
        }


        $this->finder ( 'ome_mdl_return_product' , $params );
    }

    function _views(){
        if($_GET['act'] == 'return_io') return true;
        $mdl_return_product = $this->app->model('return_product');
        $sub_menu = array(
            0 => array('label'=>__('全部'),'optional'=>false,'filter'=>array('is_fail'=>'false')),
            1 => array('label'=>__('未处理'),'filter'=>array('status'=>'1','is_fail'=>'false'),'optional'=>false),
            2 => array('label'=>__('审核中'),'filter'=>array('status'=>'2','is_fail'=>'false'),'optional'=>false),
            3 => array('label'=>__('接受申请'),'filter'=>array('status'=>'3','is_fail'=>'false'),'optional'=>false),
            4 => array('label'=>__('完成'),'filter'=>array('status'=>'4','is_fail'=>'false'),'optional'=>false),
            5 => array('label'=>__('拒绝'),'filter'=>array('status'=>'5','is_fail'=>'false'),'optional'=>false),
            6 => array('label'=>__('已收货'),'filter'=>array('status'=>'6','is_fail'=>'false'),'optional'=>false),
            7 => array('label'=>__('已质检'),'filter'=>array('status'=>'7','is_fail'=>'false'),'optional'=>false),
            8 => array('label'=>__('补差价'),'filter'=>array('status'=>'8','is_fail'=>'false'),'optional'=>false),
            9 => array('label'=>__('已拒绝退款'),'filter'=>array('status'=>'9','is_fail'=>'false'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_return_product->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_return&act=index&view='.$i++;
        }
        return $sub_menu;
    }


    function edit($return_id){
        if(!intval($return_id))
            die('单据号传入错误');
        $Oreturn = $this->app->model('return_product');
        $return_info = $Oreturn->dump(array('return_id'=>$return_id),'order_id');
        $this->apply( $return_info['order_id'] ,$return_id,'edit');
    }

    /*
     *对当前售后申请各状态进行保存
     *
     * @param Int $status 申请售后状态
     */
    function save($status) {
       
        $oProduct = &$this->app->model ( 'return_product' );
        $oOrder   = &$this->app->model ( 'orders' );

        $this->begin ();

        $return_id    = $_POST ['return_id'];
        $bn_array     = array ();
        $oPro_detail  = $oProduct->dump ( $return_id, 'status,order_id,shop_id,source' );
        #如果是本地订单不需要根据平台去读取扩展
        $source = $oPro_detail['source'];
        $archiveLib = kernel::single('archive_order');
        if ($archiveLib->is_archive['source']) {
            $oOrder   = app::get('archive')->model ( 'orders' );
        }
        $order_detail = $oOrder->dump ( $oPro_detail ['order_id'], 'ship_status' );
        if($status!='5'){#当提交状态为拒绝时不判断
        //判断订单是否已经全部退货
            if ($order_detail ['ship_status'] == '4') {
                $this->end ( false, app::get ( 'base' )->_ ( '订单已全部退货,请拒绝此售后申请!' ) );
            }
        }

        //增加售后服务保存状态前的扩展
        foreach ( kernel::servicelist ( 'ome.aftersale' ) as $o ) {
            if (method_exists ( $o, 'pre_save_status' )) {
                $o->pre_save_status ( $_POST );
            }
        }
       $_POST['shop_id'] = $oPro_detail['shop_id'];
       //平台对保存前的扩展
        if ($source=='matrix') {
          $pre_result = kernel::single('ome_aftersale_service')->pre_save_return($_POST);
        }
       
       $archive_ordObj = app::get('archive')->model('orders');
       if ($pre_result['rsp'] == 'fail') {
           $this->end ( false, $pre_result['msg'] );
       }
        //
        if ($status == 2 || $status == 3) {
            $adata = $_POST;
            
            //售后---审核出现两次,begin
            $now_status = $oProduct->getList ( 'status,delivery_id', array ('return_id' => $return_id ) );

            if(empty($now_status[0]['delivery_id'])){
                $this->end ( false, app::get ( 'base' )->_ ( '收货地址未选择，请先进入编辑界面选择' ) );
            }

            if($now_status[0]['status']!=3)
            {
                
                if ($source=='matrix') {
                    $api_flag = kernel::single('ome_aftersale_service')->return_api($_POST);
                    if ($api_flag) {
                        $api_flag = TRUE;
                    }
                }
               #
               //预先做判断看是否可以生成
               $choose_type = $adata['choose_type'];
               if (in_array($choose_type,array('1','2'))) {
                   $choose_flag = true;
                   $Oproduct_items = $this->app->model('return_product_items');
                   $reship_items = $this->app->model('reship_items');
                    $pro_items = $Oproduct_items->getList('*',array('return_id'=>$return_id,'disabled'=>'false'));
                    foreach($pro_items as $k=>$v){
                       $apply_num = $v['num'];
                       $bn = $v['bn'];
                       if (in_array($source,array('archive'))) {
                           $effective = $archive_ordObj->Get_refund_count ( $oPro_detail ['order_id'],$bn );
                       }else{
                            $effective = $reship_items->Get_refund_count($oPro_detail ['order_id'],$bn);
                       }
                        if ($effective<=0) {
                           $choose_flag = false;
                           break;
                       }
                    }

                    if (!$choose_flag) {
                        $this->end ( false, app::get ( 'base' )->_ ( '货品剩余数量不足,不可以申请!' ) );
                    }
               }
               //
               $oProduct->tosave ( $adata,$api_flag);
            }

        //售后---审核出现两次,end
        } else {
            $data = array ('return_id' => $return_id, 'status' => $_POST ['status'], 'last_modified' => time () );
            $oProduct->update_status ( $data );
            $memo = $oProduct->schema['columns']['status']['type'][$_POST ['status']];
            $this->app->model('operation_log')->write_log('return@ome',$return_id,'售后服务:'.$memo);
        }

        //增加售后服务保存状态后的扩展
        foreach ( kernel::servicelist ( 'ome.aftersale' ) as $o ) {
            if (method_exists ( $o, 'after_save_status' )) {
                $o->after_save_status ( $_POST );
            }
        }

        $this->end ( true, app::get ( 'base' )->_ ( '操作成功' ) );
        


    }

    /*
      * 新建售后服务 add_return
      */
    function add_return() {
        $source = trim($_GET['source']);
        //
        $this->pagedata['source'] = $source;
        $this->pagedata ['search_filter'] = array ('ship_name' => '收货人','ship_tel' => '收货人电话','ship_mobile' => '收货人手机', 'order_bn' => '订单号' );
        $this->display ( "admin/return_product/add_return.html" );
    }
    /*
    *
    */
    function returnPreview($page = 1) {

        $data = $_POST;

        $page = $page ? $page : 1;
        $pagelimit = 12;

        $oOrders = &$this->app->model ( 'orders' );
        $keywords = preg_replace ( "/[\s]*/i", "", $data ['keywords'] );
        $keywords = addslashes ( $keywords );
        $no_exactitude = array('ship_tel','ship_mobile');
        $exactitude = $data ['exactitude'];
        if (! $exactitude || in_array($data['search_filter'], $no_exactitude)) {
            $filter = " `".$data['search_filter']."` like '%" . $keywords . "%' ";
            $exactitude = 'false';
            $limit = 100;
        } else {
            $filter = " `".$data['search_filter']."`='" . $keywords . "' ";
            $limit = 1;
        }
        $orders = $oOrders->getOrderBybn ( $filter, 'order_bn,order_id,ship_status', $pagelimit * ($page - 1), $pagelimit );
        $this->pagedata ['exactitude'] = $exactitude;

        $count = $orders ['count'];
        $total_page = ceil ( $count / $pagelimit );
        $pager = $this->ui ()->pager ( array ('current' => $page, 'total' => $total_page, 'link' => '?page=%d' ) );
        $this->pagedata ['pager'] = $pager;
        unset ( $orders ['count'] );
        $this->pagedata ['orders'] = $orders;
        $this->pagedata ['total_page'] = $total_page;
        $this->pagedata ['pagelimit'] = $pagelimit;
        $this->pagedata['keywords'] = $keywords;
        $this->pagedata ['count'] = $count;
        $this->pagedata ['cur_page'] = $page;

        return $this->display ( "admin/return_product/add_return_div.html" );
    }

    /*
    *售后服务申请
    */
    function apply($order_id,$return_id='',$act = 'add') {
        $oProductObj =  &$this->app->model ( 'products' );
        $archive_ordObj = app::get('archive')->model('orders');
        $order_id = intval ( $order_id );
        if (!$order_id) die('订单传入错误');
        $source = trim($_GET['source']);
        
        
        $oReship_item = &$this->app->model ( 'reship_items' );
        $oProduct = &$this->app->model ( 'return_product' );
        $product = $oProduct->dump(array('return_id'=>$return_id));
        $archiveLib = kernel::single('archive_order');
        if (($source && $archiveLib->is_archive($source)) || ($product && $archiveLib->is_archive($product['source']))) {
            
            $oOrders_item = app::get('archive')->model ( 'order_items' );
            $order_items = $oOrders_item->getList ( '*', array ('order_id' => $order_id ) );
        }else{
            $oOrders_item = &$this->app->model ( 'order_items' );
            $order_items = $oOrders_item->getList ( '*', array ('order_id' => $order_id ,'delete'=>'false') );
        }
        $newItems = array();
        if($act == 'edit'){
            $oProduct_items = &$this->app->model ( 'return_product_items' );
            $items = $oProduct_items->getList ( '*', array ('return_id' => $return_id ) );
            $sendnum_list = array();
            foreach (  $order_items as $oitems ) {
                $sendnum_list[$oitems['bn']] = $oitems;
            }
            foreach ( $items as $k => $v ) {
                $spec_info = $oProductObj->dump($v['product_id'],'spec_info');
                $items[$k]['spec_info'] = $spec_info['spec_info'];
                if($newItems[$v['bn']] && $newItems[$v['bn']]['bn'] !=''){
                    $newItems[$v['bn']]['nums'] += $items[$k]['nums'];
                    $newItems[$v['bn']]['sendnum'] += $items[$k]['sendnum'];
                    $newItems[$v['bn']]['price'] = $order_items[$k]['price'];
                    $newItems[$v['bn']]['sale_price'] = $order_items[$k]['sale_price'];
                }else{
                    if (in_array($product['source'],array('archive'))) {
                        $refund = $archive_ordObj->Get_refund_count ( $order_id, $v ['bn'] );
                        $items [$k] ['branch'] = $archive_ordObj->getBranchCodeByBnAndOd ( $v ['bn'], $order_id );
                    }else{
                        $refund = $oReship_item->Get_refund_count ( $order_id, $v ['bn'] );
                        $items [$k] ['branch'] = $oReship_item->getBranchCodeByBnAndOd ( $v ['bn'], $order_id );
                    }
                    
                    $items [$k] ['effective'] = $refund;
                    
                    $items[$k]['sendnum'] = $sendnum_list[ $v ['bn']]['sendnum'];
                    $items[$k]['price'] = $sendnum_list[ $v ['bn']]['price'];
                    $items[$k]['sale_price'] = $sendnum_list[ $v ['bn']]['sale_price'];
                    $items[$k]['nums'] = $sendnum_list[ $v ['bn']]['nums'];
                    $newItems[$v['bn']] = $items[$k];

                }
            }

            
            #扩展页面
            $plugin_html = kernel::single('ome_aftersale_service')->pre_return_product_edit($product);
            
            if ($plugin_html && $plugin_html['rsp']!='fail') {
                $this->pagedata['plugin_html_show'] = $plugin_html;
            }
            
        }else{
            $items = $oOrders_item->getList ( '*', array ('order_id' => $order_id ) );
            foreach ( $items as $k => $v ) {
                $spec_info = $oProductObj->dump($v['product_id'],'spec_info');
                $items[$k]['spec_info'] = $spec_info['spec_info']; 
                /*去除订单明细ID*/
                unset($v['item_id'],$items[$k]['item_id']);

                if($newItems[$v['bn']] && $newItems[$v['bn']]['bn'] !=''){
                    $newItems[$v['bn']]['nums'] += $items[$k]['nums'];
                    $newItems[$v['bn']]['sendnum'] += $items[$k]['sendnum'];
                }else{
                    if ($source && in_array($source,array('archive'))) {
                        $refund = $archive_ordObj->Get_refund_count ( $order_id, $v ['bn'] );
                        $items [$k] ['branch'] = $archive_ordObj->getBranchCodeByBnAndOd ( $v ['bn'], $order_id );
                    }else{
                        $refund = $oReship_item->Get_refund_count ( $order_id, $v ['bn'] );
                        $items [$k] ['branch'] = $oReship_item->getBranchCodeByBnAndOd ( $v ['bn'], $order_id );
                    }
                    
                    $items [$k] ['effective'] = $refund;
                    
                    $newItems[$v['bn']] = $items[$k];
                }
            }
        }

        $items = $newItems;
        //获取仓库模式
        $branch_mode = &app::get ( 'ome' )->getConf ( 'ome.branch.mode' );
        $this->pagedata['branch_mode'] = $branch_mode;
        $this->pagedata['items'] = $items;
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['act'] = $act;
        $this->pagedata['finder_id'] = $_GET['finder_id']?$_GET['finder_id']:$_POST['finder_id'];
        
        if($product['delivery_id']){
            if (in_array($product['source'],array('archive')) ) {
                $Odelivery = app::get('archive')->model('delivery');
            }else{
                $Odelivery = $this->app->model('delivery');
            }

           $deli_info = $Odelivery->dump(array('delivery_id'=>$product['delivery_id']),'delivery_bn,ship_name,ship_area,ship_addr');
           list($package,$region_name,$region_id) = explode(':',$deli_info['consignee']['area']);
           if($region_name){
              $deli_info['consignee']['area'] = str_replace('/', '-', $region_name);
           }
           $product = array_merge($product,$deli_info);
        }

        $this->pagedata['source'] = $source;
        $this->pagedata['product'] = $product;
        $this->display( "admin/return_product/return_apply.html" );
    }

    /* 售后申请*/
    function apply_add() {
        $this->begin('index.php?app=ome&ctl=admin_return&act=index');
        $url = $_POST['url'];
        $oProduct = &$this->app->model ( 'return_product' );
        $oItems = &$this->app->model ( 'return_product_items' );
        $oOrder = &$this->app->model ( 'orders' );
        $archiveLib = kernel::single('archive_order');
        $order_id = $_POST ['order_id'];
        $order = $oOrder->dump ( $order_id, 'member_id,shop_id' );
        $oShop = &$this->app->model ( 'shop' );
        $shop_type = $oShop->getShoptype($order ['shop_id']);
        $source = $_POST['source'];
        $delivery_id = $_POST['delivery_id'];
        $title = $_POST['title'];
        $archive_ordObj = app::get('archive')->model('orders');
        if ($title == '') {
            $this->end(false,'售后标题不可为空!');

        } else if (empty ( $_POST ['goods_bn'] )) {
            $this->end(false,'您没有选择商品!');
        } else if ($delivery_id == '') {
             $this->end(false,'请为此售后服务选择收货人信息!');
        }

        $items = array ();
        foreach ( $_POST ['goods_bn'] as $key => $val ) {
            $item = array ();
            $item ['bn'] = $val;
            $item ['item_id'] = $_POST ['item_id'] [$val];
            $item ['name'] = $_POST ['goods_name'] [$val];
            $item ['product_id'] = $_POST ['product_id'] [$val];
            $item ['num'] = intval ( $_POST ['num'] [$val] );

            $_POST ['num'] [$val] = intval($_POST ['num'] [$val]);
            $_POST ['effective'] [$val] = intval($_POST ['effective'] [$val]);

            if ($_POST ['num'] [$val] <= 0) {
                $this->end(false,'申请数量不可以为小于1的整数!');
            }
            if ($_POST ['effective'] [$val] <= 0) {
                $this->end(false,'剩余数量不足，不可以操作!');

            } else if ($_POST ['num'] [$val] > $_POST ['effective'] [$val]) {
                $this->end(false,'申请数量大于剩余数量，不可以操作!');
            }

            $val = str_replace ( " ", "_", $val );
            $branch_id = $_POST ['branch_id' . $item['product_id']];//fix by danny 2012-5-18
            if (empty($branch_id)) {
                $this->end(false,'货品仓库不能为空!');
            }
            if ($source && in_array($source,array('archive'))) {
                $branch_num = $archive_ordObj->Get_delivery ( $branch_id, $val, $order_id );
            }else{
                $branch_num = $oProduct->Get_delivery ( $branch_id, $val, $order_id );
            }
            
            if ($_POST ['num'] [$val] > $branch_num) {
                $this->end(false,'所选仓库数量不足!');
            }

            $item ['branch_id'] = $branch_id;
            $items [] = $item;
        }
        $upload_file = "";
        if($_FILES ['attachment']['size'] != 0){
            if ($_FILES ['attachment'] ['size'] > 314572800) {
                $this->end(false,'上传文件不能超过300M!');
            }

            $type = array ("jpg", "gif", "bmp", "jpeg", "rar", "zip" );
            if ($_FILES ['attachment'] ['name'])
                if (! in_array ( strtolower ( $this->fileext ( $_FILES ['attachment'] ['name'] ) ), $type )) {
                    $text = implode ( ",", $type );
                    $this->end(false,"您只能上传以下类型文件{$text}!");
                }
            $ss = kernel::single ( 'base_storager' );
            $id = $ss->save_upload ( $_FILES ['attachment'], "file", "", $msg ); //返回file_id;
           
            $upload_file = $id;
            $aData ['attachment'] = $upload_file;
        }
        $aData ['order_id'] = $order_id;

        $aData ['title'] = $_POST ['title'];
        $aData ['add_time'] = time ();
        $aData ['member_id'] = $order ['member_id']; //申请人
        $aData ['content'] = $_POST ['content'];
        $aData ['memo'] = $_POST ['memo'];
        $aData ['status'] = 1;
        $aData ['shop_id'] = $order ['shop_id']; //店铺id
        $aData ['shop_type'] = $shop_type; //店铺类型
        $opInfo = kernel::single ( 'ome_func' )->getDesktopUser ();
        $aData ['op_id'] = $opInfo ['op_id'];
        if ($source && in_array($source,array('archive'))) {
            $aData['source'] = $source;
            $aData['archive'] = '1';
        }
        $aData ['delivery_id'] = $_POST ['delivery_id'];
        
        if($_POST['return_id']){
            $aData ['return_bn'] = $_POST['return_bn'];
            $aData ['return_id'] = $_POST['return_id'];
            $add_operation = '修改';
            $method = 'update_status';

        }else{
            $return_bn = $oProduct->gen_id();
            $aData ['return_bn'] = $return_bn;
            $add_operation = '创建';
            $method = 'add_aftersale';
        }
        
        
        $oProduct->save ( $aData );
        $oItems->update( array('disabled'=>'true'),array('return_id'=>$aData ['return_id']) );
        foreach ( $items as $k => $v ) {
            $v ['return_id'] = $aData ['return_id'];
            $v ['disabled'] = 'false';
            $oItems->save ( $v );
        }
        $oOperation_log = &$this->app->model ( 'operation_log' );

        $memo = $add_operation.'售后服务';

        $oOperation_log->write_log ( 'return@ome', $aData ['return_id'], $memo );

        //售后申请 API
        foreach ( kernel::servicelist ( 'service.aftersale' ) as $object => $instance ) {
            if (method_exists ( $instance, $method )) {
                $instance->$method ( $aData ['return_id'] );
            }
        }

        #售后操作
        kernel::single('ome_aftersale_service')->return_product_edit_after($_POST);

        $finder_id = $_GET['finder_id'];
        $this->end(true,"售后服务{$add_operation}成功!",'javascript:$("return-apply").getParent(".dialog").retrieve("instance").close();finderGroup["'.$finder_id.'"].refresh();');
    }

    function fileext($filename) {
        return substr ( strrchr ( $filename, '.' ), 1 );
    }
    function check() {
        $branch_id = $_GET ['branch_id'];
        $bn = $_GET ['bn'];
        $order_id = $_GET ['order_id'];
        $source = $_GET['source'];
        if ($source && in_array($source,array('archive'))) {
            $oProduct = app::get('archive')->model('orders');
        }else{
            $oProduct = &$this->app->model ( 'return_product' );
        }
        
        $result = $oProduct->Get_delivery ( $branch_id, $bn, $order_id );

        echo json_encode ( $result );
    }

    function file_download2($return_id) {
        $oProduct = &$this->app->model ( 'return_product' );
        $info = $oProduct->dump ( $return_id );
        $filename = $info ['attachment'];
        if (is_numeric ( $filename )) {
            $ss = kernel::single ( 'base_storager' );
            $a = $ss->getUrl ( $filename, "file" );
            $oProduct->file_download ( $a );
        } else {
            header ( 'Location:' . $filename );
        }

    }


    /**
     * 选择操作类型
     *   1.退货单，2.换货单，3.退款申请单
     * @return void
     * @author
     **/
    function choose_type($return_id,$status)
    {
       if(!$return_id)
        die("单据号传递错误！");
       #根据类型转化是否继续，否则保存当前状态
       
       $Oreturn = $this->app->model('return_product');
       $reship = $Oreturn->dump(array('return_id'=>$return_id),'return_bn,order_id,shop_id,source');
       $shop_id = $reship['shop_id'];
       $choose_type_flag = 0;
       $choose_type_value = 0;
       if ($reship['source'] == 'matrix') {
           $router = kernel::single('ome_aftersale_request');
           if (!$router->setShopId($shop_id)->choose_type()) {
               $choose_type_flag = 1;
           }
           $choose_type_value = $router->setShopId($shop_id)->choose_type_value($return_id);
            
       }
       
       $this->pagedata['choose_type_value'] = $choose_type_value;
       $this->pagedata['choose_type_flag'] = $choose_type_flag;
       $this->pagedata['return_id'] = $return_id;
       $this->pagedata['return_bn'] = $reship['return_bn'];
       $this->pagedata['finder_id'] = $_GET['finder_iid'];
       $this->pagedata['status'] = $status;
       $this->pagedata['is_edit'] = 'false';
       $this->getRefundinfo($reship['order_id']);

       $this->display('admin/return_product/choose_type.html');
       
       
    }

    function getRefundinfo($orderid){
        //判断是否为失败订单
        $api_failObj = &$this->app->model('api_fail');
        $api_fail = $api_failObj->dump(array('order_id'=>$orderid,'type'=>'payment'));
        if ($api_fail){
            $api_fail_flag = 'true';
        }else{
            $api_fail_flag = 'false';
        }
        $this->pagedata['api_fail_flag'] = $api_fail_flag;

        $this->pagedata['orderid'] = $orderid;
        $objOrder = &$this->app->model('orders');
        $aORet = $objOrder->order_detail($orderid);
        //if ($aORet['pay_status'] == '1'){
        //    exit("此订单已支付完成");
        //}

        $aORet['cur_name'] = 'CNY';
        $aORet['cur_sign'] = 'CNY';

        $oPayment = &$this->app->model('payments');
        $payment_cfgObj = &app::get('ome')->model('payment_cfg');
        $oShop = &$this->app->model('shop');
        $c2c_shop = ome_shop_type::shop_list();
        $shop_id = $aORet['shop_id'];
        $shop_detail = $oShop->dump($shop_id,'node_type,node_id');
        if ($shop_id && !in_array($shop_detail['node_type'], $c2c_shop)){
            $payment = kernel::single('ome_payment_type')->paymethod($shop_id);
        }else{
            $payment = $oPayment->getMethods();
        }

        $payment_cfg = $payment_cfgObj->dump(array('pay_bn'=>$aORet['pay_bn']), 'id,pay_type');

        $this->pagedata['shop_id'] = $shop_id;
        $this->pagedata['node_id'] = $shop_detail['node_id'];
        $this->pagedata['payment'] = $payment;
        $this->pagedata['payment_id'] = $payment_cfg['id'];
        $this->pagedata['pay_type'] = $payment_cfg['pay_type'];
        if ($payment_cfg['id']){
            $order_paymentcfg = kernel::single('ome_payment_type')->paymethod($shop_id,$payment_cfg['pay_type']);
        }
        $this->pagedata['order_paymentcfg'] = $order_paymentcfg;
        $this->pagedata['op_name'] = 'admin';
        $this->pagedata['typeList'] = ome_payment_type::pay_type();

        if($aORet['member_id'] > 0){
            $objMember = &$this->app->model('members');
            $aRet = $objMember->member_detail($aORet['member_id']);
            $this->pagedata['member'] = $aRet;
        }else{
            $this->pagedata['member'] = array();
        }
        $this->pagedata['order'] = $aORet;

        $aRet = $oPayment->getAccount();
        $aAccount = array('--使用已存在帐户--');
        foreach ($aRet as $v){
            $aAccount[$v['bank']."-".$v['account']] = $v['bank']." - ".$v['account'];
        }
        $this->pagedata['pay_account'] = $aAccount;
        //剩余支付金额
        $pay_money = kernel::single('eccommon_math')->number_minus(array($aORet['total_amount'],$aORet['payed']));
        $this->pagedata['pay_money'] = $pay_money;
        $this->pagedata['aItems'] = $objOrder->getItemList($orderid);

    }

    /**
     * 构造一个已发货订单列表
     *
     * @return void
     * @author
     **/
    function getOrders()
    {

        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '订单查看';
        $source = trim($_GET['source']);
        if (in_array($source,array('archive'))) {
            $base_filter = array('disabled'=>'false','is_fail'=>'false','ship_status'=>array('1','3'),'pay_status'=>array('1','4','3'));
            if (in_array($_SERVER['SERVER_NAME'],array('bzclarks.erp.taoex.com'))) {
                    unset($base_filter['pay_status']);
                    $base_filter['pay_status'] = array('1','4','5','6');
            }
            $params = array(
                'title'=>$this->title,
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>true,
                'use_view_tab'=>false,
                'finder_aliasname' => 'order_view'.$op_id,
                'finder_cols' => 'order_bn,shop_id,total_amount,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime',
                'orderBy' => 'order_id',
                'orderType' => 'desc',
                'base_filter' => $base_filter,
           );


           $this->finder('archive_mdl_orders',$params);
        }else{
            $base_filter = array('disabled'=>'false','is_fail'=>'false','ship_status'=>array('1','3'),'pay_status'=>array('1','4','3'));
        //$base_filter['order_confirm_filter'] = "(sdb_ome_orders.is_cod='true' OR sdb_ome_orders.pay_status='1' OR sdb_ome_orders.pay_status='4')";

        $params = array(
            'title'=>$this->title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>false,
            'finder_aliasname' => 'order_view'.$op_id,
            'finder_cols' => 'order_bn,shop_id,total_amount,column_print_status,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime,mark_type',
            'orderBy' => 'order_id',
            'orderType' => 'desc',
            'base_filter' => $base_filter,
       );


           $this->finder('ome_mdl_orders',$params);
        }
        
    }

    /**
     * 跳转至单据编辑页
     *
     * @param INT $return_id 售后服务ID
     * @param String $type 单据类型(return:退货单，change:换货单)
     * @return void
     * @author
     **/
    public function gotoreceipt($return_id,$type)
    {
        if (!in_array($type, array('return','change'))) {
            echo '单据类型错误!';exit;
        }

        $reship_id = $this->app->model('reship')->select()->columns('reship_id')->where('return_id=?',$return_id)->instance()->fetch_one();
        
        if (!$reship_id) {
            echo '单据不存在!';exit;
        }

        kernel::single('ome_ctl_admin_return_rchange')->edit($reship_id);
    }

    /**
     * 售后入库单
     *
     * @return void
     * @author
     **/
    function return_io()
    {
        #增加单据导出权限
        $is_export = kernel::single('desktop_user')->has_permission('bill_export');
        $this->workground = "invoice_center";

        #$finder_cols = 'return_bn,return_apply_time,return_problem,verify_time,verify_person,order_bn,bn,reship_bn';

        $params = array(
            'title'=>'售后入库单',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            //'finder_cols' => $finder_cols,
        );

        $this->finder('ome_mdl_return_iostock',$params);
    }

    
    /**
     * 新建售后.
     * @param  
     * @return  
     * @access  public
     * 
     */
    function create_return()
    {
        $this->page('admin/return_product/create_return.html');
    } // end func
    
    /**
     * 退款留言
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function refund_message($apply_id,$type='return')
    {
        $this->pagedata['apply_id'] = $apply_id;
        $this->pagedata['type'] = $type;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->pagedata['shop_type'] = $_GET['shop_type'];
        //$this->pagedata['message_list'] = $message_list;
        $this->display('admin/refund/plugin/refund_message.html');
    }

    function download_message($apply_id,$type){
        set_time_limit(0);
        $message_list = kernel::single('ome_service_aftersale')->refund_message($apply_id,$type);

        $shop_type = $_POST['shop_type'];
        $online_memo = array();
        $rsp = array('rsp'=>'succ','msg'=>'获取成功');
        if ($message_list) {
            $online_memo = serialize($message_list);
        
            $oRefund = &$this->app->model('refund_apply');
            $refund = $oRefund->dump($apply_id,'shop_id');
            $oShop = &$this->app->model('shop');
            $shop = $oShop->dump(array('shop_id'=>$refund['shop_id']));
            if ($type == 'refund') {#退款
                if ($shop_type == 'tmall') {
                    $oRefund_apply_model = &$this->app->model('refund_apply_tmall');
                }else{
                    $oRefund_apply_model = &$this->app->model('refund_apply_taobao');
                }
                $result = $oRefund_apply_model->update(array('online_memo'=>$online_memo),array('apply_id'=>$apply_id));
             
            }else{#退货
                if ($shop_type == 'tmall') {
                    $oRefund_apply_model = &$this->app->model('return_product_tmall');
                }else{
                    $oRefund_apply_model = &$this->app->model('return_product_taobao');
                }
                $result = $oRefund_apply_model->update(array('online_memo'=>$online_memo),array('return_id'=>$apply_id));
               
            }
            if (!$result) {
                $rsp = array('rsp'=>'fail','msg'=>'获取失败,请稍后再试');
            } 
            
        }else{
            $rsp = array('rsp'=>'fail','msg'=>'暂无凭证');
        }
        echo json_encode($rsp);
    }
   
    
    /**
     * 拒绝留言.
     * @param   return_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function refuse_message($return_id,$shop_type)
    {
        set_time_limit(0);
        if ($_POST) {
            $this->begin();
            $oProduct = &$this->app->model('return_product');
            $refuse_message = $_POST['refuse_message'];
            
            $return_id = $_POST['return_id'];
            $shop_type = $_POST['shop_type'];
            if (in_array($shop_type,array('taobao','tmall'))) {
                if ($_FILES ['refuse_proof']['size']<=0) {
                    $this->end(false,'请上传凭证图片!');
                }
            }
            if ($shop_type == 'taobao') {
                
                $return_model = &$this->app->model('return_product_taobao');
            }else if($shop_type == 'tmall'){
                $return_model = &$this->app->model('return_product_tmall');
                $return_tmall = $return_model->dump(array('return_id' => $return_id));
                $operation_contraint = $return_tmall['operation_contraint'];
                if ($operation_contraint) {
                    $operation_contraint = explode('|',$operation_contraint);
                    if ( in_array('cannot_refuse',$operation_contraint) ) {
                        $this->end(false,'此单据,不允许拒绝，必须同意');
                    }
                    if ( in_array('refund_onweb',$operation_contraint) ) {
                        $this->end(false,'此单据,回到web页面上操作');
                    }
                }
            }else if($shop_type == 'yhd'){
                $return_model = &$this->app->model('return_product_yihaodian');
            }
            $refuse_memo['refuse_message'] = $refuse_message;
            if($_FILES ['refuse_proof']['size'] != 0){
                if ($_FILES ['refuse_proof'] ['size'] > 512000) {
                    $this->end(false,'上传文件不能超过500K!');
                }

                $type = $type = array ('gif','jpg','png');
                $imgext = strtolower ( $this->fileext ( $_FILES ['refuse_proof'] ['name'] ) );
                if ($_FILES ['refuse_proof'] ['name'])
                    if (! in_array ( $imgext, $type )) {
                        $text = implode ( ",", $type );
                        $this->end(false,"您只能上传以下类型文件{$text}!");
                    }
                $ss = kernel::single ( 'base_storager' );
                $id = $ss->save_upload ( $_FILES ['refuse_proof'], "file", "", $msg ); //返回file_id;
                $refuse_memo['image'] = $ss->getUrl ( $id, "file" );
                if ($shop_type == 'tmall') {
                    $rh = fopen($_FILES['refuse_proof']['tmp_name'],'rb');
                    $imagebinary = fread($rh, filesize($_FILES['refuse_proof']['tmp_name']));
                    fclose($rh);
                    $imagebinary = base64_encode($imagebinary);

                }else{
                    $imagebinary = $refuse_memo['image'];
                }
                                          
            }
            $product = $oProduct->dump($return_id);
            if ($refuse_memo) {
                $data = array(
                    'return_id'     =>$return_id,
                    'shop_id'       =>$product['shop_id'],
                    'return_bn'     =>$product['return_bn'],
                    'refuse_memo'    =>serialize($refuse_memo),
                    'imgext'        =>$imgext,
                );
                
                $return_result = $return_model->save($data);
               
            }
            
            $aftersale_service = kernel::single('ome_service_aftersale');
            if(method_exists($aftersale_service, 'refuse_return')){
                
                $memo['refuse_message'] = $refuse_message;
                $memo['refuse_proof'] = $imagebinary;
                $rs = $aftersale_service->update_status($return_id,'5','sync',$memo);

                if (!$rs || $rs['rsp'] == 'fail') {
                    $this->end(false,$rs['msg']);
                }
                
                $adata = array(
                    'return_id'=>$return_id,
                    'shop_id'=>$product['shop_id'],
                    'status'=>'5',
                );
                $oProduct->tosave ( $adata,TRUE);
            }
            $this->end(true,'成功');
        }
        
        $this->pagedata['shop_type'] = $shop_type;
       
        $this->pagedata['return_id'] = $return_id;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/return_product/plugin/refuse_message.html');
    }

    
    
   
   /**
    * 批量同步更新状态
    * @param   type    $varname    description
    * @return  type    description
    * @access  public
    * @author cyyr24@sina.cn
    */
   function batch_syncUpdate(){
       $oReturn = &app::get('ome')->model('return_product');
       $oReturn_tmall = &app::get('ome')->model('return_product_tmall');
       $oReturn_address = &app::get('ome')->model('return_address');//cancel_def
       $oReturn_batch = &app::get('ome')->model('return_batch');
       $status_type = $_GET['status_type'];
       if (!in_array($status_type,array('agree','refuse'))) {
           echo '暂不支持此状态变更';
           exit;
        }
        $return_list = $oReturn->getlist('status,return_bn,source,shop_type,shop_id',array('return_id'=>$_POST['return_id']));
        $chk_msg = array();
        $error = array();
        //淘宝异步 天猫是即时的 
        if ($status_type =='agree') {
            foreach ( $return_list as $return ) {
                $return_id = $return['return_id'];
                $status = $return['status'];
                if ( !in_array($status,array('1','2'))) {
                    $error_msg =$return['return_bn'].':状态不可以批量接受退货申请';
                }
                //淘宝天猫必须填地址等信息
                if (($return['shop_type'] == 'tmall' || $return['shop_type'] == 'taobao') && $return['source'] == 'matrix') {
                    
                    $return_address = $oReturn_address->dump(array('shop_id'=>$return['shop_id'],'cancel_def'=>'true'));
                    if (!$return_address) {
                        $chk_msg[]= '请为店铺设置默认退货地址,否则批量将无法操作';
                        break;
                    }
                    
                }
                if ($error_msg) {
                    $error[] = array('error_msg' => $error_msg);
                }
            
            }
        }elseif( $status_type =='refuse' ) {
            foreach ( $return_list as $return ) {
                $return_id = $return['return_id'];
                $status = $return['status'];
                if ( !in_array($status,array('1','2'))) {
                    $error_msg =$return['return_bn'].':状态不可以批量拒绝';
                }
                //淘宝天猫必须填地址等信息
                if (($return['shop_type'] == 'tmall' || $return['shop_type'] == 'taobao') && $return['source'] == 'matrix') {
                    
                    $return_batch = $oReturn_batch->dump(array('shop_id'=>$return['shop_id'],'is_default'=>'true','batch_type'=>'refuse_return'));
                    if (!$return_batch) {
                        $chk_msg[]= '请为店铺设置默认拒绝凭证和留言!';
                        break;
                    }
                    
                }
                if ($error_msg) {
                    $error[] = array('error_msg' => $error_msg);
                }
            
            }
        }
        
        $this->pagedata['error'] = $error;
        $this->pagedata['chk_msg'] = $chk_msg;
        //查询是否都是线上单据，是否淘宝和天猫
        #获取可操作数据
        $returnObj = kernel::single('ome_return_product');
        $need_return_list = $returnObj->return_list($_POST['return_id']);
        $this->pagedata['status_type'] = $status_type;
        $this->pagedata['need_return_list'] = json_encode($need_return_list);
        $this->pagedata['need_return_list_count'] = count($need_return_list);
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/return_product/plugin/batch_taobao.html');
   }

    
    /**
     * 请求执行可操作
     * @param      
     * @return   
     * @access  public
     * @author cyyr24@sina.cn
     */
    function ajax_batch()
    {
        set_time_limit(0);
        $returnObj = kernel::single('ome_return_product');
        $data = $_POST;
        $ajaxParams = trim($data['ajaxParams']);
        if (strpos($ajaxParams, ';')) {

            $params = explode(';', $ajaxParams);
        } else {

            $params = array($ajaxParams);
        }
        $status_type = $data['status_type'];
        $return_id = json_decode($data['return_id'],true);
        $rs = $returnObj->batch_update($status_type,$params);
        echo json_encode(array('total' => count($params), 'succ' => $rs['succ'], 'fail' => $rs['fail'],'error_msg'=>$rs['error_msg']));
    }
   
   /**
    * Short description.
    * @param   type    $varname    description
    * @return  type    description
    * @access  public
    * @author cyyr24@sina.cn
    */
   function showImage($filepath)
   {
       echo "<img src='$filepath'>";
   }

    /**
     * 更新退款单状态
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function do_updateReturn($return_id,$status)
    {
        
        $oReturn = &app::get('ome')->model('return_product');
        $return = $oReturn->dump($return_id,'return_id,shop_id');
        $adata = array(
                    'return_id'=>$return_id,
                    'shop_id'=>$return['shop_id'],
                    'status'=>'5',
                    'memo'=>'向线上请求拒绝失败,本地拒绝',
         );
        $oReturn->tosave ( $adata,TRUE);
        $data = array('rsp'=>'succ');
        echo json_encode($data);
    }
}
?>
