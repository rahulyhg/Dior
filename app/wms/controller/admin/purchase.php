<?php
class wms_ctl_admin_purchase extends desktop_controller{
    var $name = "采购入库";
    var $workground = "wms_center";

    function eoList($p=null){
        switch ($p) {
            case 'i':
                $sub_title = '采购入库';
                //$this->workground = 'storage_center';
            break;
            default:
                $sub_title = '待入库';
                break;
        }

        $filter['eo_status'] = array('1', '2');
        $params = array(
                        'title'=>$sub_title,
                        'base_filter' => $filter,
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'orderBy' => 'purchase_time desc',
        				'finder_cols'=>'column_edit,supplier_id,emergency,name,arrive_time,operator,deposit,purchase_time,check_status,check_time,po_status,po_type,statement,eo_status,branch_id,check_operator',
                    );
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);

    	   if ($branch_ids){
            $params['base_filter']['branch_id'] = $branch_ids;
        }else{
            $params['base_filter']['branch_id'] = 'false';
        }

        $this->finder('purchase_mdl_po', $params);
    }

    /**
     * 打印采购单
     *
     * @param int $po_id
     */
    function printItem($po_id,$type='po'){
        $poObj = &app::get('purchase')->model('po');
        $suObj = &app::get('purchase')->model('supplier');
        $brObj = &app::get('ome')->model('branch');
        $prObj = &app::get('ome')->model('products');
        $poo = $poObj->dump($po_id, '*', array('po_items'=>array('*')));
        $su = $suObj->dump($poo['supplier_id'],'name');
        $bran = $brObj->dump($poo['branch_id'],'name');
        
        $obj_wms = &app::get('wms')->model('products');
        foreach($poo['po_items'] as $key=>$item){
            $prodcut_data = $obj_wms->getProuductInfoById($item['product_id']);
            $poo['po_items'][$key]['unit'] = $prodcut_data['unit'];
        }
        
        $this->pagedata['type'] = $type;
        $poo['supplier'] = $su['name'];
        $poo['branch'] = $bran['name'];
        $poo['memo'] = unserialize($poo['memo']);
        $this->pagedata['po'] = $poo;
        $this->pagedata['time'] = time();
        $this->pagedata['base_dir'] = kernel::base_url();

        # 改用新打印模板机制 chenping
        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],'purchase',$this);
    }

    function cancel($po_id, $type='confirm'){

        //获取采购单供应商经办人/负责人
        $oPo = app::get('purchase')->model('po');
        $po = $oPo->dump($po_id, 'supplier_id');
        $oSupplier = &app::get('purchase')->model('supplier');
        $supplier = $oSupplier->dump($po['supplier_id'], 'operator');
        //if (!$supplier['operator']) $supplier['operator'] = '未知';

        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();

        //print_r($po_id);
        $this->pagedata['type'] = $type;
        $this->pagedata['id'] = $po_id;
        $this->display("admin/purchase/purchase_cancel.html");
    }

    /**
     * 入库取消
     *
     *
     */
    function doRefund(){
        $po_id = $_POST['po_id'];
        $memo = $_POST['memo'];
        if (!$_POST['memo_flag']) $memo = '';
        $operator = $_POST['operator'];
        $this->begin('index.php?app=wms&ctl=admin_purchase&act=eoList&p[0]=i');
        if (empty($po_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        if ($operator == ''){
            $this->end(false,'操作出错，请重新操作');
        }
        $poObj = &app::get('purchase')->model('po');
        $po = $poObj->dump($po_id, '*', array('po_items'=>array('*')));
        if ($po['check_status'] != 2){
            $this->end(false,'操作出错，请重新操作');
        }
        if ($po['eo_status']<3){


            //TODO 一期为取消所有未入库的商品，以后会通过POST数据进行入库取消
            //生成退货单与退货明细
            $po_itemObj = &app::get('purchase')->model('po_items');
            $returnObj = &app::get('purchase')->model('returned_purchase');
            $paymentObj = &app::get('purchase')->model('purchase_payments');
            $refundObj = &app::get('purchase')->model('purchase_refunds');
            $rp_itemObj = &app::get('purchase')->model('returned_purchase_items');

            $return_flag = false;//无任何操作时，不生成退款单标志
            $pay = $paymentObj->dump(array('po_id'=>$po_id), '*');
            if ($po['eo_status'] == '1' && $pay['statement_status'] != '2'){//没有入库并且没有结算付款单
                $return_flag = true;
                if ($pay['payment_id']){
                    $paym['payment_id'] = $pay['payment_id'];
                    $paym['statement_status'] = '3';
                    $paymentObj->save($paym);
                }
            }


            //如果采购单已入库或者付款单已结算，生成退货单与退款单
            $return['supplier_id'] = $po['supplier_id'];
            $return['operator'] = $operator;//kernel::single('desktop_user')->get_name();
            $return['po_type'] = $po['po_type'];
            $return['purchase_time'] = $po['purchase_time'];
            $return['returned_time'] = time();
            $return['branch_id'] = $po['branch_id'];
            $return['arrive_time'] = $po['arrive_time'];
            $return['amount'] = 0;
            $return['rp_type'] = 'po';
            $return['object_id'] = $po_id;

            $rp_id = $returnObj->createReturnPurchase($return);//生成退货单

            $po_items = $po['po_items'];//$poObj->getPoItemsByPoId($po_id);
            $money = 0;
            if ($po_items)
            foreach ($po_items as $item){
                $num = $item['num']-$item['in_num']-$item['out_num'];
                $num = $num<0?0:$num;
                if (($item['status'] == '1' || $item['status'] == '2') && $num != 0){//判断此商品是否可以取消入库
                    $row['rp_id'] = $rp_id;
                    $row['product_id'] = $item['product_id'];
                    $row['num'] = $num;
                    $row['price'] = $item['price'];
                    $money += $item['price']*$num;
                    $row['bn'] = $item['bn'];
                    $row['name'] = $item['name'];
                    $row['spec_info'] = $item['spec_info'];

                    $rp_itemObj->save($row);
                    $row = null;
                    $poObj->updateBranchProductArriveStore($po['branch_id'], $item['product_id'],$num,  '-');

                    $r['item_id'] = $item['item_id'];
                    $r['out_num'] = $item['out_num']+$num;
                    $r['status'] = ($r['out_num']+$item['in_num'])>=$item['num']?'3':$item['status'];

                    $po_itemObj->save($r);
                    $r = null;
                }
            }
            $data['rp_id'] = $rp_id;
            $data['amount'] = $money;
            $data['product_cost'] = $money;

            $returnObj->save($data);//更新退货单
            //日志备注
            $log_msg .= '<br/>生成了一张编号为：'.$return['rp_bn'].'的退货单';
            if ($return_flag==false){
                //生成退款单
                $refund['add_time'] = time();
                $refund['po_type'] = $po['po_type'];
                $refund['delivery_cost'] = 0;
                $refund['type'] = 'po';
                $refund['rp_id'] = $rp_id;
                $refund['supplier_id'] = $po['supplier_id'];
                if ($po['po_type'] == 'cash'){
                    $refund['refund'] = $money;
                    $refund['product_cost'] = $money;

                }elseif ($po['po_type'] == 'credit' && $po['deposit_balance'] != 0){
                    $refund['refund'] = $po['deposit_balance'];
                    $refund['product_cost'] = 0;
                }
                $refund_id = $refundObj->createRefund($refund);

                $poo['amount'] = $po['amount'] - $money;
                $poo['product_cost'] = $po['product_cost'] - $money;
                $poo['deposit_balance'] = 0;
            }else {
                $poo['amount'] = 0;
                $poo['product_cost'] = 0;
            }
            $poo['po_id'] = $po_id;
            if ($_POST['memo']) {
                $op_name = kernel::single('desktop_user')->get_name();
                $oldmemo= unserialize($po['memo']);
                $memo = array();
                if ($oldmemo) {
                    foreach($oldmemo as $k=>$v){
                        $memo[] = $v;
                    }
                }
                $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>htmlspecialchars($_POST['memo']));
                $poo['memo'] = serialize($memo);
            }
            if ($po['po_status'] == '1'){
                $poo['po_status'] = '2';//入库取消
            }

            if ($po['eo_status'] == '2'){
                $poo['eo_status'] = '3';//已入库
            }elseif ($po['eo_status'] == '1') {
                $poo['eo_status'] = '4';//未入库
            }

            $poObj->save($poo);

            //--采购单入库取消日志记录
            if ($refund_id){
               $refund_bn = $refundObj->dump($refund_id,'refund_bn');
               $log_msg = '<br/>生成了一张编号为：'.$refund_bn['refund_bn'].'的退款单';
            }
            $log_msg2 = '对采购单编号为:'.$po['po_bn'].'进行了入库取消<br/>';
            $opObj = &app::get('ome')->model('operation_log');
            $opObj->write_log('purchase_cancel@purchase', $po_id, $log_msg2.$log_msg);

             //发起至WMS
            //事件触发，通知wms取消采购通知单 add by danny event notify

            $data = array(
                'branch_id'=>$po['branch_id'],
                'io_bn'=>$po['po_bn'],
                'io_type'=>'PURCHASE',
            );
            $wms_result = kernel::single('wms_event_trigger_purchase')->cancel($data, true);


            $this->end(true, '入库取消已完成');
        }else {
            $this->end(false, '此采购单已完成入库，请走采购退货流程');
        }
    }



}