<?php
class ome_finder_orders{
    var $detail_basic = '基本信息';
	var $detail_invoice = '发票相关';
    var $detail_goods = '订单明细';
    var $detail_pmt = '优惠方案';
    var $detail_bill = '收退款记录';
    var $detail_refund_apply = '退款申请记录';
    var $detail_delivery = '退发货记录';
    var $detail_mark = '订单备注';
    var $detail_abnormal = '订单异常备注';
    var $detail_history = '订单操作记录';
    //var $detail_aftersale = '售后记录';
    var $detail_custom_mark = '订单附言';
    var $detail_shipment = '发货日志';
    function __construct(){
        if($_GET['ctl'] == 'admin_order' && ($_GET['act'] == 'confirm' || $_GET['flt'] == 'buffer' || $_GET['flt'] == 'assigned')){
            //nothing
        }else{
           unset($this->column_confirm);
        }
        
        //剔除复审操作按扭[ExBOY]
        if($_GET['ctl'] == 'admin_order' && $_GET['act'] == 'retrial')
        {
            //nothing
        }else{
            unset($this->column_abnormal_status);
            unset($this->column_mark_text);
        }
    }
	
	
    
	function detail_basic($order_id){
        $render = app::get('ome')->render();
        $oOrders = &app::get('ome')->model('orders');
        $oOperation_log = &app::get('ome')->model('operation_log');

        if($_POST){
            if($_POST['is_flag']){
                $data['order_id'] = $_POST['order_id'];
                $data['tax_no'] = $_POST['tax_no'];
                //新增是否开发票和发票抬头的修改 2012-7-19
                $data['is_tax'] = $_POST['is_tax'];
                $data['tax_title'] = $_POST['tax_title'];
                $oOrders->save($data);
                
                //更新开票订单 ExBOY 2014.04.08
                if(app::get('invoice')->is_installed())
                {
                    $Invoice       = &app::get('invoice')->model('order');
                    $Invoice->update_order($data);
                }

                //TODO:api，发票号的回写
                $oOperation_log->write_log('order_modify@ome',$_POST['order_id'],'录入及变更发票号');
            }else{
                $order_id = $_POST['order']['order_id'];

                $memo = "";
                if(isset($_POST['order_action'])){
                    switch($_POST['order_action']){
                        case "cancel" :
                            $memo = "订单被取消";

                            //TODO: 订单取消作为单独的日志记录
                            $oOrders->unfreez($order_id);
                            $oOrders->cancel_delivery($order_id);
                            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            break;
                        case "order_limit_time" :
                            $plainData = $_POST['order'];
                            $plainData['order_limit_time'] = strtotime($plainData['order_limit_time']);
                            $oOrders->save($plainData);

                            $memo = "订单的有效时间被设置为".date("Y-m-d",$plainData['order_limit_time']);
                            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            break;
                        case "order_payed" :
                            $memo = "确认订单付款";
                            $orderinfo = $oOrders->order_detail($order_id);
                            if ($orderinfo['payed'] == $orderinfo['total_amount'])
                            {
                                $plainData['pay_status'] = 1;
                                $oOrders->save($plainData);
                                $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            }
                            break;
                        case "order_pause":
                            $memo = "订单暂停";
                            $oOrders->pauseOrder($order_id);
                            break;
                        case "order_renew":
                            $memo = "订单恢复";
                            $oOrders->renewOrder($order_id);
                            break;
                        default:
                            $memo = "订单内容修改";
                            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            break;
                    }
                }else{
                    $memo = "订单内容修改";
                    $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                }

            }

            //写操作日志
        }
        $order_detail = $oOrders->dump($order_id,"*",array("order_items"=>array("*")));
        $oRefund = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund->getList('*',array('order_id'=>$order_id),0,-1);
        $amount = 0;
        foreach ($refunddata as $row){
            if ($row['status'] != '3' && $row['status'] != '4'){
                $render->pagedata['isrefund'] = 'false';//如果退款申请没有处理完成
            }
        }
        if ($render->pagedata['isrefund'] == ''){
            if ($order_detail['pay_status'] == '5'){
                $render->pagedata['isrefund'] = 'false';//订单已全额退货
            }
        }
        $render->pagedata['is_c2cshop'] = in_array($order_detail['shop_type'],ome_shop_type::shop_list()) ?true:false;
        $render->pagedata['shop_name'] = ome_shop_type::shop_name($order_detail['shop_type']);
        $order_detail['mark_text'] = kernel::single('ome_func')->format_memo($order_detail['mark_text']);
        $order_detail['custom_mark'] = kernel::single('ome_func')->format_memo($order_detail['custom_mark']);
        $render->pagedata['total_amount'] = floatval($order_detail['total_amount']);
        $render->pagedata['payed'] = floatval($order_detail['payed']);
        $oMembers = &app::get('ome')->model('members');
        $member_id = $order_detail['member_id'];
        $render->pagedata['member'] = $oMembers->dump($member_id);
        $render->pagedata['url'] = kernel::base_url()."/app/".$render->app->app_id;


        //订单代销人会员信息
        $oSellagent = &app::get('ome')->model('order_selling_agent');
        $sellagent_detail = $oSellagent->dump(array('order_id'=>$order_id));
        if (!empty($sellagent_detail['member_info']['uname'])){
            $render->pagedata['sellagent'] = $sellagent_detail;
        }
        //发货人信息
        $order_consigner = false;
        if ($order_detail['consigner']){
            foreach ($order_detail['consigner'] as $shipper){
                if (!empty($shipper)){
                    $order_consigner = true;
                    break;
                }
            }
        }
        if ($order_consigner == false){
            //读取店铺发货人信息
            $oShop = &app::get('ome')->model('shop');
            $shop_detail = $oShop->dump(array('shop_id'=>$order_detail['shop_id']));
            $order_detail['consigner'] = array(
                'name' => $shop_detail['default_sender'],
                'mobile' => $shop_detail['mobile'],
                'tel' => $shop_detail['tel'],
                'zip' => $shop_detail['zip'],
                'email' => $shop_detail['email'],
                'area' => $shop_detail['area'],
                'addr' => $shop_detail['addr'],
            );
        }
        $sh_base_url = kernel::base_url(1);
        $render->pagedata['base_url'] = $sh_base_url;


     	$is_edit_view = 'true';//
        if ($order_add_service = kernel::service('service.order.'.$order_detail['shop_type'])){
            if (method_exists($order_add_service, 'is_edit_view')){
                $order_add_service->is_edit_view($order_detail, $is_edit_view);
            }
        }

        if($order_detail['shipping']['is_cod'] == 'true'){
            $orderExtendObj = &app::get('ome')->model('order_extend');
            $extendInfo = $orderExtendObj->dump($order_id);
            $order_detail['receivable'] = $extendInfo['receivable'];
        }

        $render->pagedata['is_edit_view'] = $is_edit_view;
        $render->pagedata['order'] = $order_detail;
        if(in_array($_GET['act'],array('confirm','abnormal'))){
            $render->pagedata['operate'] = true;
            $render->pagedata['act_'.$_GET['act']] = true;
        }
        if(($_GET['act'] == 'dispatch' && $_GET['flt'] == 'buffer') || ($_GET['ctl'] == 'admin_order' && ($_GET['act'] == 'active' || $_GET['act'] == 'index'))){
            $render->pagedata['operate'] = true;
            $render->pagedata['act_confirm'] = true;
        }
		if($render->pagedata['order']['is_w_card']=='true'){
			$render->pagedata['order']['is_w']=1;
		}
		if($render->pagedata['order']['is_card']=='true'){
			$render->pagedata['order']['is_c']=1;
		}
  
        return $render->fetch('admin/order/detail_basic.html');
    }
	
    function detail_goods($order_id){
        $render = app::get('ome')->render();
        $oOrder = &app::get('ome')->model('orders');

        $item_list = $oOrder->getItemList($order_id,true);
        $item_list = ome_order_func::add_getItemList_colum($item_list);
        ome_order_func::order_sdf_extend($item_list);
		// echo "<pre>";print_r($item_list);exit();
		if(!empty($item_list['pkg'])){
			$pkg=array();
			$arrPkg=$item_list['pkg'];
			$i=0;
			foreach($arrPkg as $k=>$v){
				$pkg_id=$v['pkg_id'];
				$pkg[$pkg_id]['pkg_name']=$v['pkg_name'];
				$pkg[$pkg_id]['pkg_price']=$v['pkg_price'];
				$pkg[$pkg_id]['pkg_num']=$v['pkg_num'];
				foreach($v['order_items'] as $item){
					$pkg[$pkg_id]['order_items'][$i]['name']=$item['name'];
					$pkg[$pkg_id]['order_items'][$i]['bn']=$item['bn'];
					$pkg[$pkg_id]['order_items'][$i]['addon']=$item['addon'];
					$pkg[$pkg_id]['order_items'][$i]['unit']=$item['unit'];
					$pkg[$pkg_id]['order_items'][$i]['price']=$item['price'];
					$pkg[$pkg_id]['order_items'][$i]['true_price']=$item['true_price'];
					$pkg[$pkg_id]['order_items'][$i]['ax_pmt_price']=$item['ax_pmt_price'];
					$pkg[$pkg_id]['order_items'][$i]['sale_price']=$item['sale_price'];
					$pkg[$pkg_id]['order_items'][$i]['quantity']=$item['quantity'];
					$pkg[$pkg_id]['order_items'][$i]['pmt_price']=$item['pmt_price'];
					$pkg[$pkg_id]['order_items'][$i]['sendnum']=$item['sendnum'];
					$pkg[$pkg_id]['order_items'][$i]['return_num']=$item['return_num'];
					$i++;
				}
			}
			unset($item_list['pkg']);
			$item_list['pkg']=$pkg;
		}
		
        $orders = $oOrder->getRow(array('order_id'=>$order_id),'shop_type,order_source');
        $is_consign = false;
        #淘宝代销订单增加代销价
        if($orders['shop_type'] == 'taobao' && $orders['order_source'] == 'tbdx' ){
            kernel::single('ome_service_c2c_taobao_order')->order_sdf_extend($item_list);
            $is_consign = true;
        }

        $configlist = array();
        if ($servicelist = kernel::servicelist('ome.service.order.products'))
        foreach ($servicelist as $object => $instance){
            if (method_exists($instance, 'view_list')){
                $list = $instance->view_list();
                $configlist = array_merge($configlist, is_array($list) ? $list : array());
            }
        }

        $render->pagedata['is_consign'] = ($is_consign > 0)?true:false;
        $render->pagedata['configlist'] = $configlist;
        $render->pagedata['item_list'] = $item_list;//echo "<pre>";print_r($item_list);exit();
        $render->pagedata['object_alias'] = $oOrder->getOrderObjectAlias($order_id);
        return $render->fetch('admin/order/detail_goods.html');
    }

    function detail_invoice($order_id){
        $render = app::get('ome')->render();
        $oOrders = &app::get('ome')->model('orders');
        $einvoice = &app::get('einvoice')->model('invoice');

        if($_POST){
            $eItems = $einvoice->getList('taxIdentity,invoice_type',array('order_id'=>$order_id),0,1,'id desc');
            $isEinvoice = $oOrders->getList('is_einvoice,order_bn',array('order_id'=>$order_id),0,1);
            //编辑发票信息 @author payne.wu 2017-07-05
            $data['tax_title'] = $_POST['tax_title'];
			$data['tax_company'] = $_POST['tax_title'];
            $data['invoice_name'] = $_POST['invoice_name'];
            $data['invoice_area'] = $_POST['invoice_area'];
            $data['invoice_contact'] = $_POST['invoice_contact'];
            $data['invoice_addr'] = $_POST['invoice_addr'];
            $data['invoice_zip'] = $_POST['invoice_zip'];
			$data['taxpayer_identity_number'] = $_POST['taxIdentity'];
			if($_POST['is_einvoice']){
				$data['is_einvoice'] = $_POST['is_einvoice'];
			}
            $_data['order_id'] = $order_id;
            $_data['order_bn'] = $isEinvoice[0]['order_bn'];
            $_data['taxIdentity'] = $_POST['taxIdentity'];
            $oOrders->update($data,array('order_id'=>$order_id));
            //根据是否是电子发票(及发票状态)进行插入或更新操作 @author payne.wu 2017-07-06
			//echo "<pre>";print_r($_POST);exit;
            if($_POST['is_einvoice']&&$_POST['is_einvoice'] != $isEinvoice[0]['is_einvoice']){
                if($_POST['is_einvoice'] == 'true'){
                    $einvoice->insert($_data);
					kernel::single('omemagento_service_order')->send_invoice_type($isEinvoice[0]['order_bn'],'电子发票');
                }else{
					kernel::single('omemagento_service_order')->send_invoice_type($isEinvoice[0]['order_bn'],'纸质发票');
                    if($eItems[0]['invoice_type'] == 'ready')
                    $einvoice->update(array('invoice_type'=>'cancel'),array('order_id'=>$order_id));
                }
            }
//echo "<pre>";print_r($_POST);exit;
			if($_POST['sub_type']=='apply'){
				 $order_info = $oOrders->getList("*",array('order_id'=>$order_id));
				 if($order_info[0]['is_einvoice']=='false'){
					$msg = '此订单的发票类型不是电子发票，无法重开！';
				 }else{
					 $info = $einvoice->getList('*',array('order_id'=>$order_id,'invoice_type'=>'active'));
					 if($info){
						$msg = '此订单的发票尚未冲红，无法重开！';
					}else{
						kernel::single('einvoice_request_invoice')->invoice_request($order_id,'getApplyInvoiceData');
					}
				 }
			}
			if($_POST['sub_type']=='cancel'){
				$order_info = $oOrders->getList("*",array('order_id'=>$order_id));
				 if($order_info[0]['is_einvoice']=='false'){
					$msg = '此订单的发票类型不是电子发票，无法冲红！';
				 }else{
					 $info = $einvoice->getList('*',array('order_id'=>$order_id,'invoice_type'=>'active'));
					 if($info){
						kernel::single('einvoice_request_invoice')->invoice_request($order_id,'getCancelInvoiceData');
					}else{
						$msg = '此订单的发票尚未开票，不能冲红！';
					}
				 }
			}
        }
		$this->pagedata['invoice_msg'] = $msg;
		$info = $einvoice->getList('*',array('order_id'=>$order_id,'invoice_type'=>'active'));
		if($info){
			$render->pagedata['invoice_status'] = 'active';
		}else{
			$render->pagedata['invoice_status'] = 'ready';
		}
        $order_detail = $oOrders->dump($order_id,"*",array("order_items"=>array("*")));
        $render->pagedata['order'] = $order_detail;
	//	echo "<pre>";print_r($order_detail);exit;
        $render->pagedata['order']['eItems'] = $eItems[0];
        return $render->fetch('admin/order/detail_invoice.html');
    }

    function detail_pmt($order_id){
        $render = app::get('ome')->render();
        $oOrder_pmt = &app::get('ome')->model('order_pmt');

        $pmts = $oOrder_pmt->getList('pmt_amount,pmt_describe',array('order_id'=>$order_id));

        $render->pagedata['pmts'] = $pmts;
        return $render->fetch('admin/order/detail_pmt.html');
    }

    function detail_bill($order_id){
        $render = app::get('ome')->render();
        $oPayments = &app::get('ome')->model('payments');
        $oRefunds = &app::get('ome')->model('refunds');

        $payments = $oPayments->getList('payment_id,payment_bn,t_begin,download_time,money,paymethod',array('order_id'=>$order_id));
        $refunds = $oRefunds->getList('refund_bn,t_ready,download_time,money,paymethod,payment',array('order_id'=>$order_id));
        
        $paymentCfgModel = app::get('ome')->model('payment_cfg');
        foreach ($refunds as $key=>$refund) {
            if ($refund['paymethod']) {
                $paymentCfg = $paymentCfgModel->getList('custom_name',array('id'=>$refund['payment']),0,1);
                $refunds[$key]['paymethod'] = $paymentCfg[0]['custom_name'] ? $paymentCfg[0]['custom_name'] : '';
            }
        }

		foreach($payments as $k=>$v){
			$payments[$k]['t_begin'] = date('Y-m-d H:i:s',$v['t_begin']);
			if($v['download_time']) $payments[$k]['download_time'] = date('Y-m-d H:i:s',$v['download_time']);
		}

        $render->pagedata['payments'] = $payments;
        $render->pagedata['refunds'] = $refunds;

        return $render->fetch('admin/order/detail_bill.html');
    }

    function detail_refund_apply($order_id){
        $render = app::get('ome')->render();
        $oRefund_apply = &app::get('ome')->model('refund_apply');

        $refund_apply = $oRefund_apply->getList('create_time,status,money,refund_apply_bn,refunded',array('order_id'=>$order_id));
        if($refund_apply){
            foreach($refund_apply as $k=>$v){
                $refund_apply[$k]['status_text'] = ome_refund_func::refund_apply_status_name($v['status']);
            }
        }

        $render->pagedata['refund_apply'] = $refund_apply;

        return $render->fetch('admin/order/detail_refund_apply.html');
    }

    function detail_delivery($order_id){
        $render = app::get('ome')->render();
        $oDelivery = &app::get('ome')->model('delivery');
        $oReship = &app::get('ome')->model('reship');
        $oWms_delivery = &app::get('wms')->model('delivery');
        $obj_order = &app::get('ome')->model('orders');
        $wms_delivery = $oWms_delivery->getDeliveryByOrder($order_id);
        $oBranch = &app::get('ome')->model('branch');
        $delivery = $oDelivery->getDeliveryByOrder('branch_id,create_time,delivery_id,delivery_bn,logi_id,logi_no,logi_name,ship_name,delivery,branch_id,stock_status,deliv_status,expre_status,status,weight',$order_id);
        $reship = $oReship->getList('t_begin,reship_id,reship_bn,logi_no,ship_name,delivery',array('order_id'=>$order_id));
        $wms_id = kernel::single('wms_branch')->getBranchByselfwms();
        $order_info = $obj_order->dump($order_id,'order_bn');
        #检测是否开启华强宝物流
        $is_hqepay_on =  &app::get('ome')->getConf('ome.delivery.hqepay');
        if($is_hqepay_on == 'false'){
            $is_hqepay_on = false;
        }else{
            $is_hqepay_on = true;
        }
        foreach($delivery as $k=>$v){
            //判断是否第三方
            $branch_list = $oBranch->getList('branch_id', array('wms_id'=>$wms_id,'branch_id'=>$v['branch_id']), 0, -1);
           if ($branch_list) {
               $delivery[$k]['selfwms'] = 1;
           }
			$delivery[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
			
		}
		$render->pagedata['order_bn'] = $order_info['order_bn'];
		$render->pagedata['is_hqepay_on'] = $is_hqepay_on;
        $render->pagedata['delivery'] = $delivery;
        $render->pagedata['wms_delivery'] = $wms_delivery;
        $render->pagedata['reship'] = $reship;

        return $render->fetch('admin/order/detail_delivery.html');
    }

    function detail_mark($order_id){
        $render = app::get('ome')->render();
        $oOrders = &app::get('ome')->model('orders');
		$oOrderItems = app::get('ome')->model('order_items');

        if($_POST){
            $order_id = $_POST['order']['order_id'];
            //取出原备注信息
            $oldmemo = $oOrders->dump(array('order_id'=>$order_id), 'mark_text');
            $oldmemo= unserialize($oldmemo['mark_text']);
            $op_name = kernel::single('desktop_user')->get_name();
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $memo[] = $v;
            }
            $newmemo =  htmlspecialchars($_POST['order']['mark_text']);
            $newmemo = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i:s',time()), 'op_content'=>$newmemo);
            $memo[] = $newmemo;
            $_POST['order']['mark_text'] = serialize($memo);
            $plainData = $_POST['order'];
            $oOrders->save($plainData);
            //写操作日志
            $memo = "订单备注修改";

            //订单留言 API
            foreach(kernel::servicelist('service.order') as $object=>$instance){
                if(method_exists($instance, 'update_memo')){
                    $instance->update_memo($order_id, $newmemo);
                }
            }

            $oOperation_log = &app::get('ome')->model('operation_log');
            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
        }

        $order_detail = $oOrders->dump($order_id);
        $render->pagedata['base_dir'] = kernel::base_url();
        $order_detail['mark_text'] = unserialize($order_detail['mark_text']);
        if ($order_detail['mark_text'])
        foreach ($order_detail['mark_text'] as $k=>$v){
            if (!strstr($v['op_time'], "-")){
                $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                $order_detail['mark_text'][$k]['op_time'] = $v['op_time'];
            }
        }
        $order_detail['custom_mark'] = unserialize($order_detail['custom_mark']);
        if ($order_detail['custom_mark'])
        foreach ($order_detail['custom_mark'] as $k=>$v){
            if (!strstr($v['op_time'], "-")){
                $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                $order_detail['custom_mark'][$k]['op_time'] = $v['op_time'];
            }
        }
        $order_detail['mark_type_arr'] = ome_order_func::order_mark_type();
		//zjr 刻字
		if($order_detail['is_lettering']=='true'){
			$arrItems=$oOrderItems->getList('*',array('order_id'=>$order_id));
			foreach($arrItems as $items){
				if(!empty($items['message1'])){
					$strLettering.=$items['message1'];
				}
			}
			$render->pagedata['strLettering']  = $strLettering;
		}
		
        $render->pagedata['order']  = $order_detail;
        return $render->fetch('admin/order/detail_mark.html');
    }

    /*买家留言*/
    function detail_custom_mark($order_id){
        $render = app::get('ome')->render();
        $oOrders = &app::get('ome')->model('orders');

        if($_POST){
            $order_id = $_POST['order']['order_id'];
            //取出原留言信息
            $oldmemo = $oOrders->dump(array('order_id'=>$order_id), 'custom_mark');
            $oldmemo= unserialize($oldmemo['custom_mark']);
            $op_name = kernel::single('desktop_user')->get_name();
            if ($oldmemo)
            foreach($oldmemo as $k=>$v){
                $memo[] = $v;
            }
            $newmemo =  htmlspecialchars($_POST['order']['custom_mark']);
            $newmemo = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i:s',time()), 'op_content'=>$newmemo);
            $memo[] = $newmemo;
            $_POST['order']['custom_mark'] = serialize($memo);
            $plainData = $_POST['order'];
            $oOrders->save($plainData);
            //写操作日志
            $memo = "买家留言修改";

            //买家留言 API
            foreach(kernel::servicelist('service.order') as $object=>$instance){
                if(method_exists($instance, 'add_custom_mark')){
                    $instance->add_custom_mark($order_id, $newmemo);
                }
            }

            $oOperation_log = &app::get('ome')->model('operation_log');
            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
        }

        $order_detail = $oOrders->dump($order_id);
        $render->pagedata['base_dir'] = kernel::base_url();
        $order_detail['custom_mark'] = unserialize($order_detail['custom_mark']);
        if ($order_detail['custom_mark'])
        foreach ($order_detail['custom_mark'] as $k=>$v){
            if (!strstr($v['op_time'], "-")){
                $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                $order_detail['custom_mark'][$k]['op_time'] = $v['op_time'];
            }
        }
        $render->pagedata['order']  = $order_detail;

        return $render->fetch('admin/order/detail_custom_mark.html');
    }

    function detail_abnormal($order_id){
        $render = app::get('ome')->render();
        $oAbnormal = &app::get('ome')->model('abnormal');
        $oOrder = &app::get('ome')->model('orders');
        $ordersdetail = $oOrder->dump(array('order_id'=>$order_id));
        //组织分派所需的参数
        $render->pagedata['op_id'] = $ordersdetail['op_id'];
        $render->pagedata['group_id'] = $ordersdetail['group_id'];
        $render->pagedata['dt_begin'] = strtotime(date('Y-m-d',time()));
        $render->pagedata['dispatch_time'] = strtotime(date('Y-m-d',time()));
        $render->pagedata['ordersdetail'] = $ordersdetail;
        //增加一个标识
        $render->pagedata['is_flag'] = 'true';
        if($ordersdetail['shop_type'] == 'vjia'){
            $outstorageObj = app::get('ome')->model('order_outstorage');
            $outstorage = $outstorageObj->dump(array('order_id'=>$order_id),'order_id');
            if(is_array($outstorage) && !empty($outstorage)) {
                $render->pagedata['outstorage'] = 'fail';
            }
        }

        if($_POST){
            $abnormal_data = $_POST['abnormal'];
            if($abnormal_data['is_done']=='vjia') {
                $outstorageObj->delete(array('order_id'=>$order_id));
                $abnormal_data['is_done'] = 'true';
            }
            $oOrder->set_abnormal($abnormal_data);
        }

        $abnormal = $oAbnormal->getList("*",array("order_id"=>$order_id),0,-1,'abnormal_id desc');
        if($abnormal){
            $oAbnormal_type = &app::get('ome')->model('abnormal_type');

            $abnormal_type = $oAbnormal_type->getList("*");

            $abnormal[0]['abnormal_memo'] = unserialize($abnormal[0]['abnormal_memo']);
            $render->pagedata['abnormal'] = $abnormal[0];
            $render->pagedata['abnormal_type'] = $abnormal_type;
            $render->pagedata['order_id'] = $order_id;
            $render->pagedata['set_abnormal'] = true;
        }else{
            $render->pagedata['set_abnormal'] = false;
        }

        return $render->fetch('admin/order/detail_abnormal.html');
    }

    function detail_history($order_id){

        $render = app::get('ome')->render();
        $orderObj = &app::get('ome')->model('orders');
        $logObj = &app::get('ome')->model('operation_log');
        $deliveryObj = &app::get('ome')->model('delivery');
        $ooObj = &app::get('ome')->model('operations_order');

        /* 本订单日志 */
        $history = $logObj->read_log(array('obj_id'=>$order_id,'obj_type'=>'orders@ome'),0,-1);
		foreach($history as $k=>$v){
            $data = $ooObj->getList('operation_id',array('log_id'=>$v['log_id']));
            if(!empty($data)){
                $history[$k]['flag'] ='true';
            }else{
                $history[$k]['flag'] ='false';
            }
			$history[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
		}

        /* 发货单日志 */
        $delivery_ids = $deliveryObj->getDeliverIdByOrderId($order_id);
        $deliverylog = $logObj->read_log(array('obj_id'=>$delivery_ids,'obj_type'=>'delivery@ome'), 0, -1);
        
        #[拆单]多个发货单 格式化分开显示  ExBOY
        $dly_log_list   = array();
        foreach($deliverylog as $k=>$v)
        {
            $deliverylog[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
            
            $obj_id     = $v['obj_id'];
            $dly_log_list[$obj_id]['obj_name']  = $v['obj_name'];
            $dly_log_list[$obj_id]['list'][]    = $deliverylog[$k];
        }
        $render->pagedata['dly_log_list'] = $dly_log_list;

        /* “失败”、“取消”、“打回”发货单日志 */
        $history_ids = $deliveryObj->getHistoryIdByOrderId($order_id);
        $deliveryHistorylog = array();
        foreach($history_ids as $v){
            $delivery = $deliveryObj->dump($v,'delivery_id,delivery_bn,status');
            $deliveryHistorylog[$delivery['delivery_bn']] = $logObj->read_log(array('obj_id'=>$v,'obj_type'=>'delivery@ome'), 0, -1);
            
            
            foreach($deliveryHistorylog[$delivery['delivery_bn']] as $k=>$v){
                $deliveryHistorylog[$delivery['delivery_bn']][$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
                $deliveryHistorylog[$delivery['delivery_bn']][$k]['status'] =$delivery['status'];
            }
        }
        
        /* 同批处理的订单日志 */
        $order_ids = $deliveryObj->getOrderIdByDeliveryId($delivery_ids);
        $orderLogs = array();
        foreach($order_ids as $v){
            if($v != $order_id){
                $order = $orderObj->dump($v,'order_id,order_bn');
                $orderLogs[$order['order_bn']] = $logObj->read_log(array('obj_id'=>$v,'obj_type'=>'orders@ome'), 0, -1);
                foreach($orderLogs[$order['order_bn']] as $k=>$v){
                    if($v)
                        $orderLogs[$order['order_bn']][$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
                }
            }
        }

        $render->pagedata['history'] = $history;
        $render->pagedata['deliverylog'] = $deliverylog;
        $render->pagedata['deliveryHistorylog'] = $deliveryHistorylog;
        $render->pagedata['orderLogs'] = $orderLogs;
        $render->pagedata['order_id'] = $order_id;


        return $render->fetch('admin/order/detail_history.html');
    }

    function detail_shipment($order_id) {
        $render = app::get('ome')->render();
        $orderObj = &app::get('ome')->model('orders');
        $shipmentObj = & app::get('ome')->model('shipment_log');
        $userObj = app::get('desktop')->model('users');

        $order = $orderObj->dump($order_id);
        if ($order) {

            $orderBn = $order['order_bn'];
            $shipmentLogs = $shipmentObj->getList('*', array('orderBn' => $orderBn));
            foreach ($shipmentLogs as $k=>$log) {
                if ($shipmentLogs[$k]['receiveTime']) {
                    $shipmentLogs[$k]['receiveTime'] = date('Y-m-d H:i:s', $shipmentLogs[$k]['receiveTime']);
                } else {
                    $shipmentLogs[$k]['receiveTime'] = '&nbsp;';
                }
                if ($shipmentLogs[$k]['updateTime']) {
                    $shipmentLogs[$k]['updateTime'] = date('Y-m-d H:i:s', $shipmentLogs[$k]['updateTime']);
                } else {
                    $shipmentLogs[$k]['updateTime'] = '&nbsp;';
                }
                switch ($shipmentLogs[$k]['status']) {
                    case 'succ':
                        $shipmentLogs[$k]['status'] = '<font color="green">成功</font>';
                        break;
                    case 'fail':
                        $shipmentLogs[$k]['status'] = '<font color="red">失败</font>';
                        break;
                    default:
                        $shipmentLogs[$k]['status'] = '<font color="#000">运行中……</font>';
                        break;
                }

                if($log['ownerId'] == 16777215){
                    $shipmentLogs[$k]['ownerId'] = 'system';
                }else{
                    $user = $userObj->dump($log['ownerId'],'name');
                    $shipmentLogs[$k]['ownerId'] = $user['name'];
                }
            }
            $render->pagedata['order'] = $order;
            $render->pagedata['shipmentLogs'] = $shipmentLogs;
        }

        return $render->fetch('admin/order/detail_shipment.html');
    }

    /*function detail_aftersale($order_id){
        $render = app::get('ome')->render();
        $oReturn = &app::get('ome')->model('return_product');
        $return = $oReturn->Get_aftersale_list($order_id);

        $render->pagedata['return'] = $return;
        return $render->fetch('admin/order/detail_aftersale.html');
    }*/
    var $addon_cols = "print_status,member_id,refund_status,confirm,dt_begin,status,process_status,tax_no,ship_status,op_id,group_id,mark_text,auto_status,custom_mark,mark_type,tax_company,createtime,paytime,sync,pay_status,is_cod,source";
    var $column_confirm='操作';
    var $column_confirm_width = "120";

    function column_confirm($row){

        if ($_GET['ctl']=='admin_order') {

            return $this->_get_confirm_btn($row);
        } else {

            return $this->_get_sync_btn($row);
        }
    }

    private function _get_sync_btn($row) {

        if ($_GET['view']==3 || $_GET['view']==5 ||  $_GET['view']==4) {

            unset($this->column_confirm);
            return;
        }
        $find_id = $_GET['_finder']['finder_id'];

        $result = '';
        $order_id = $row['order_id'];
        if ($row[$this->col_prefix . 'refund_status'] < 1) {

            switch ($row['_0_sync']) {
                case 'none':
                    $result = "<a href='index.php?app=ome&ctl=admin_consign&act=do_sync&p[0]={$order_id}&finder_id=$find_id' target='download'>发货</a>";
                    break;
                case 'fail':
                case 'run':
                    $result = "<a href='index.php?app=ome&ctl=admin_consign&act=do_sync&p[0]={$order_id}&finder_id=$find_id' target='download'>重试</a>";
                    break;
            }
        } else {

            if ($row['_0_sync'] <> 'succ') {

                $result = '无法操作';
            }
        }

        return $result;
    }

    private function _get_confirm_btn($row) {

        //条件过滤
        $filter_data = array();
        if ($_POST)
        foreach ($_POST as $key=>$v){
            if (preg_match("/^_+/i",$key)) continue;
            $filter_data[$key] = $v;
        }
        $filter = urlencode(serialize($filter_data));
        $find_id = $_GET['_finder']['finder_id'];
        $order_id = $row['order_id'];

        $button = <<<EOF
            <a href="index.php?app=ome&ctl=admin_order&act=do_confirm&p[0]=$order_id&filter=$filter&find_id=$find_id" target="_blank">订单确认</a>
EOF;

        $button2 = <<<EOF
            <a href="index.php?app=ome&ctl=admin_order&act=do_confirm&p[0]=$order_id&filter=$filter&find_id=$find_id" target="_blank">订单拆分</a>
EOF;

        $remain_order_cancel_but = <<<EOF
            <a href="index.php?app=ome&ctl=admin_order&act=remain_order_cancel_confirm&order_id=$order_id&find_id=$find_id&from=order_button" target="_blank">余单撤销</a>
EOF;

        $button_batch = <<<EOF
            <a href="index.php?app=ome&ctl=admin_order&act=do_confirm&p[0]=$order_id&filter=$filter&find_id=$find_id" target="_blank">审核</a>
EOF;

        $button_dispatch = <<<EOF
            <a href="index.php?app=ome&ctl=admin_order&act=dispatchSingle&finder_id=$find_id&p[0]=$order_id&single=is" target="dialog::{width:400,height:200,title:'订单分派'}">分派</a>
EOF;

        #订单编辑同步状态
        $shop_id = $row['shop_id'];
        $order_bn = $row['order_bn'];
        $oOrder_sync = &app::get('ome')->model('order_sync_status');
        $sync_status = $oOrder_sync->getList('order_id,type,sync_status',array('order_id'=>$order_id),0,1);
        if ($sync_status[0]['sync_status'] == '1' && $row[$this->col_prefix.'source'] == 'matrix'){
            $button2 = $button = <<<EOF
            <a onclick="javascript:new Request({
                url:'index.php?app=ome&ctl=admin_shop&act=sync_order',
                data:'order_id={$order_bn}&shop_id={$shop_id}',
                method:'post',
                onSuccess:function(response){
                    var resp = JSON.decode(response);
                    if (resp.rsp == 'fail'){
                        alert(resp.msg);
                    }else{
                        new Request({
                            url:'index.php?app=ome&ctl=admin_order&act=set_sync_status&p[0]={$order_id}&p[1]=success',
                            method:'get',
                            onSuccess:function(rs){
                                alert('同步成功');
                                finder = finderGroup['{$find_id}'];
                                finder.refresh.delay(100, finder);
                            }
                        }).send();
                    }
                }
            }).send();" href="javascript:;" >重新同步</a>
EOF;
            $re_sync = true;
        }

        // 订单确认 - 本组的订单
        if ($_GET['flt'] == 'ourgroup')
        {
            if (empty($row[$this->col_prefix.'op_id']) && !in_array($row[$this->col_prefix.'process_status'],array('cancel')))
            {
                $button_3 = sprintf('<a href="javascript:if (confirm(\'是否确认领取？如果领取相关订单将同时被领取！\')){W.page(\'index.php?app=ome&ctl=admin_order&act=claim&order_id[0]=%s&filter=%s&find_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">领取</a>', $order_id, $filter, $find_id);
                return $button_3;
            }
        }
        // 订单确认 - 我的待确认订单
        elseif ($_GET['flt'] == 'unmyown') {
            if (($row[$this->col_prefix.'pay_status'] == 1 || $row[$this->col_prefix.'pay_status'] == 4 || ($row[$this->col_prefix.'is_cod'] == 'true' && ($row[$this->col_prefix.'pay_status'] == 0 || $row[$this->col_prefix.'pay_status'] == 3))) && in_array($row[$this->col_prefix.'process_status'], array('unconfirmed', 'confirmed','splitting')) && $row[$this->col_prefix.'ship_status'] == 0)
            {
                if ($row[$this->col_prefix.'confirm'] == 'N' && !in_array($row[$this->col_prefix.'process_status'],array('splited','cancel','remain_cancel')) && $row[$this->col_prefix.'status'] == 'active'){
                    //return $button.$button_batch;
                    return $button_batch;
                }

                if (!in_array($row[$this->col_prefix.'process_status'],array('splited','unconfirmed','cancel','remain_cancel')) && $row[$this->col_prefix.'status'] == 'active'){
                    //return $button2.$button_batch;
                    return $button_batch;
                }
            }
            elseif (($row[$this->col_prefix.'pay_status'] == 1 || $row[$this->col_prefix.'is_cod'] == 'true') && $row[$this->col_prefix.'process_status'] == 'splitting' && ($row[$this->col_prefix.'ship_status'] == 2 || $row[$this->col_prefix.'ship_status'] == 3))
            {
                return sprintf("%s | %s", $button2, $remain_order_cancel_but);//已支付-部分拆分-部分发货-部分退货
            }
            elseif ($row[$this->col_prefix.'pay_status'] == 1 && $row[$this->col_prefix.'process_status'] == 'splitting' && $row[$this->col_prefix.'ship_status'] == 4)
            {
                return sprintf("%s", $remain_order_cancel_but);//已支付-部分拆分-已退货-可余单撤销
            }
            elseif ($row[$this->col_prefix.'pay_status'] == 4 && $row[$this->col_prefix.'process_status'] == 'splitting' && ($row[$this->col_prefix.'ship_status'] == 2 || $row[$this->col_prefix.'ship_status'] == 3))
            {
                return sprintf("%s | %s", $button2, $remain_order_cancel_but);//部分退款-部分退款-部分拆分-部分发货的订单可继续操作
            }
            elseif($row[$this->col_prefix.'pay_status'] == 5 && ($row[$this->col_prefix.'process_status'] == 'splitting' || $row[$this->col_prefix.'ship_status'] == 2))
            {
                return sprintf("%s", $remain_order_cancel_but);//全额退款-部分拆分-部分发货-可余单撤销
            }
        } elseif($_GET['flt'] == 'buffer') {
            //缓冲区
            return $button_dispatch;
        } elseif($_GET['flt'] == 'assigned') {
            //缓冲区
            $deliveryObj = app::get('ome')->model('delivery');
            $deliveryIds = $deliveryObj->getDeliverIdByOrderId($row['order_id']);
            if(count($deliveryIds)==0){
                return $button_dispatch;
            }
        } else {
        	 // 余单撤销(只有已支付，或是货到付款并且部分发货的才会出现余单撤销按钮)
        	if (($row['pay_status'] == 1 || $row['is_cod'] == 'true') && $row[$this->col_prefix.'ship_status'] == 2){
        	return 	$button = $remain_order_cancel_but;
        	}
        }

        if($re_sync == true){
            return $button;
        }
    }

    function row_style($row){
        $time = time();
        $limit = (app::get("ome")->getConf('ome.order.unconfirmtime'))*60;
        $style='';
        if($row[$this->col_prefix.'confirm'] == 'N'
            && ($time - $row[$this->col_prefix.'dt_begin'] > $limit)
            && ($row[$this->col_prefix.'op_id'] || $row[$this->col_prefix.'group_id'])
            && $row[$this->col_prefix.'process_status'] == 'unconfirmed'){
            $style .= ' highlight-row ';
        }
        if($row[$this->col_prefix.'is_cod'] == 'true'){
            $style .= " list-even ";
        }
        elseif($row['process_status'] == 'splitting' && $row['pay_status'] == '4')
        {
            $style  = 'list-warning';//部分退款--颜色显示 ExBOY
        }
        
        return $style;
    }

    var $column_tax_no='是否录入发票号';
    var $column_tax_no_width = "100";
    function column_tax_no($row){
    	if($row[$this->col_prefix.'tax_no']){
    		return '是';
    	}else{
    		return '否';
    	}
    }

    var $column_custom_add='买家备注';
    var $column_custom_add_width = "100";
    function column_custom_add($row){
        $order_id = $row['order_id'];
        //$oObj = app::get('ome')->model('orders');
        //$custom_mark = $oObj->dump($order_id,'custom_mark');
        $custom_mark = $row[$this->col_prefix.'custom_mark'];
        $custom_mark = kernel::single('ome_func')->format_memo($custom_mark);
        foreach ((array)$custom_mark as $k=>$v){
        	$html .= $v['op_content'].' '.$v['op_time'].' by '.$v['op_name']."<br />";
        }
        $html = strip_tags(htmlspecialchars($html));
        return "<div onmouseover='bindFinderColTip(event)' rel='{$html}'>".strip_tags(htmlspecialchars($custom_mark[$k]['op_content']))."<div>";
    }

    var $column_customer_add='客服备注';
    var $column_customer_add_width = "100";
    function column_customer_add($row){
        $order_id = $row['order_id'];
        //$oObj = app::get('ome')->model('orders');
        //$mark_text = $oObj->dump($order_id,'mark_text');
        $mark_text = $row[$this->col_prefix.'mark_text'];
        $mark_text = kernel::single('ome_func')->format_memo($mark_text);
        foreach ((array)$mark_text as $k=>$v){
            $html .= $v['op_content'].' '.$v['op_time'].' by '.$v['op_name']."<br />";
        }
        $html = strip_tags(htmlspecialchars($html));
        return "<div onmouseover='bindFinderColTip(event)' rel='{$html}'>".strip_tags(htmlspecialchars($mark_text[$k]['op_content']))."<div>";
    }

    //新增
    var $column_fail_status = '注意事项';
    var $column_fail_status_width = "130";

    function column_fail_status($row) {

        //$order_id = $row['order_id'];
        //$oObj = app::get('ome')->model('orders');
        //$row = $oObj->dump($order_id,'*');
        foreach ($row as $key => $val) {

            $key = str_replace('_0_', '', $key);
            $row[$key] = $val;
        }

        $auto_status = $row['auto_status'];

        $msgs = kernel::single('omeauto_auto_combine')->fetchAlertMsg($auto_status, $row);

        if (empty($msgs)) {

            return '';
        } else {

            $ret = '';
            foreach ($msgs as $msg) {

                $ret .= $this->getViewPanel($msg['color'], $msg['msg'], $msg['flag']);
            }

            return $ret;
        }
    }

    var $column_deff_time = '下单距今';
    var $column_deff_time_width = "100";
    var $column_deff_time_order_field = "createtime";

    function column_deff_time($row) {
        if ($row['_0_is_cod'] == 'true') {
            $difftime = kernel::single('ome_func')->toTimeDiff(time(), $row['_0_createtime']);
        } else {
            if ($row['_0_paytime'] > 0) {
                $difftime = kernel::single('ome_func')->toTimeDiff(time(), $row['_0_paytime']);
            } else {
                //return '<span style="color:red;font-weight:700;">未支付</span>';
                return '';
            }
        }
        return $difftime['d'] . '天' . $difftime['h'] . '小时' . $difftime['m'] . '分';
    }

    public function getViewPanel($color, $msg, $title) {

        return sprintf("<div onmouseover='bindFinderColTip(event)' rel='%s' style='width:18px;padding:2px;height:16px;background-color:%s;float:left;color:#ffffff;'>&nbsp;%s&nbsp;</div>", $msg, $color, $title);
    }


    //显示状态
    var $column_print_status = "打印状态";
    var $column_print_status_width = "80";

    function column_print_status($row) {

        $stockColor = (($row['_0_print_status'] & 0x02) == 0x02) ? 'green' : '#eeeeee';
        $delivColor = (($row['_0_print_status'] & 0X04) == 0X04) ? 'red' : '#eeeeee';
        $expreColor = (($row['_0_print_status'] & 0x01) == 0x01) ? 'gold' : '#eeeeee';
        $ret = $this->_getViewPanel('备货单', $stockColor);
        $ret .= $this->_getViewPanel('发货单', $delivColor);
        $ret .= $this->_getViewPanel('快递单', $expreColor);
        return $ret;
    }
	
	var $column_users_type = "客户类型";
    var $column_users_type_width = "65";

    function column_users_type($row) {
		$objMember = app::get('ome')->model('members');
		$member_id=$row['_0_member_id'];
		$arrMember=$objMember->db->select("SELECT m_memeber_num FROM sdb_ome_members WHERE member_id='$member_id'");
		$m_memeber_num=$arrMember['0']['m_memeber_num'];//getList('member_id,m_member_num',array('member_id'=>$member_id));
		if(empty($m_memeber_num)||$m_memeber_num==""){
			return '访客';
		}else{
			return '会员';
		}
		//echo "<pre>";print_r($arrMember);exit();	
	}
	
    public function _getViewPanel($caption, $color) {
        if ($color == '#eeeeee')
            $caption .= '未打印';
        else
            $caption .= '已打印';
        return sprintf("<div style='width:18px;padding:2px;height:16px;background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", $color, $caption, $caption, substr($caption, 0, 3));
    }
    #订单异常类型
    /*
    var $column_abnormal_type_name ='异常类型';
    var $column_abnormal_type_name_width = "80";
    
    function column_abnormal_type_name($row){
        $obj_abnormal = app::get('ome')->model('abnormal');
        $arr = $obj_abnormal->getList('abnormal_type_name',array('order_id'=>$row['order_id']));
        return $arr[0]['abnormal_type_name'];
    }
    */
    var $column_tax_company='发票抬头';
    var $column_tax_company_width = "150";
    function column_tax_company($row){
        //$oObj = app::get('ome')->model('orders');
        //$tax_info = $oObj->dump($row['order_id'],'tax_company');
        if(empty($row[$this->col_prefix.'tax_company'])){
            return '-';
        }
        return $row[$this->col_prefix.'tax_company'];
    }
    
    /*------------------------------------------------------ */
    //-- 我的异常订单'操作'[ExBOY]
    /*------------------------------------------------------ */
    var $column_abnormal_status    = '复审操作';
    var $column_abnormal_status_width  = '110';
    var $column_abnormal_status_order  = '10';
    function column_abnormal_status($row)
    {
        $find_id       = $_GET['_finder']['finder_id'];
        $order_id      = $row['order_id'];
        
        $sql    = "SELECT id, retrial_type, status FROM ".DB_PREFIX."ome_order_retrial WHERE order_id='".$order_id."' AND status in('0', '2') ORDER BY dateline DESC";
        $result = kernel::database()->select($sql);
        
        $str    = '<a href="index.php?app=ome&ctl=admin_order&act=view_edit&p[0]='.$order_id.'&finder_id='.$find_id.'&oldsource=active" target="_blank">编辑</a>';
        if($result[0]['status'] == '2' && $result[0]['retrial_type'] == 'normal')
        {
            return $str.' | <a href="index.php?app=ome&ctl=admin_order&act=retrial_rollback&p[0]='.$order_id.'&finder_id='.$find_id.'&oldsource=retrial" target="_blank" style="color:red;">恢复原订单</a>';
        }
        elseif($result[0]['status'] == '2')
        {
            return $str.'<span style="color:#999">(价格复审)</span>';
        }
        else
        {
            return '<span style="color:#999">未审核</span>';
        }
    }
    /*------------------------------------------------------ */
    //-- 我的异常订单'备注'[ExBOY]
    /*------------------------------------------------------ */
    var $column_mark_text    = '复审备注';
    var $column_mark_text_width  = '130';
    var $column_mark_text_order  = '15';
    function column_mark_text($row)
    {
        $order_id      = $row['order_id'];
        
        $sql    = "SELECT id, remarks, lastdate FROM ".DB_PREFIX."ome_order_retrial WHERE order_id='".$order_id."' AND status in('0', '2') ORDER BY dateline DESC";
        $result = kernel::database()->select($sql);
        
        $html   = strip_tags(htmlspecialchars($result[0]['remarks']));
        return "<div onmouseover='bindFinderColTip(event)' rel='".$html.' by '.date('Y-m-d H:i:s', $result[0]['lastdate'])."'>".$html."<div>";
    }
}
?>