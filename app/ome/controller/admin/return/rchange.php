<?php
class ome_ctl_admin_return_rchange extends desktop_controller {
    var $name = "退换货单";
    var $workground = "aftersale_center";

    function index(){

        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
        );

        if($_GET['flt'] == 'process_list'){
           $oBranch = &app::get('ome')->model('branch');
           $params['title'] = '售后收货';
           //$params['base_filter'] = array('status|noequal'=>'succ');
           if(!$_GET['view']){
              $params['base_filter']['is_check'] = array('1','3','4','7','8','9','13');
           }
           $params['use_buildin_export'] = false;
           $wms_id = kernel::single('wms_branch')->getBranchByselfwms();

            $branch_list = $oBranch->getList('branch_id', array('wms_id'=>$wms_id), 0, -1);

            if ($branch_list)
            $branch_ids = array();
            foreach ($branch_list as $branch_list) {
                $branch_ids[] = $branch_list['branch_id'];

            }
            $params['base_filter']['branch_id'] = $branch_ids;
           //$this->workground = "wms_center";
        }else{
           $params['use_buildin_export'] = true;
           $params['title'] = '退换货单';
           //$params['base_filter'] = array('status|noequal'=>'succ');
           $actions = array();
           /*$actions[] =array(
                    'label' => '新建退换货单',
                    'href' => 'index.php?app=ome&ctl=admin_return_rchange&act=rchange',
                    'target' => "dialog::{width:1200,height:546,title:'新建退换货单'}",
                  );*/
           if ($_GET['view'] == '2') {
                $actions[] =array('label' => '发送至第三方',
                            'submit' => 'index.php?app=ome&ctl=admin_return_rchange&act=batch_sync', 
                            'confirm' => '你确定要对勾选的退货单发送至第三方吗？', 
                            'target' => 'refresh');
           }
           $params['actions'] = $actions;
           $this->workground = "aftersale_center";
        }

        if(isset($_POST['return_type'])){
            $params['base_filter']['return_type'] = $_POST['return_type'];
        }else{
            //过滤拒收退货
            $params['base_filter']['return_type'] = array('return','change');
        }


        # 权限判定
        if(!$this->user->is_super()){
           $returnLib = kernel::single('ome_return');
           // foreach ($params['actions'] as $key=>$action) {
           //     $url = parse_url($action['href']);
           //     parse_str($url['query'],$url_params);
           //      $has_permission = $returnLib->chkground($this->workground,$url_params);
           //      if (!$has_permission) {
           //          unset($params['actions'][$key]);
           //      }
           // }
           $add_rchange_permission = kernel::single('desktop_user')->has_permission('aftersale_rchange_add');
           if (!$add_rchange_permission) {
               unset($params['actions'][0]);
           }
        }
        #如果没有导出权限，则屏蔽导出按钮
        $is_export = kernel::single('desktop_user')->has_permission('aftersale_rchange_export');
        $params['use_buildin_export'] = $is_export;
        

        $this->finder ( 'ome_mdl_reship' , $params );
    }

    function _views(){
        if($_GET['flt'] == 'process_list'){
            #$this->workground = "wms_center";
            $sub_menu = $this->_view_process();
        }else{
            #$this->workground = "aftersale_center";
            $sub_menu = $this->_view_all();
        }
        return $sub_menu;
    }

    function _view_all(){
        $mdl_reship = $this->app->model('reship');
        $base_filter = array('return_type'=>array('return','change'));
        $sub_menu = array(
            0 => array('label'=>__('全部'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>__('未审核'),'filter'=>array('is_check'=>'0'),'optional'=>false),
            2 => array('label'=>__('审核成功'),'filter'=>array('is_check'=>'1'),'optional'=>false),
            3 => array('label'=>__('审核失败'),'filter'=>array('is_check'=>'2'),'optional'=>false),
            //4 => array('label'=>__('收货成功'),'filter'=>array('is_check'=>'3'),'optional'=>false),
            //5 => array('label'=>__('拒绝收货'),'filter'=>array('is_check'=>'4'),'optional'=>false),
            6 => array('label'=>__('拒绝'),'filter'=>array('is_check'=>'5'),'optional'=>false),
            7 => array('label'=>__('补差价'),'filter'=>array('is_check'=>'6'),'optional'=>false),
            8 => array('label'=>__('完成'),'filter'=>array('is_check'=>'7'),'optional'=>false),
            9 => array('label'=>__('质检通过'),'filter'=>array('is_check'=>'8'),'optional'=>false),
            10 => array('label'=>__('拒绝质检'),'filter'=>array('is_check'=>'9'),'optional'=>false),
            11=> array('label'=>__('未录入退回物流号'),'filter'=>array('filter_sql'=>'({table}return_logi_no is null or {table}return_logi_no="")'),'optional'=>false),
            12 => array('label'=>__('质检异常'),'filter'=>array('filter_sql'=>'({table}is_check="10" or ({table}need_sv="false" and {table}is_check="0"))','optional'=>false)),
            13 => array('label'=>__('待确认'),'filter'=>array('is_check'=>'11','optional'=>false)),
        );

        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_reship->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_return_rchange&act=index&view='.$k;
        }

        return $sub_menu;
    }
    function _view_process(){
        $mdl_reship = $this->app->model('reship');
        $base_filter = array('return_type'=>array('return','change'));
        $is_check = array('1','3','4','7','8','9');
        $sub_menu = array(
            0 => array('label'=>__('全部'),'filter'=>array('is_check'=>$is_check),'optional'=>false),
            1 => array('label'=>__('审核成功'),'filter'=>array('is_check'=>'1'),'optional'=>false),
            //2 => array('label'=>__('收货成功'),'filter'=>array('is_check'=>'3'),'optional'=>false),
            //3 => array('label'=>__('拒绝收货'),'filter'=>array('is_check'=>'4'),'optional'=>false),
            4 => array('label'=>__('完成'),'filter'=>array('is_check'=>'7'),'optional'=>false),
            5 => array('label'=>__('质检通过'),'filter'=>array('is_check'=>'8'),'optional'=>false),
            6 => array('label'=>__('拒绝质检'),'filter'=>array('is_check'=>'9'),'optional'=>false),
        );


        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_reship->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_return_rchange&act=index&flt=process_list&view='.$k;
        }

        return $sub_menu;
    }


    function rchange(){
        $return_type = array('return'=>'退货','change'=>'换货');
        if ($_GET['type']) {
			//zjr写死
            if ($_GET['type'] =='change') {
                $return_type = array('change'=>'换货');
            }else{
				$return_type = array('return'=>'退货');
			}
            $reship_data['return_type'] = $_GET['type'];
            $this->pagedata['reship_data'] = $reship_data;
        }
        $this->pagedata['return_type'] = $return_type;
        $this->pagedata['order_filter'] = array('pay_status'=>'1','ship_status'=>'1');
        $oProblem = &$this->app->model('return_product_problem');
        $list = $oProblem->getList('problem_id,problem_name',array('disabled'=>'false'));
        $this->pagedata['problem_type'] = $list;
        //
        $branchtype = app::get('wms')->getConf('wms.branchset.type');
        $this->pagedata['branchtype'] =  $branchtype;
        $branchObj = app::get('ome')->model('branch');
        $branch_list = $branchObj->getlist('branch_id,name',array('disabled'=>'false'));
        $this->pagedata['branch_list'] = $branch_list;
        unset($branch_list);
        //
	    $source = trim($_GET['source']);
        $this->pagedata['source'] = $source;
        $this->display('admin/return_product/rchange/rchange.html');
    }

    function add_rchange(){
        $this->begin();
        $post = kernel::single('base_component_request')->get_params(true);
		
		if($post['shop_type']=="minishop"){
			$this->end(false,'小程序订单不允许退货!');
		}
		
        $Oreship = $this->app->model('reship');
        $reshipinfo = $Oreship->dump($post['reship_id'],'is_check');
        $post['is_check'] = $reshipinfo['is_check'];
		
		//zjrMCD
		$arrPostMagento=array();
		if($post['return_type']=="change"){
			$arr=$Oreship->getMcd($post,true);
			$post['change']['product']=$arr['change']['product'];
			$post['memo']=$arr['memo'];
			$arrPostMagento=$arr['magento'];
		}
		
        if(!$Oreship->validate($post,$v_msg)){
            $this->end(false,$v_msg);
        }
        if ($post['reship_id']) {
             
             if ($reshipinfo['is_check']=='7') {
                 $this->end(false,'此单据已完成!');
             }
         }
		// echo "<pre>";print_r($post);print_r($arrPostMagento);exit;
        # 生成退换货单
        $reship_bn = $Oreship->create_treship($post,$msg);
        
        if($reship_bn == false) {
            $this->end(false,$msg);
        }
        
        # 补差价
        if($reship_bn){
            $money = kernel::single('ome_return_rchange')->calDiffAmount($post);
            $refund['totalmoney'] = $money['totalmoney'];
            $refund['tmoney'] = $money['tmoney'];
            $refund['bmoney'] = $money['bmoney'];
            $refund['bcmoney'] = $money['bcmoney'];
            $refund['diff_money'] = $money['diff_money'];
            $refund['change_amount'] = $money['change_amount'];
            $refund['diff_order_bn'] = $post['diff_order_bn'] ? $post['diff_order_bn'] : '';
            $refund['cost_freight_money'] = $money['cost_freight_money'];
			//oms发起的换货单 前端单号等于oms换货单号
			$refund['m_reship_bn']=$reship_bn;
			$refund['relate_change_items']=serialize($arrPostMagento);
            $Oreship->update($refund,array('reship_bn'=>$reship_bn));
        }

        $reship = $Oreship->getList('reship_id',array('reship_bn'=>$reship_bn),0,1);
		
		//发给ax
		$objDeliveryOrder = app::get('ome')->model('delivery_order');
		$delivery_id = $objDeliveryOrder->getList('*',array('order_id'=>$post['order_id']));
		//echo "<pre>";print_r($delivery_id);exit;
		$delivery_id = array_reverse($delivery_id);
		
		//换货发给magento
		if($post['return_type']=="change"){
			$arrPostMagento['order_id']=$post['order_id'];
			$arrPostMagento['exchange_no']=$reship_bn;
			//kernel::single('omemagento_service_change')->sendChangeOrder($arrPostMagento);
		}
        //kernel::single('omeftp_service_reship')->delivery($delivery_id[0]['delivery_id'],$reship[0]['reship_id'],$post['return_type'],$arrPostMagento);

        //奇门退单创建
        kernel::single('qmwms_request_omsqm')->returnOrderCreate($delivery_id[0]['delivery_id'],$reship[0]['reship_id'],$post['return_type'],$arrPostMagento);

        $params['reship_id'] = $reship[0]['reship_id'];

        $this->end(true,$msg,null,$params);
    }
	
	function getOrderReturnType(){
		
		$return_type=$_GET['get_return_type'];
			
		$objOrder = $this->app->model('orders');
		$objReship = $this->app->model('reship');
			
		$res=array();
		$res['status']='fail';
			
		if(!$objReship->isCanAddMcdReship($_GET['order_id'],$return_type,$msg)){
			$res['msg']=$msg;
		}else{
			$res['status']='succ';
		}
	
		echo json_encode($res);
		
	}

    /**
     * 根据order_bn快速获取订单信息
     *
     * @return void
     * @author yangminsheng
     **/
    function getOrderinfo()
    {
        $order_bn = trim($_GET['order_bn']);
        $source = trim($_GET['source']);
        if ($order_bn){
            //已支付部分退款并且已发货或部分退货的款到发货订单或货到付款已发货或部分退货的订单
			//MCD 新增 部分付款
            $base_filter = array('disabled'=>'false','is_fail'=>'false','ship_status'=>array('1','3'),'pay_status'=>array('1','3','4'),'order_bn|has'=>$order_bn);

            $order = $this->app->model('orders');
            if ($source) {
                $is_archive = kernel::single('archive_order')->is_archive($source);
                
                if ($is_archive) {
                    $order = app::get('archive')->model('orders');
                    $base_filter = array('is_fail'=>'false','ship_status'=>array('1','3'),'pay_status'=>array('1','4'),'order_bn|has'=>$order_bn);
                    if (in_array($_SERVER['SERVER_NAME'],array('bzclarks.erp.taoex.com'))) {
                        unset($base_filter['pay_status']);
                        $base_filter['pay_status'] = array('1','4','5','6');
                    }
                }
            }
            

            $data = $order->getList('order_id,order_bn',$base_filter);

            echo "window.autocompleter_json=".json_encode($data);
        }
    }

    function getOdersById(){
        //
        $source = trim($_GET['source']);

        $order_id = $_POST['id'];

        if ($order_id){
            if ($source && in_array($source,array('archive'))) {
                $orders = app::get('archive')->model('orders');
                $base_filter = array('is_fail'=>'false','order_id'=>$order_id,'ship_status'=>array('1','3'),'pay_status'=>array('1','4'));
                if (in_array($_SERVER['SERVER_NAME'],array('bzclarks.erp.taoex.com'))) {
                    unset($base_filter['pay_status']);
                    $base_filter['pay_status'] = array('1','4','5','6');
                }
            }else{
                $orders = $this->app->model('orders');
                $base_filter = array('disabled'=>'false','is_fail'=>'false','order_id'=>$order_id,'ship_status'=>array('1','3'),'pay_status'=>array('1','4'));
            }
            

            //$base_filter['order_confirm_filter'] = "(sdb_ome_orders.is_cod='true' OR sdb_ome_orders.pay_status='1' OR sdb_ome_orders.pay_status='4')";
            $data = $orders->dump($base_filter, 'order_id,order_bn');
            $res['name'] = $data['order_bn'];
            $res['id'] = $data['order_bn'];
            echo json_encode($res);
            exit;
        }
    }

    /**
     * 获取订单信息
     *
     * @return void
     * @author
     **/
    function ajax_getOrderinfo()
    {	
        $json_data = array('rsp'=>'fail','msg'=>'');
        $post = kernel::single('base_component_request')->get_params(true);
		
        $source = $_GET['source'];
        if ($source && in_array($source,array('archive'))) {
            $oOrders = app::get('archive')->model ( 'orders' );
            $oDelivery = app::get('archive')->model ( 'delivery' );
            $order = $oOrders->dump ( array ('order_id' => $post['order_id']),'*' );
        }else{
            $oOrders = &$this->app->model ( 'orders' );
            $oDelivery = &$this->app->model ( 'delivery' );
            $order = $oOrders->dump ( array ('order_id' => $post['order_id'] ),'*' );
        }
        
        $oProduct = &$this->app->model ( 'return_product' );
        
        if($order){
            $member = $this->app->model('members')->dump(array('member_id'=>$order['member_id']));

            $json_data['rsp'] = 'succ';
            $this->pagedata ['order_id'] = $order_id;

            $delivery = $oDelivery->getDeliveryByOrder('*',$post['order_id']);
            $order = array_merge($order,$delivery[0]);
            $order['member_id'] = $member['account']['uname'];
            $order['createtime'] = date('Y-m-d H:i:s',$order['createtime']);
            $this->pagedata['ship_area'] = $order['ship_area'];
            $order['ship_area'] = $this->fetch('admin/return_product/rchange/show_area.html');
            $json_data['msg'] = $order;
        }

        echo json_encode($json_data);
        exit;
    }

    /**
     * @description
     * @access public
     * @param Int $order_id 订单ID
     * @param String $type 数据类型 return:退货、change:换货
     * @return void
     */
    function get_data($order_id,$type,$source='',$after_service='return'){
        //获取仓库模式
        $newItems = array();
        $tmp_product = array();
        if ($source == 'archive') {
            $oOrders_item = app::get('archive')->model ( 'order_items' );
            
            $order_object = app::get('archive')->model('order_objects')->getList('*',array('order_id'=>$order_id,'obj_type'=>'pkg'));
            
        }else{
            $oOrders_item = &$this->app->model ( 'order_items' );
            $order_object = $this->app->model('order_objects')->getList('*',array('order_id'=>$order_id,'obj_type'=>'pkg'));
        }
		$objProducts = &app::get('ome')->model('products');
        $archive_ordObj = app::get('archive')->model('orders');
        $oReship_item = &$this->app->model ( 'reship_items' );
        $items = $oOrders_item->getList ( '*', array ('order_id' => $order_id ),0,-1,'obj_id desc' );
        

        foreach($order_object as $object){
            $table = '<table><caption>捆绑信息</caption><thead><tr><th>货号</th><th>商品名称</th><th>价格</th><th>数量</th></tr></thead><tbody><tr>';
            $table .= '<td>'.$object['bn'].'</td><td>'.$object['name'].'</td><td>'.$object['price'].'</td><td>'.$object['quantity'].'</td>';
            $table .= '</tr></tbody></table>';
            $object['ref'] = $table;
            $oObject[$object['obj_id']] = $object;
        }
		
        $color = array('red','blue');
        foreach ( $items as $k => $v ) {
             $str_spec_value = '';
             $spec_info = unserialize($v['addon']);
             if(!empty($spec_info['product_attr'])){
                 foreach($spec_info['product_attr'] as $_val){
                     $str_spec_value .= $_val['value'].'|';
                 }
                 if(!empty($str_spec_value)){
                     $str_spec_value = substr_replace($str_spec_value,'',-1,1);
                 }
                 $items [$k]['spec_value'] = $str_spec_value;
             }

            if (!$objColor[$v['obj_id']]) {
                $objColor[$v['obj_id']] = $c = array_shift($color);
                array_push($color,$c);
            }

            if($newItems[$v['bn']] && $newItems[$v['bn']]['bn'] !=''){
                    $newItems[$v['bn']]['nums'] += $items[$k]['nums'];
                    $newItems[$v['bn']]['sendnum'] += $items[$k]['sendnum'];
					$newItems[$v['bn']]['sale_price']+=$items[$k]['sale_price'];
					$newItems[$v['bn']]['amount']+=$items[$k]['amount'];
            }else{
                if ($source == 'archive') {
                    $refund = $archive_ordObj->Get_refund_count ( $order_id, $v ['bn'] );
                    $items [$k] ['branch'] = $archive_ordObj->getBranchCodeByBnAndOd ( $v ['bn'], $order_id );
                }else{
                    $refund = $oReship_item->Get_refund_count ( $order_id, $v ['bn'], '', $after_service);
                    $items [$k] ['branch'] = $oReship_item->getBranchCodeByBnAndOd ( $v ['bn'], $order_id );
                }
                
                $items [$k] ['effective'] = $refund;

                $items [$k]['obj_type'] = $oObject[$v['obj_id']]['obj_type'];

                if ($oObject[$v['obj_id']]['ref']) {
                    $items [$k]['ref'] = $oObject[$v['obj_id']]['ref'];
                    $items [$k]['color'] = $objColor[$v['obj_id']];
                }

                
                $newItems[$v['bn']] = $items[$k];
            }
            $tmp_product[] = $items[$k]['product_id'];
        } 
		
		//换货拆先拆明细
		if($after_service=="change"){
			foreach($newItems as $bn=>$v){
				$averageSalesPrice=$averageAmountPrice=0;
				if($v['is_mcd_product']=="true"&&$v['nums']>=1){
					$averageSalesPrice=$v['sale_price']/$v['nums'];
					$averageAmountPrice=$v['amount']/$v['nums'];
					$effective=$v['effective'];
					 
					for($i=0;$i<$v['nums'];$i++){
						if($effective<=0){
							break;
						}
						
						$key=$bn."_".$i;
						
						$newItems[$key]=$newItems[$bn];
						$newItems[$key]['sale_price']=$averageSalesPrice;
						$newItems[$key]['amount']=$averageSalesPrice;
						$newItems[$key]['nums']=1;
						$newItems[$key]['sendnum']=1;
						if($effective=="1"){
							$newItems[$key]['effective']=$effective;
						}else{
							$newItems[$key]['effective']=$effective-1;
						}
						//通过接口获取可以换货的商品
						$arrChangeSku=array();
						if($arrChangeSku=kernel::single('omemagento_service_change')->getChangeSku($bn)){
							foreach($arrChangeSku['items'] as $k=>$product){
								$newItems[$key]['change'][$k]['bn']=$product['sku'];
								$newItems[$key]['change'][$k]['name']=$product['name'];
								$newItems[$key]['change'][$k]['price']=$product['price'];//$product['price'];
								
								$arrProduct=array();
								$arrProduct=$objProducts->getList("product_id",array('bn'=>$product['sku']));
								
								$newItems[$key]['change'][$k]['sale_store']=$objProducts->get_product_store(1,$arrProduct[0]['product_id']);
								$newItems[$key]['change'][$k]['product_id']=$arrProduct[0]['product_id'];
							}
						}
						/*$newItems[$key]['change'][0]['bn']='F041542789';
						$newItems[$key]['change'][0]['name']='真我';
						$newItems[$key]['change'][0]['price']='100';
						$newItems[$key]['change'][0]['sale_store']=$objProducts->get_product_store(1,'1661');
						$newItems[$key]['change'][0]['product_id']='1661';
							
						$newItems[$key]['change'][1]['bn']='F001601009';
						$newItems[$key]['change'][1]['name']='克丽丝汀迪奥真我香发喷雾';
						$newItems[$key]['change'][1]['price']='100';
						$newItems[$key]['change'][1]['sale_store']=$objProducts->get_product_store(1,'1444');
						$newItems[$key]['change'][1]['product_id']='1444';*/
						$effective--;
					}
					
					unset($newItems[$bn]);
				}
			}
		}
		//echo "<pre>";print_r($after_service);print_r($newItems);exit;
        $items = $newItems;
        if($type == 'return'){
            $this->pagedata['total_return_filter'] = implode(',',$tmp_product);
        }else{
            $this->pagedata['total_change_filter'] = implode(',',$tmp_product);
        }

        $branch_mode = &app::get ( 'ome' )->getConf ( 'ome.branch.mode' );
        $this->pagedata ['branch_mode'] = $branch_mode;
        $this->pagedata ['items'] = $items;
    }

    /**
     * 获取退入商品信息
     *
     * @return void
     * @author
     **/
    function ajax_getProductinfo_one(){
        $html = '';
        $source = trim($_GET['source']);
		$after_service=trim($_GET['after_service']);
        $this->get_data($_POST['order_id'],'return',$source,$after_service);
		$this->pagedata['return_type']=$after_service;
        $html = $this->fetch('admin/return_product/rchange/rc_html_t.html');
        echo $html;exit;
    }

    /**
     * 获取换出商品信息
     *
     * @return void
     * @author
     **/
    function ajax_getProductinfo_two(){
        $html = '';
        $this->get_data($_POST['order_id'],'change');
        $html = $this->fetch('admin/return_product/rchange/rc_html_c.html');
        echo $html;exit;
    }


    /**
     * 返回支付/退款明细
     *
     * @return void
     * @author
     **/
    function ajax_paydetail()
    {
        $html = '';
        $this->paydetail($_POST['order_id']);
        $html = $this->fetch('admin/return_product/rchange/paydetail.html');
        echo $html;exit;
    }

    function paydetail($order_id){
        $Orefunds = &$this->app->model('refunds');
        $Opayments = &$this->app->model('payments');
        $refunds = $Orefunds->getList('t_ready,refund_bn,money,paymethod,refund_refer',array('order_id'=>$order_id));
        $payments = $Opayments->getList('t_begin,payment_bn,money,paymethod,payment_refer',array('order_id'=>$order_id));
        $this->pagedata['payments'] = $payments;
        $this->pagedata['refunds'] = $refunds;
    }
    /**
     * 退换货单审核
     *
     * @return void
     * @author
     **/
    function check($reship_id){
       $obj_return_process = &app::get('ome')->model('return_process');
       $por_id = $obj_return_process->getList('por_id',array('reship_id'=>$reship_id));
       $this->pagedata['por_id'] = $por_id[0]['por_id'];
       $this->pagedata['act'] = 'save_check';
       $this->common_html(__FUNCTION__,$reship_id);
    }

    /**
    * 最终收货

    */
    function endcheck($reship_id){
       $obj_return_process = &app::get('ome')->model('return_process');
       $por_id = $obj_return_process->getList('por_id',array('reship_id'=>$reship_id));
       $this->pagedata['por_id'] = $por_id[0]['por_id'];
       $this->pagedata['act'] = 'save_endcheck';
       $this->common_html(__FUNCTION__,$reship_id);
    }
    /**
     * 保存审核信息
     *
     * @return void
     * @author
     **/
    function save_check($reship_id,$status,$is_anti = false)
    {
       $this->begin();
       if($reship_id){
            $Oreship = $this->app->model('reship');
            $archive_ordObj = app::get('archive')->model('orders');
            $reship = $Oreship->dump(array('reship_id'=>$reship_id),'order_id, is_check,reship_bn,return_type,reason,need_sv,source');
            $source = $reship['source'];
            if($reship['is_check'] == '1' && !$is_anti){
                $this->end(false,'改单据已审核过!');
            }
            if ($reship['is_check'] == '7') {
                $this->end(false,'此单据已完成!');
            }
            $oReship_item = &$this->app->model('reship_items');
            if($status == '1' && $reship['return_type'] == 'change'){
                
                $oReship_item->Get_items_count($reship_id,$result);
                if($result['return'] == '0'||$result['change'] == '0'){
                    $this->end(false,'由于提交信息有误，审核请求失败! 请确认后再提交。');
                }
                //生成一张虚拟的换货单，并锁定相应的商品库存
                /*=========*/

                /*=========*/
            }
            //判断库存
            if ((($reship['is_check']=='0' && $reship['need_sv'] == 'true') || $reship['is_check']=='12') && ($reship['return_type'] == 'change')){
                $pStockObj = kernel::single('console_stock_products');
                $change_item =kernel::single('console_reship')->change_items($reship_id);
                foreach ( $change_item as $item ) {
                    $usable_store = $pStockObj->get_branch_usable_store($item['branch_id'],$item['product_id']);
                    if ($item['num']>$usable_store) {
                        $this->end(false,'货号:'.$item['bn'].',可用库存不足');
                    }
                }

            }
            //$check_memo = '#审核原因#'.$_POST['reason'];
            $reason = unserialize($reship['reason']);
            if(isset($_POST['reason'])) {
                $reason['check'] = $_POST['reason'];
            }

            #最终确认时 残损确认判断
            if($reship['is_check'] == '10' || $reship['is_check'] == '11' || $reship['is_check'] == '9'){
                $normal_reship_item = $oReship_item->getList('*',array('reship_id'=>$reship_id,'normal_num|than'=>0),0,1);
                $reship_item = $oReship_item->getList('*',array('reship_id'=>$reship_id,'defective_num|than'=>0),0,1);
                if (count($normal_reship_item)==0 && count($reship_item)==0) {
                    $this->end(false,'良品或不良品数量至少有一种不为0!');
                }
                if (count($reship_item)>0) {
                    $branch_id = $reship_item[0]['branch_id'];
                    $damaged = kernel::single('console_iostockdata')->getDamagedbranch($branch_id);
                    if (!$damaged) {
                        $this->end(false,'由于有不良品入库，请设置主仓对应残仓');
                    }
                }
                
            }
            
            $updateData = array('is_check'=>$status,'reason'=>serialize($reason));
            if ($reship['is_check']=='0' && $reship['need_sv'] == 'true') {
                $updateData['check_time'] = time();//审核时间
            }
            if(isset($_POST['need_sv'])) {
                $updateData['need_sv'] = $_POST['need_sv'];
            }
            $Oreship->update($updateData,array('reship_id'=>$reship_id));
            $oOperation_log = &$this->app->model('operation_log');
            $schema = $this->app->model('reship')->schema['columns'];
            $memo = '审核状态:'.$schema['is_check']['type'][$status];
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);
            if (($reship['is_check']=='0' && $reship['need_sv'] == 'true') || $reship['is_check']=='12') {#发起通知单
                $reship_data = kernel::single('ome_receipt_reship')->reship_create(array('reship_id'=>$reship_id));
                //冻结
                //判断换货商品库存
                 
                if ($reship['return_type'] == 'change' ) {
                    kernel::single('console_reship')->change_freezeproduct($reship_id,'+');
                }
                $wms_id = kernel::single('ome_branch')->getWmsIdById($reship_data['branch_id']);
                $rsp_result = kernel::single('console_event_trigger_reship')->create($wms_id, $reship_data, false);
                //记录日志
                $rsp_result = json_encode($rsp_result);
                $memo = '发送至第三方:';
                $oOperation_log->write_log('reship@ome',$reship_id,$memo.$rsp_result);
                //
            }
            if($reship['is_check'] == '10' || $reship['is_check'] == '11' || $reship['is_check'] == '9') {
                if($Oreship->finish_aftersale($reship_id)){
					if (count($normal_reship_item)>0){
						$reshipLib = kernel::single('siso_receipt_iostock_reship');
						$result = $reshipLib->create(array('reship_id'=>$reship_id), $data, $msg);
					}
                    
                    if (count($reship_item)>0) {
                        $damagedreshipLib = kernel::single('siso_receipt_iostock_reshipdamaged');
                        $result = $damagedreshipLib->create(array('reship_id'=>$reship_id), $data, $msg);
                    }
                    #$result = kernel::single('ome_return_process')->do_iostock($_POST['por_id'],1,$msg);
                    #更新收货表为入库
                    //反审核质检
                    $process_sql = "UPDATE sdb_ome_return_process_items SET is_check='true' WHERE reship_id=".$reship_id." AND is_check='false'";
                    $Oreship->db->exec($process_sql);                    
                    if(!$result){
                        $this->end(false,'没有生成出入库明细!');
                    }
                }
            }
       }

       $this->end(true,'操作成功！');
    }

    
    /**
     * 最终收货保存
     * @param   type    $varname    description
     * @return  type    description
     * @access  public or private
     * 
     */
    function save_endcheck($reship_id,$status,$is_anti = false)
    {
       $this->begin();
       if($reship_id){
            $Oreship = $this->app->model('reship');
            $oReship_item = &$this->app->model('reship_items');
            $reship = $Oreship->dump(array('reship_id'=>$reship_id),'is_check,reship_bn,return_type,reason,need_sv');
            if($reship['is_check'] == '7'){
                $this->end(false,'改单据已完成!');
            }
            
            
            $normal_reship_item = $oReship_item->getList('*',array('reship_id'=>$reship_id,'normal_num|than'=>0),0,1);
            
            $reship_item = $oReship_item->getList('*',array('reship_id'=>$reship_id,'defective_num|than'=>0),0,1);
            if (count($normal_reship_item)==0 && count($reship_item)==0) {
                $this->end(false,'良品或不良品数量至少有一种不为0!');
            }
            if (count($reship_item)>0) {
                $branch_id = $reship_item[0]['branch_id'];
                $damaged = kernel::single('console_iostockdata')->getDamagedbranch($branch_id);
                if (!$damaged) {
                    $this->end(false,'由于有不良品入库，请设置主仓对应残仓');
                }
            }
            if($Oreship->finish_aftersale($reship_id)){
                $reshipLib = kernel::single('siso_receipt_iostock_reship');
                $result = $reshipLib->create(array('reship_id'=>$reship_id), $data, $msg);
                if (count($reship_item)>0) {
                    $damagedreshipLib = kernel::single('siso_receipt_iostock_reshipdamaged');
                    $damagedreshipLib->create(array('reship_id'=>$reship_id), $data, $msg);
                }
                #$result = kernel::single('ome_return_process')->do_iostock($_POST['por_id'],1,$msg);
                if(!$result){
                    $this->end(false,'没有生成出入库明细!');
                }
            }
       }

       $this->end(true,'操作成功！');
    } // end func
    /**
     * 退发货单编辑
     *
     * @return void
     * @author
     **/
    function edit($reship_id)
    {
        $return_type = array('return'=>'退货','change'=>'换货');
        $this->pagedata['return_type'] = $return_type;
        $this->pagedata['order_filter'] = array('pay_status'=>'1','ship_status'=>'1');
        $Oreship = $this->app->model('reship');
        $oOrder_pmt = &$this->app->model('order_pmt');
        $reship_data = $Oreship->getCheckinfo($reship_id,false);
        
        $this->paydetail($reship_data['order_id']);
        $oProblem = &$this->app->model('return_product_problem');
        $list = $oProblem->getList('problem_id,problem_name');
        $this->pagedata['problem_type'] = $list;
        $reship_data['return_type_name'] = $return_type[$reship_data['return_type']];

        $this->pagedata['reship_data'] = $reship_data;
        $pmts = $oOrder_pmt->getList('pmt_amount,pmt_describe',array('order_id'=>$reship_data['order_id']));
        $this->pagedata['pmts'] = $pmts;
        $this->pagedata['total_return_filter'] = $reship_data['total_return_filter'];
        $this->pagedata['total_change_filter'] = $reship_data['total_change_filter'];

        # 计算差价
        $this->pagedata['diffmoney'] = $reship_data['diff_money'];

        # 订单的支付明细
        $oPayments = $this->app->model('payments');
        $this->pagedata['payments'] = $oPayments->getList('payment_id,payment_bn,t_begin,download_time,money,paymethod',array('order_id'=>$reship_data['order_id']));

        # 订单的退款明细
        $oRefunds = $this->app->model('refunds');
        $this->pagedata['refunds'] = $oRefunds->getList('refund_bn,t_ready,download_time,money,paymethod',array('order_id'=>$reship_data['order_id']));
        $oOrders = &$this->app->model ( 'orders' );
        if (in_array($reship_data['source'],array('archive'))) {
            $oOrders = app::get('archive')->model ( 'orders' );
        }
        $order = $oOrders->dump ( array ('order_id' => $reship_data['order_id'] ),'*' );

        $this->pagedata['order'] = $order;
        //
        $branchtype = app::get('wms')->getConf('wms.branchset.type');
        $this->pagedata['branchtype'] =  $branchtype;
        $branchObj = app::get('ome')->model('branch');
        $branch_list = $branchObj->getlist('branch_id,name',array('disabled'=>'false'));
        $this->pagedata['branch_list'] = $branch_list;
        unset($branch_list);
	    $this->pagedata['source'] = $reship_data['source'];
        $this->display('admin/return_product/rchange/rchange.html');
    }

    /**
     * 反审核
     *
     * @return void
     * @author
     **/
    function anti_check($reship_id)
    {
       if(!$reship_id)
        die("单据号传递错误！");
       $Oreship = $this->app->model('reship');
       $reship = $Oreship->dump(array('reship_id'=>$reship_id),'reship_bn');
       $this->pagedata['reship_id'] = $reship_id;
       $this->pagedata['reship_bn'] = $reship['reship_bn'];
       $oOperation_log = &$this->app->model('operation_log');
       $memo = '进行反审核';
       $oOperation_log->write_log('reship@ome',$reship_id,$memo);
       $this->display('admin/return_product/rchange/anti_check.html');
    }

    /**
     * 入库单
     *
     * @return void
     * @author
     **/
    function ruku($reship_id){
        $this->pagedata['act'] = 'save_ruku';
        $this->pagedata['ruku_html'] = true;
        $Oreship = $this->app->model('reship');
        $reships = $Oreship->dump(array('reship_id'=>$reship_id),'order_id');
        $this->paydetail($reships['order_id']);
        $this->common_html(__FUNCTION__,$reship_id);
    }

    /**
     * 保存入库单信息
     * status 状态：
     *       5: 拒绝 生成一张发货单 商品明细为退入商品中的商品信息
     *       6：补差价，生成一张未付款的支付单
     *       8: 完成
     * @return void
     * @author
     **/
    function save_ruku($reship_id,$status){
        if(!$reship_id)
            die('单据号传递错误!');
        $this->begin();
        $data = kernel::single('base_component_request')->get_params(true);

        $Oreship = $this->app->model('reship');
        $Oreship->saveinfo($reship_id,$data,$status);
        $this->end(true,'操作成功！');
    }

    /**
     * 保存验收退换货单状态
     *
     * @return void
     * @author
     **/
    function save_accept_returned($reship_id,$status){
       $this->begin();
       if($reship_id){
            $oOperation_log = &$this->app->model('operation_log');
            $Oreship = $this->app->model('reship');
            $oProduct_pro = &$this->app->model('return_process');
            $oProduct_pro_detail = $oProduct_pro->product_detail($reship_id);
            $reship = $Oreship->dump(array('reship_id'=>$reship_id),'is_check,return_id,reason');
            if($reship['is_check'] == '3'){
                $this->end(false,'改单据已验收过!');
            }

            //增加售后收货前的扩展
            foreach(kernel::servicelist('ome.aftersale') as $o){
                if(method_exists($o,'pre_sv_charge')){
                    if(!$o->pre_sv_charge($_POST,$memo)){
                        $this->end(false, app::get('base')->_($memo));
                    }
                }
            }

            $data['branch_name'] = $oProduct_pro_detail['branch_name'];
            $data['memo'] = $_POST['info']['memo'];
            $data['shipcompany'] = $_POST['info']['shipcompany'];
            $data['shiplogino'] = $_POST['info']['shiplogino'];
            $data['shipmoney'] = $_POST['info']['shipmoney'];
            $data['shipdaofu'] = $_POST['info']['daofu'] == 1 ? 1 : 0;
            $data['shiptime'] = time();



            if($status == '4'){
                $addmemo = ',拒绝收货';
                $refuse_memo = unserialize($reship['reason']);
                $refuse_memo .= '#收货原因#'.$_POST['info']['refuse_memo'];
                $prodata = array('reship_id'=>$reship_id,'reason'=>serialize($refuse_memo));
                $oProduct_pro->cancel_process($prodata);
            }elseif($status == '3'){
                $prodata = array('reship_id'=>$reship_id,'process_data'=>serialize($data));
                $addmemo = ',收货成功';
                $oProduct_pro->save_return_process($prodata);
            }
            $filter = array(
                'is_check'=>$status,
                'return_logi_name'=>$data['shipcompany'],
                'return_logi_no'=>$data['shiplogino'],
            );
            $Oreship->update($filter,array('reship_id'=>$reship_id));

            if($reship['return_id']){
                $Oproduct = $this->app->model('return_product');
                $recieved = 'false';
                if($status == '3'){
                   $recieved = 'true';
                }
                $Oproduct->update(array('process_data'=>serialize($data),'recieved'=>$recieved),array('return_id'=>$reship['return_id']));
            }


            $Oreship_items = &$this->app->model('reship_items');
            $oBranch = &$this->app->model('branch');
            $reship_items = $Oreship_items->getList('branch_id',array('reship_id'=>$reship_id,'return_type'=>'return'));
            $branch_name = array();
            foreach($reship_items as $k=>$v){
                $branch_name[] = $oBranch->Get_name($v['branch_id']);
            }
            $add_name = array_unique($branch_name);
            $memo='仓库:'.implode(',', $add_name).$addmemo;
            $oOperation_log = &$this->app->model('operation_log');
            if($reship['return_id']){
                $oOperation_log->write_log('return@ome',$reship['return_id'],$memo);
            }
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);

           if($oProduct_pro_detail['return_id']){
               //售后申请状态更新
                foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
                    if(method_exists($instance,'update_status')){
                        $instance->update_status($oProduct_pro_detail['return_id']);
                    }
                }
           }


           //增加售后收货前的扩展
            foreach(kernel::servicelist('ome.aftersale') as $o){
                if(method_exists($o,'after_sv_charge')){
                    $o->after_sv_charge($_POST);
                }
            }
       }

       $this->end(true,'操作成功！');
    }


    /**
     * 收货拒绝理由
     *
     * @return void
     * @author
     **/
    function refuse_reason($reship_id,$status,$type){
        if($type == 'returned'){
           $refuse_memo = '收货拒绝';
           $ctl = 'admin_return_rchange';
           $act = 'save_accept_returned';
        }else{
           $refuse_memo = '质检拒绝';
           $ctl = 'admin_return_sv';
           $act = 'tosave';
        }
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->pagedata['refuse_memo'] = $refuse_memo;
        $this->pagedata['from_type'] = $_GET['from_type'];
        $this->pagedata['ctl'] = $ctl;
        $this->pagedata['act'] = $act;
        $this->pagedata['reship_id'] = $reship_id;
        $this->pagedata['status'] = $status;
        $this->display('admin/return_product/rchange/refuse_reason.html');
    }

    /**
     * 验收退换货单
     *
     * @return void
     * @author
     **/
    function accept_returned($reship_id){
        $this->pagedata['act'] = 'save_accept_returned';
        $this->common_html(__FUNCTION__,$reship_id);
    }

    /**
     * 验收退换货和审核公共页面
     *
     * @return void
     * @author
     **/
    function common_html($display_button = 'check',$reship_id){
        if(!$reship_id) die('单据号传递错误!');
        $Oreship = $this->app->model('reship');
        $reship_data = $Oreship->getCheckinfo($reship_id);
        $reship_data['reason'] = unserialize($reship_data['reason']);
        $oProblem = &$this->app->model('return_product_problem');
        $list = $oProblem->dump(array('problem_id'=>$reship_data['problem_id']),'problem_name');

        # 支付单
        $this->pagedata['payments'] = $this->app->model('payments')->getList('*',array('order_id'=>$reship_data['order_id']));

        # 退款单
        $this->pagedata['refunds'] = $this->app->model('refunds')->getList('*',array('order_id'=>$reship_data['order_id']));

        $reship_data['problem_type'] = $list['problem_name'];

        $this->pagedata['reship_data'] = $reship_data;
        $this->pagedata['display_button'] = $display_button;

        $this->display('admin/return_product/rchange/check.html');
    }

    function getProducts(){

        $pro_id = $_POST['product_id'];
        $type = $_GET['type'];
        if (is_array($pro_id)){
            $filter['product_id'] = $pro_id;
        }
        $Obranch = &app::get('ome')->model('branch');
        $pObj = &app::get('ome')->model('products');
        $pObj->filter_use_like = true;
        $data = $pObj->getList('visibility,product_id,bn,name,price,store,store_freeze',$filter,0,-1);
        $pObj->filter_use_like = false;
        $branchlist = $Obranch->Get_branchlist();
        if (!empty($data)){
            foreach ($data as $k => $item){
                $item['num'] = 1;

                //$item['price'] = app::get('purchase')->model('po')->getPurchsePrice($item['product_id'], 'asc');
                if (!$item['price']){
                    $item['price'] = 0;
                }
                $branch_info = '';
                foreach ($branchlist as $k => $v) {
                    if($v['is_deliv_branch'] == 'false' && $type == 'change') continue;

                    $checked = '';
                    if($k==0){
                       $checked = 'checked="checked"';
                       $item['sale_store'] = $pObj->get_product_store($v['branch_id'],$item['product_id']);
                    }
                    $branch_info .= '<input type="radio" '.$checked.' bn="'.$item['bn'].'" onclick="choose_branch(this,'.$v['branch_id'].','.$item['product_id'].');" name="'.$type.'[product][branch_id]['.$item['bn'].']" value="'.$v['branch_id'].'">'.$v['name'].'&nbsp;';
                }
                $item['branch_info'] = $branch_info;
                $item['type'] = $type;

                $rows[] = $item;
            }
        }
        echo "window.autocompleter_json=".json_encode($rows);exit;
    }

    /**
     * 构造一个商品列表
     *
     * @return void
     * @author
     **/
    function getGoods($product_id)
    {
        $base_filter['visibility'] = 'true';
        $base_filter['product_id|notin'] = explode(',',$product_id);

        $params = array(
           'title'=>'商品列表',
           'base_filter' => $base_filter,
           'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_setcol'=>true,
            'use_buildin_refresh'=>true,
            'orderBy' =>'product_id DESC',
            'alertpage_finder'=>true,
            'use_view_tab' => false,
        );
        $this->finder('ome_mdl_products',$params);
    }

    /**
     * 取消退换货单
     *
     * @return void
     * @author
     **/
    function do_cancel($reship_id)
    {
        if(!$reship_id)
            die("单据号传递错误！");
        $Oreship = app::get('ome')->model('reship');
        $oOperation_log = app::get('ome')->model('operation_log');//写日志
        $reship = $Oreship->dump(array('reship_id'=>$reship_id),'reship_bn,return_id,return_type');
        if($_POST){
            $reship_id = $_POST['reship_id'];
            #触发奇门-WMS单据取消接口(退单取消)
            $res = kernel::single('qmwms_request_omsqm')->orderCancel($reship_id,'退单取消','return');
            if($res['status'] == 'success'){
                $memo = '状态:拒绝';
                $Oreship->update(array('is_check'=>'5','t_end'=>time()),array('reship_id'=>$reship_id));
                //判断是否是已确认拒绝如果是需要释放冻结库存
                if ($reship['return_type'] == 'change') {
                    kernel::single('console_reship')->change_freezeproduct($reship_id,'-');
                }
                if($reship['return_id']){
                    $oOperation_log->write_log('return@ome',$reship['return_id'],$memo);
                    $data = array ('return_id' => $reship['return_id'], 'status' => '5', 'last_modified' => time () );
                    $oProduct = app::get('ome')->model ( 'return_product' );
                    $oProduct->update_status ( $data );
                }

            }else{
                $memo = 'wms返回:'.$res['res_msg'];
            }
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);
        }
        $this->pagedata['reship_bn'] = $reship['reship_bn'];
        $this->pagedata['reship_id'] = $reship_id;
        $this->display('admin/return_product/rchange/do_cancel.html');

    }

    /**
     * 打回发货单方法
     *
     * @return void
     * @author
     **/
    function do_back($reship_id)
    {
        if(!$reship_id)
            die("单据号传递错误！");
        $Oreship = $this->app->model('reship');
        $Oreturn_products = $this->app->model('return_product');
        if($_POST){
            $reship_id = $_POST['reship_id'];
            $memo = '';
            $delivery_num=0;//发货单数量
            $oReship = $this->app->model('reship');
            $oDelivery = $this->app->model('delivery');
            $oDelivery_order= $this->app->model('delivery_order');
            $oDelivery_items = $this->app->model('delivery_items');
            $oOrder_items = $this->app->model('order_items');
            $reshipinfo = $oReship->dump(array('reship_id'=>$reship_id));
            $order_items = $oOrder_items->getList('*',array('order_id'=>$reshipinfo['order_id']));
            $order_id = $reshipinfo['order_id'];
            $delivery_order = $oDelivery_order->dump(array('order_id'=>$order_id),'delivery_id');
            $delivery_id = $delivery_order['delivery_id'];
            $is_archive = kernel::single('archive_order')->is_archive($reshipinfo['source']);
            if ($is_archive) {
                $oDelivery = app::get('archive')->model('delivery');
            }
            $delivery_items = $this->app->model('reship_items')->getList('product_id,bn,product_name,num as number',array('reship_id'=>$reship_id,'return_type'=>'return'));

            $deliveryinfo = $oDelivery->dump($delivery_id,'branch_id,delivery_id,logi_id,delivery_cost_actual');
            $Process_data = array_merge($deliveryinfo,$reshipinfo);
            $Process_data['logi_id'] = $reshipinfo['logi_id']!='' ? $reshipinfo['logi_id'] :$deliveryinfo['logi_id'];
            $Process_data['logi_name'] = $reshipinfo['logi_name']!='' ? $reshipinfo['logi_name'] :$deliveryinfo['logi_name'];
            $Process_data['delivery_items'] = $delivery_items;

            define('FRST_TRIGGER_OBJECT_TYPE','发货单：售后申请原样寄回生成发货单');
            define('FRST_TRIGGER_ACTION_TYPE','ome_mdl_return_product：saveinfo');

            $new_delivery_bn = $oReship->create_delivery($Process_data);
            $delivery_memo = '，发货单号为:'.$new_delivery_bn;
            $delivery_num = 1;
            $oReship->update(array('is_check'=>'5'),array('reship_id'=>$reship_id));
            if($reshipinfo['return_id']){
                $Oreturn_products->update(array('status'=>'5'),array('return_id'=>$reshipinfo['return_id']));
            }

            if($delivery_num!=0){
                $memo.='   生成了'.$delivery_num.'张发货单'.$delivery_memo;
            }

            //售后原样寄回埋点
            $new_delivery_info = $oDelivery->dump(array('delivery_bn'=>$new_delivery_bn),'delivery_id');
            $original_data = kernel::single('ome_event_data_delivery')->generate($new_delivery_info['delivery_id']);
            $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
            kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);

            $oOperation_log = &$this->app->model('operation_log');//写日志
            if($reshipinfo['return_id']){
               $oOperation_log->write_log('return@ome',$reshipinfo['return_id'],$memo);
            }
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);
            echo '生成退回发货单成功!';exit;
        }
        $reship = $Oreship->dump(array('reship_id'=>$reship_id),'reship_bn');
        $this->pagedata['reship_bn'] = $reship['reship_bn'];
        $this->pagedata['reship_id'] = $reship_id;
        $this->display('admin/return_product/rchange/do_back.html');

    }

    /**
     * 根据仓库ID和货品ID 获取相应的库存数量
     *
     * @return void
     * @author
     **/
    function ajax_showStore()
    {
        $branch_id = $_POST['branch_id'];
        $product_id = $_POST['product_id'];
        $result = array('res'=>'fail','msg'=>'product_id is empty');
        if($product_id && $branch_id){
            $Oproduct = &$this->app->model('products');
            $store = $Oproduct->get_product_store($branch_id,$product_id);
            $result = array('res'=>'succ','msg'=>$store);
        }

        echo json_encode($result);exit;
    }

    /**
     * 根据订单ID 获取相应的优惠方案信息
     *
     * @return void
     * @author
     **/
    function ajax_getPmts(){

        $order_id = $_POST['order_id'];
        if($order_id){
            $oOrder_pmt = &$this->app->model('order_pmt');
            $pmts = $oOrder_pmt->getList('pmt_amount,pmt_describe',array('order_id'=>$order_id));
            $this->pagedata['pmts'] = $pmts;
            $html = $this->fetch('admin/return_product/rchange/show_pmt.html');
            echo $html;exit;
        }
    }


    /**
     * 计算补差价金额
     *
     * @return void
     * @author chenping<chenping@shopex.cn>
     **/
    public function calDiffAmount($reship_id,$return_type)
    {	
        $post = kernel::single('base_component_request')->get_post();
        $is_check = $post['is_check'];
		//zjrMCD换货
		if($return_type=="change"){
			$objReship = $this->app->model('reship');
			//先无脑替换zjr
			$arr=array();
			$arr=$objReship->getMcd($post,false);
			$post['change']['product']=$arr['change']['product'];
		}
		
        # 进行数量判断
        if (isset($post['return']['goods_bn']) && is_array($post['return']['goods_bn'])) {
            foreach ($post['return']['goods_bn'] as $pbn) {
                if ($is_check == '11') {
                    if ($post['return']['normal_num'][$pbn] > $post['return']['effective'][$pbn]) {
                        $error = array(
                            'error' => "货品【{$pbn}】的入库数量大于可退入数量!",
                        );
                        break;
                    }
                }else{
					if(ereg("^[0-9]*[1-9][0-9]*$",$post['return']['num'][$pbn])!=1){
						 $error = array(
                            'error' => "申请数量请填写正整数！",
                        );
						 break;
					}
                    if ($post['return']['num'][$pbn] > $post['return']['effective'][$pbn]) {
                        $error = array(
                            'error' => "货品【{$pbn}】的申请数量大于可退入数量!",
                        );
                        break;
                    }
                }
                
            }
            if ($error) {
                echo json_encode($error);exit;
            }
        }

        $money = kernel::single('ome_return_rchange')->calDiffAmount($post);
        $moneyValue = $money;
        $curModel = app::get('eccommon')->model('currency');
        foreach ($money as &$value) {
            $value = $curModel->changer($value);
        }
        $money['mvalue'] = $moneyValue;

        # 判断退款金额是否大于订单金额
        if ($post['order_id']) {
            $source = $_GET['source'];

            if ($source && in_array($source,array('archive'))) {
                $orderInfo = app::get('archive')->model('orders')->getList('total_amount,payed',array('order_id'=>$post['order_id']),0,1);

            }else{
                $orderInfo = $this->app->model('orders')->getList('total_amount,payed',array('order_id'=>$post['order_id']),0,1);
            }
            
            if ($moneyValue['totalmoney']-$post['bcmoney']>$orderInfo[0]['payed']) {
                $error = array(
                    'error' => "退款金额不能大于订单的已支付金额!",
                );
                echo json_encode($error);exit;
            }

            if ($return_type == 'return' && $moneyValue['totalmoney']<0) {
                $error = array(
                    'error' => "退款金额不能小于零!",
                );
                echo json_encode($error);exit;
            }
        }


        echo json_encode($money);exit;

    }

    /**
     * 更新
     *
     * @return void
     * @author
     **/
    public function update_reship($reship_id)
    {
        $finder_id = $_GET['finder_id'];
        $this->begin();
        if (!$reship_id) {
            $this->end(false,$this->app->_('退换货单不存在!'));
        }

        $post = kernel::single('base_component_request')->get_post();
        if (empty($post['return_logi_no'])) {
            $this->end(false,$this->app->_('物流单号不能为空!'));
        }
        if (empty($post['return_logi_name'])) {
            $this->end(false,$this->app->_('物流公司不能为空!'));
        }

        $reshipModel = $this->app->model('reship');
        $isExit = $reshipModel->getList('reship_id',array('return_logi_no'=>$post['return_logi_no'],'reship_id|noequal'=>$reship_id,'is_check|noequal'=>'5'));
        if ($isExit) {
            //$this->end(false,$this->app->_('物流单号已经存在!'));
        }

        $post = kernel::single('base_component_request')->get_post();
        $reshipUpdate = array(
            'return_logi_no' => $post['return_logi_no'],
            'return_logi_name' => $post['return_logi_name'],
        );
        $result = $reshipModel->update($reshipUpdate,array('reship_id'=>$reship_id));

        # 记LOG
        $corp = $this->app->model('dly_corp')->getList('name',array('corp_id'=>$post['return_logi_name']),0,1);
        $oOperation_log = &$this->app->model('operation_log');
        $memo = '更改退回物流单号('.$post['return_logi_no'].'),退回物流公司('.$corp[0]['name'].')';
        $oOperation_log->write_log('reship@ome',$reship_id,$memo);

        $msg = $result ? $this->app->_('更新成功!') : $this->app->_('更新失败');
        $this->end($result,$msg);
    }

    /**
     * @description 选择补差价订单
     * @access public
     * @param Int $order_id 原订单ID
     * @param Int $page 页码
     * @return void
     */
    public function selectDiffOrder($order_id,$page=1)
    {
        
        if(empty($order_id)){
            $result = array(
                'error' => $this->app->_('退货订单不存在!'),
            );
            echo '退货订单不存在!';exit;
        }

        $pagelimit = 20;

        $orderModel = $this->app->model('orders');
        $order = $orderModel->getList('member_id,total_amount,shop_id',array('order_id'=>$order_id),0,1);
        if (!$order) {
            
            $order = app::get('archive')->model('orders')->getList('member_id,total_amount,shop_id',array('order_id'=>$order_id),0,1);
            if (!$order) {
                $result = array(
                'error' => $this->app->_('退货订单信息不存在!'),
                );
                echo '退货订单信息不存在!';exit;
            }
            
        }

        # 查询该会员的所有订单
        $filter = array(
            'member_id' => $order[0]['member_id'],
            //'total_amount|lthan' => $order[0][''],
            'pay_status' => '1',
            'ship_status' => '0',
            'status'  => 'active',
            'process_status' => 'unconfirmed',
            'shop_id' => $order[0]['shop_id'],
            'order_id|noequal' => $order_id,
        );
        $diffOrders = $orderModel->getList('order_id,order_bn,total_amount,cost_freight,cost_protect,tostr,shop_id,member_id,createtime,pay_status,ship_status',$filter,($page-1)*$pagelimit,$pagelimit,'createtime desc');
        
        if ($diffOrders) {
            foreach ($diffOrders as $key=>$diffOrder) {
                $diffOrders[$key]['uname'] = &$members[$diffOrder['member_id']];
                $diffOrders[$key]['shop_name'] = &$shops[$diffOrder['shop_id']];
                $diffOrders[$key]['pay_status'] = $orderModel->schema['columns']['pay_status']['type'][$diffOrder['pay_status']];
                $diffOrders[$key]['ship_status'] = $orderModel->schema['columns']['ship_status']['type'][$diffOrder['ship_status']];

                $member_ids[] = $diffOrder['member_id'];
                $shop_ids[] = $diffOrder['shop_id'];
            }

            $memberModel = $this->app->model('members');
            $memberList = $memberModel->getList('member_id,uname,name',array('member_id'=>$member_ids));
            foreach ($memberList as $key=>$member) {
                $members[$member['member_id']] = $member['uname'];
            }

            $shopModel = $this->app->model('shop');
            $shopList = $shopModel->getList('shop_id,name',array('shop_id' => $shop_ids));
            foreach ($shopList as $key=>$shop) {
                $shops[$shop['shop_id']] = $shop['name'];
            }

            $this->pagedata['diffOrders'] = $diffOrders;

            $count = $orderModel->count($filter);

            $totalpage = ceil($count/$pagelimit);
            $pager = $this->ui()->pager(array(
                'current'=>$page,
                'total'=>$totalpage,
                'link'=>'javascript:gotopage(%d);',
            ));
            $this->pagedata['pager'] = $pager;
        }

        $this->pagedata['order_id'] = $order_id;

        if ($page==1) {
            $view = 'admin/return_product/rchange/diff_orders.html';
        }else{
            $view = 'admin/return_product/rchange/diff_orders_container.html';
        }
        $this->display($view);
    }

    /**
     * @description 补差价订单选定确认
     * @access public
     * @param void
     * @return void
     */
    public function diffOrderSelected()
    {

        $post = kernel::single('base_component_request')->get_post();

        $order = kernel::single('ome_return_rchange')->diffOrderValidate($post,$errormsg);

        if ($order === false) {
            $this->pagedata['errormsg'] = $errormsg;
        }

        $this->pagedata['order'] = $order;

        $this->display('admin/return_product/rchange/diff_orders_confirm.html');
    }

    /**
     * @description 质检异常处理页
     * @access public
     * @param void
     * @return void
     */
    public function processException($reship_id)
    {
        $reshipModel = $this->app->model('reship');
        $reason = $reshipModel->select()->columns('reason')->where('reship_id=?',$reship_id)->instance()->fetch_one();
        $reason = unserialize($reason);
        $this->pagedata['reason'] = $reason['sv'];
        $this->pagedata['reship_id'] = $reship_id;
        $this->display('admin/return_product/rchange/process_exception.html');
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function doException()
    {
        $this->begin();
        $reship_id = $_POST['reship_id'];
        if (!$reship_id) {
            $this->end(false,$this->app->_('退换货单据不存在!'));
        }

        $op_info = kernel::single('ome_func')->getDesktopUser();

        $reshipModel = $this->app->model('reship');
        $row = $reshipModel->select()->columns('reason,is_check')->where('reship_id=?',$reship_id)->instance()->fetch_row();

        if ($row['is_check'] == '10') {
            $this->end(false,'对不起！该单据已经置为异常!');
        }

        $reason = $row['reason'];

        $reason = (array)unserialize($reason);

        $reason['sv'][] = array(
            'op_id' => $op_info['op_id'],
            'op_name' => $op_info['op_name'],
            'reason' => $_POST['reason'],
            'createtime' => time(),
        );

        $updateData = array(
            'reason' => serialize($reason),
            'is_check' => '10',
        );

        $reshipModel->update($updateData,array('reship_id'=>$reship_id));

        # 写日志
        $this->app->model('operation_log')->write_log('reship@ome',$reship_id,'质检异常，重新审核');

        $this->end(true);
    }

    
    /**
     * 发送退货单至第三方
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batch_sync()
    {
        $this->begin('');
        kernel::database()->exec('commit');
        $ids = $_POST['reship_id'];
        $oReship = &app::get('ome')->model('reship');
        if ($ids) {
            foreach ($ids as $reship_id ) {
                $reship_list = $oReship->dump(array($reship_id=>$reship_id,'is_check'=>1),'reship_id');
            
                if ($reship_list) {
                    $reship_data = kernel::single('ome_receipt_reship')->reship_create(array('reship_id'=>$reship_id));
                    $wms_id = kernel::single('ome_branch')->getWmsIdById($reship_data['branch_id']);
                    kernel::single('console_event_trigger_reship')->create($wms_id, $reship_data, false);
                }
            }
            
        }
        
        $this->end(true, '命令已经被成功发送！！');
    }
}