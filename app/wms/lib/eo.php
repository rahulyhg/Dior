<?php
class wms_eo{
    /*
     * 将采购单入库
     * 采购单入库会分配货位，生成供应商商品采购价历史记录
     * 更新库存
     */
   
    function save_eo($data){

        $oPo = &app::get('purchase')->model("po");
        $supplierObj = &app::get('purchase')->model("supplier");
        $oPo_items = &app::get('purchase')->model("po_items");
        $oProducts = &app::get('ome')->model("products");
        $oEo = &app::get('purchase')->model("eo");
        $oEo_items = &app::get('purchase')->model("eo_items");
        $oCredit_sheet = &app::get('purchase')->model("credit_sheet");
        $oProduct_pos = &app::get('ome')->model("branch_product_pos");
        $oBranch_pos = &app::get('ome')->model("branch_pos");
        $oProduct_batch = &app::get('purchase')->model("branch_product_batch");
        $po_id = $data['po_id'];
        $branch_id = $_POST['branch_id'];
        $Po = $oPo->dump($po_id,'*');
        $supplier = $supplierObj->dump($Po['supplier_id'],'*');

        $amount=0;
        //start入库
        $history_data= array();
        foreach($data['ids'] as $i){
            $v = intval($data['entry_num'][$i]);
            $k = $i;
            $Po_items = $oPo_items->dump($k,'price,product_id,num,status,name,spec_info,bn');
            $Products = $oProducts->dump($Po_items['product_id'],'unit,goods_id');
            $amount+=$v*$Po_items['price'];
            $item_memo = $data['item_memo'][$k];
            $eo_items[$Po_items['product_id']]=array(
                'product_id' => $Po_items['product_id'],
                'name' => $Po_items['name'],
                'spec_info' => $Po_items['spec_info'],
                'bn' => $Po_items['bn'],
                'unit' => $Products['unit'],
                'price' => $Po_items['price'],
                'purchase_num' => $Po_items['num'],
                'nums' => $v,
                'is_new' => $data['is_new'][$k],
                'memo' => $item_memo,
              );

           //为供应商与商品建立关联
           if($Products['goods_id']!=''){
                $supplier_goods = array(
                    'supplier_id' => $Po['supplier_id'],
                    'goods_id' => $Products['goods_id']
                );
                $su_goodsObj = &app::get('purchase')->model('supplier_goods');
                $su_goodsObj->save($supplier_goods);//end

           }
            $history_data[]=array('product_id'=>$Po_items['product_id'],'purchase_price'=>$Po_items['price'],'store'=>$v,'branch_id'=>$Po['branch_id']);
            //更新采购单数量
            $po_items_data[] = array(
                'item_id'=>$k,
                'in_num'=>$v,
                'status'=>$Po_items['status'],
                'item_memo'=>addslashes($item_memo),
                'product_id' => $Po_items['product_id']
                );
        }

        //追加备注信息
        $memo = array();
        $op_name = kernel::single('desktop_user')->get_name();
        $newmemo =  htmlspecialchars($data['memo']);
        $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
        $memo = serialize($memo);

        $iostock_instance =  kernel::single('console_iostockorder');
        $eo_data = array (
                'iostockorder_name' => date('Ymd').'入库单',
                'supplier' => $supplier['name'],
                'supplier_id' => $Po['supplier_id'],
                'branch' => $Po['branch_id'],
                'type_id' => ome_iostock::PURCH_STORAGE,
                'iso_price' => $Po['delivery_cost'],
                'memo' => $newmemo,
                'operator' => $data['operator'],
                'products' => $eo_items,
                'original_bn' => $Po['po_bn'],
                'original_id' => $po_id,
                'confirm' => 'Y',
                'extend' => array('po_type' => $Po['po_type']),
                 );
        if ( method_exists($iostock_instance, 'save_iostockorder') ){
            $eo_data['eo_id'] = $iostock_instance->save_iostockorder($eo_data, $msg);
            $eo_data['eo_bn'] = $iostock_instance->getIoStockOrderBn();
        }

        //日志备注
        $log_msg = '对编号为（'.$Po['po_bn'].'）的采购单进行采购入库，生成一张入库单编号为:'.$eo_data['eo_bn'];

        //更新采购单状态
        foreach($po_items_data as $ke=>$va){
            //更新在途库存
            $oPo->updateBranchProductArriveStore($branch_id, $va['product_id'], $va['in_num'], '-');
            $oPo->db->exec('UPDATE sdb_purchase_po_items SET in_num=IFNULL(in_num,0)+'.$va['in_num'].' WHERE item_id='.$va['item_id']);
            //更新对应状态
            $new_Po_items = $oPo_items->dump($va['item_id'],'in_num,out_num,num');
            $status = 1;
            if($new_Po_items['num']>$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status = 2;
            }else if($new_Po_items['num']==$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status=3;
            }
            if ($va['item_memo']) $update_memo = ",memo='".$va['item_memo']."'";
            $oPo->db->exec(" UPDATE `sdb_purchase_po_items` SET `status`='".$status."'$update_memo WHERE item_id='".$va['item_id']."'");
        }
        //保存入库单
        $eorder_data = array(
                'eo_id'       => $eo_data['eo_id'],
                'supplier_id' => $eo_data['supplier_id'],
                'eo_bn'       => $eo_data['eo_bn'],
                'po_id'       => $po_id,
                'amount'      => $amount,
                'entry_time'  => time(),
                'arrive_time' => $Po['arrive_time'],
                'operator'    => kernel::single('desktop_user')->get_name(),
                'branch_id'   => $branch_id,
                'status'      => $status,

            );
       $oEo->save($eorder_data);
       $new_Po = $oPo->db->selectrow('SELECT SUM(num) as total_num,SUM(in_num) as total_in_num,SUM(out_num) AS total_out_num FROM sdb_purchase_po_items WHERE po_id='.$po_id);
       if($new_Po['total_num']>$new_Po['total_in_num']+$new_Po['total_out_num']){
           $po_data['eo_status'] =2;
       }else{
           $po_data['eo_status'] =3;
           if ($Po['po_status']==1){
                $po_data['po_status'] =4;
           }
       }
       $po_data['po_id'] =$po_id;
       $oPo->save($po_data);

      

       //供应商商品采购价历史记录
       foreach($history_data as $k2=>$v2){

            $v2['supplier_id']=$eo_data['supplier_id'];
            $v2['eo_id'] =$eo_data['eo_id'];
            $v2['eo_bn'] =$eo_data['eo_bn'];
            $v2['purchase_time']=time();
            $v2['in_num'] = $v2['store'];
            $oProduct_batch->save($v2);
       }
       //--采购入库日志记录

       $log_msg .= '<br/>生成了供应商商品采购历史价格记录表';
       $opObj = &app::get('ome')->model('operation_log');
       $opObj->write_log('purchase_storage@purchase', $eo_data['eo_id'], $log_msg);

       return $eo_data['eo_id'];

    }



}
