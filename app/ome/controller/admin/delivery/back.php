<?php
class ome_ctl_admin_delivery_back extends desktop_controller {

    var $name = "退回服务";
    var $workground = "wms_center";


    /**
     *
     * 拒收退货单列表
     */
    function index(){

        #如果没有导出权限，则屏蔽导出按钮
        $is_export = kernel::single('desktop_user')->has_permission('aftersale_rchange_export');
        $params = array(
            'title' => '拒收单',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_filter'=>true,
        );

        $params['base_filter']['return_type'] = array('refuse');

        $this->finder ( 'ome_mdl_reship_refuse' , $params );
    }



    //未发货 已发货 全部
    function _views(){
        $oDelivery = app::get('ome')->model('reship');
        $base_filter = array(
            'return_type' => 'refuse',
           

        );
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>app::get('base')->_('审核成功'),'filter'=>array('is_check'=>array('1')),'optional'=>false),
            2 => array('label'=>app::get('base')->_('已完成'),'filter'=>array('is_check'=>array('7')),'optional'=>false),
           
        );
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $oDelivery->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }

        return $sub_menu;
    }
    /**
     * 发货拒收默认页
     */
    function check(){
        if($_POST['order_bn']){
            
            $order_bn = trim($_POST['order_bn']);

            $orderObj = $this->app->model('orders');
            $deliveryObj = app::get('ome')->model('delivery');
            $order_list = $orderObj->getList('pay_status,ship_status,is_cod,order_id',array('order_bn'=>$order_bn,'ship_status'=>'1'));
             if(!$order_list && !$has_error){
                $has_error = true;
                $msg = '没有找到对应的订单号';
            }
            foreach ($order_list as $order ) {
                $order_id = $order['order_id'];
                if(($order['ship_status'] != '1' && $order['ship_status'] != '2' ) && !$has_error){
                    $has_error = true;
                    $msg = '当前发货状态的订单无法做拒收处理';
                    break;
                }

                if(($order['shipping']['is_cod'] == 'true' ) && !$has_error){
                    $has_error = true;
                    $msg = '货到付款状态的订单无法做退回处理';
                    break;
                }
                $return = $orderObj->db->select("SELECT * from sdb_ome_return_product WHERE order_id=".$order_id." AND `status` not in('5')");
                if ($return) {
                    $has_error = true;
                    $msg = '当前订单已有相关售后单据';
                    break;
                }
                $reship = $orderObj->db->select("SELECT * from sdb_ome_reship WHERE order_id=".$order_id." AND `status` not in('5')");
                if ($reship) {
                    $has_error = true;
                    $msg = '当前订单已有相关退货单据';
                    break;
                }
                
            
                $delivery = $deliveryObj->getDeliverIdByOrderId($order_id);
                if (!$delivery) {
                    $has_error = true;
                    $msg = '当前订单没有发货单';
                    break;
                }
            }
            
            
            if($has_error){
                $this->pagedata['error_msg'] = $msg;
                $this->page("admin/delivery/return/check.html");
            }else{
               
                $this->process(current($delivery),(array)$order_id);
            }
        }else{
            $this->page("admin/delivery/return/check.html");
        }
    }

    /**
     * 显示待拒收发货单明细及相关信息
     *
     **/
    function process($deliveryId,$orderIds)
    {
       
        $deliveryObj = $this->app->model('delivery');
        $deliveryInfo = $deliveryObj->dump($deliveryId);
        $deliveryItems = $deliveryObj->getItemsByDeliveryId($deliveryId);
        
        $branchObj = app::get('ome')->model('branch');
        $delivery_branch = $branchObj->Get_name($deliveryInfo['branch_id']);
        $branch_lists = $branchObj->getAllBranchs('branch_id,name');

        $this->pagedata['info'] = $deliveryInfo;
        $this->pagedata['items'] = $deliveryItems;
        $this->pagedata['delivery_branch'] = $delivery_branch;
        $this->pagedata['branch_lists'] = $branch_lists;
        $this->pagedata['deliveryId'] = $deliveryId;
        $this->pagedata['orderIds'] = implode(",",$orderIds);
        $this->page("admin/delivery/return/process_show.html");
    }

    /**
     * 执行发货拒收的具体数据处理
     */
    function doprocess(){
        $this->begin();
        $delivery_id = $_POST['delivery_id'];
        $orderIdString = $_POST['order_ids'];

        $productIds = $_POST['product_id'];
       
        $instock_branch = $_POST['instock_branch'];

        $branchLib = kernel::single('ome_branch');
        $channelLib = kernel::single('channel_func');
        $wms_id = $branchLib->getWmsIdById($instock_branch);
        if ($wms_id ) {
            $is_selfWms = $channelLib->isSelfWms($wms_id);
        }
        
        $reshipObj = $this->app->model('reship');
        $deliveryObj = $this->app->model('delivery');
        $orderObj = $this->app->model('orders');
        $operationLogObj = $this->app->model('operation_log');
        $shopObj = $this->app->model('shop');
        $productsObj = $this->app->model('products');
        $items_detailObj = $this->app->model('delivery_items_detail');

        $deliveryInfo = $deliveryObj->dump($delivery_id);
        $shopInfo = $shopObj->dump(array('shop_id'=>$deliveryInfo['shop_id']),'node_type,node_id');
        $c2c_shop_type = ome_shop_type::shop_list();
        
        $op_id = kernel::single('desktop_user')->get_id();

        $orderIds = explode(',',$orderIdString);

        foreach((array)$orderIds as $orderid){
            $reshipData = array();
            $orderItems = array();

            $orderdata = $orderObj->dump($orderid);
            $orderItems = $items_detailObj->getlist('*',array('order_id'=>$orderid,'delivery_id'=>$delivery_id));

            $reshipData = array(
        		'status' => 'succ',
                'order_id'=> $orderid,
                'member_id'=> $deliveryInfo['member_id'],
                'return_logi_name'=> $deliveryInfo['logi_name'],
                'return_type'=> 'refuse',
                'return_logi_no'=> $deliveryInfo['logi_no'],
                'logi_name'=> $deliveryInfo['logi_name'],
                'logi_no'=> $deliveryInfo['logi_no'],
                'logi_id' => $deliveryInfo['logi_id'],
                'delivery'=> $deliveryInfo['delivery'],
                'memo'=> '',
                'is_check'=>$is_selfWms ? '7' : '1',
                'op_id'=>$op_id,
            	't_begin'=>time(),
                't_end'=>0,
                'shop_id'=>$deliveryInfo['shop_id'],
                'reship_bn'=>$reshipObj->gen_id(),
    			'ship_name'=>$deliveryInfo['consignee']['name'],
                'ship_addr'=>$deliveryInfo['consignee']['addr'],
                'ship_zip'=>$deliveryInfo['consignee']['zip'],
                'ship_tel'=>$deliveryInfo['consignee']['telephone'],
                'ship_mobile'=>$deliveryInfo['consignee']['mobile'],
                'ship_email'=>$deliveryInfo['consignee']['email'],
                'ship_area'=>$deliveryInfo['consignee']['area'],
                'branch_id' => $instock_branch,
                'check_time'=>time(),
            );

            foreach($orderItems as $k =>$orderitem){
                
        			$reshipData['reship_items'][$k] = array(
        				'bn' => $orderitem['bn'],
        				'product_name' => $orderitem['name'],
        			    'product_id' => $orderitem['product_id'],
        				'num' => $orderitem['number'],
        			    'branch_id' => $instock_branch,
        			    'op_id' => $op_id,
        			    'return_type' => 'refuse'
                    );
               
            }

            //生成退货单
            if($reshipObj->save($reshipData)){
                //退货单创建 API
                if(!empty($shopInfo['node_id']) && !in_array($shopInfo['node_type'],$c2c_shop_type)){
                    foreach(kernel::servicelist('service.reship') as $object=>$instance){
                        if(method_exists($instance,'reship')){
                            $instance->reship($reshipData['reship_id']);
                        }
                    }
                }
                if ($is_selfWms) {
                    //发货单关联订单sendnum扣减
                    foreach($orderItems as $orderitem){

                        $orderObj->db->exec('UPDATE sdb_ome_order_items SET return_num=return_num+ '.$orderitem['number'].' WHERE order_id='.$orderid.' AND bn=\''.$orderitem['bn'].'\' AND obj_id='.$orderitem['order_obj_id']);

                    }

                    //订单相关状态变更

                    kernel::single('ome_delivery_refuse')->update_orderStatus($orderid);

                    //增加拒收退货入库明细
                    kernel::single('ome_delivery_refuse')->do_iostock($reshipData['reship_id'],1,$msg);
                    //负销售单
                    if ($orderdata['status'] == 'finish') {
                        kernel::single('sales_aftersale')->generate_aftersale($reshipData['reship_id'],'refuse');
                    }
                     
                    //订单添加相应的操作日志
                    $operationLogObj->write_log('order_refuse@ome', $orderid, "发货后退回，订单做退货处理");

                } else{
                //发送至第三方仓
                    $reship_data = kernel::single('ome_delivery_refuse')->reship_create($reshipData['reship_id']);
                    kernel::single('console_event_trigger_reship')->create($wms_id, $reship_data, false);
                }
                
                
            }else{
                $this->end(false, app::get('base')->_('发货拒收确认失败'));
            }
        }
		kernel::single('omeftp_service_back')->delivery($delivery_id,'拒收');
		//如果是货到付款，将订单状态更新为取消
		if($orderdata['shipping']['is_cod']=='true'){
			kernel::single('omemagento_service_order')->update_status($orderdata['order_bn'],'canceled');
		}
        if ($is_selfWms) {
            //更新发货单状态为退回
            //$deliveryObj->db->exec("UPDATE sdb_ome_delivery SET `status`='return_back' WHERE delivery_id=".$delivery_id." AND `status`='succ'");
            //确认是否第三方仓
            //$deliveryObj->db->exec("UPDATE sdb_wms_delivery SET `status`='1' WHERE outer_delivery_bn='".$deliveryInfo['delivery_bn']."'");
            
            //
            //$operationLogObj->write_log('delivery_back@ome', $delivery_id, "发货单退回");
        }
        $this->end(true, app::get('base')->_('发货拒收确认成功'));
    }

    
     
    

}
