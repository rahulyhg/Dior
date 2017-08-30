<?php
class purchase_po_to_import {

    function run(&$cursor_id,$params){
        $poObj = &app::get($params['app'])->model($params['mdl']);
        $piObj = &app::get('purchase')->model('po_items');
        $payObj = &app::get('purchase')->model('purchase_payments');
        $poSdf = $params['sdfdata'];
        
        $po = $poObj->dump(array('po_bn'=>$poSdf['po_bn']));
        if (!empty($po)){
            //存在采购单并追加
            $amount = 0;
            foreach ($poSdf['po_items'] as $v){//采购单详情
                $pi = $piObj->dump(array('bn'=>$v['bn'],'po_id'=>$po['po_id']));
                if (!empty($pi)){//更新已有商品
                    $pii['item_id'] = $pi['item_id'];
                    $pii['num'] = $pi['num']+$v['num'];
                    $pii['price'] = $v['price'];
                    $amount += $pii['num']*$pii['price'];

                    $piObj->save($pii);
                }else {//新增商品
                    $v['po_id'] = $po['po_id'];
                    $v['status'] = '1';
                    $amount += $v['num']*$v['price'];

                    $piObj->save($v);
                }

                if($po['check_status'] == 2){
                    //如果采购单已审核，增加branch_product的在途库存
                    $poObj->updateBranchProductArriveStore($poSdf['branch_id'], $v['product_id'], $v['num']);
                }
            }
            $p['po_id'] = $po['po_id'];
            $pay = $payObj->dump(array('po_id'=>$po['po_id']));
            if ($po['po_type'] == 'cash'){
                
                //现购
                //生成付款单
                $row['payment_id'] = $pay['payment_id'];
                $row['payable']    = $amount+$poSdf['delivery_cost'];
                $row['deposit']    = 0;
                $row['product_cost'] = $amount;
                $row['delivery_cost'] = $poSdf['delivery_cost'];
                $row['supplier_id']   = $poSdf['supplier_id'];
                
                //$payObj->save($row); 2011.11.15屏蔽
                
                $p['amount'] = $amount+$poSdf['delivery_cost'];
                $p['product_cost'] = $amount;
                $p['delivery_cost'] = $poSdf['delivery_cost'];
                $p['memo'] = $po['memo'].";".$poSdf['memo'];
                
                $poObj->save($p);
            }else {
                //赊购
                $p['product_cost'] = $amount;
                $p['amount'] = $amount;
                $p['delivery_cost'] = $poSdf['delivery_cost'];
                $p['memo'] = $po['memo'].";".$poSdf['memo'];
                $poObj->save($p);
            }
        }else {
            //新建采购单
            $psdf = $poSdf;
            unset($psdf['po_items']);
            
            $poObj->save($psdf);
            $po_id = $psdf['po_id'];
            $amount = 0;
            foreach ($poSdf['po_items'] as $i){//采购单详情
                $i['po_id'] = $po_id;
                $i['status'] = '1';
                $amount += $i['price']*$i['num'];
                
                $piObj->save($i);
                //增加branch_product的在途库,新建采购单无需增加在途库存
                //$poObj->updateBranchProductArriveStore($poSdf['branch_id'], $i['product_id'],$i['num']);
            }
            $pay_bn = $payObj->gen_id();
            $p['po_id'] = $po_id;
            $p['name'] = date('Ymd',time()).'采购单';
            if ($poSdf['po_type'] == 'cash'){
                
                //现购
                //生成付款单
                $row['payment_bn'] = $pay_bn;
                $row['po_id']      = $po_id;
                $row['po_type']    = $poSdf['po_type'];
                $row['add_time']   = time();
                $row['payable']    = $amount+$poSdf['delivery_cost'];
                $row['deposit']    = 0;
                $row['product_cost'] = $amount;
                $row['delivery_cost'] = $poSdf['delivery_cost'];
                $row['supplier_id']   = $poSdf['supplier_id'];
                
                //$payObj->save($row); 2011.11.15屏蔽
                
                $p['amount'] = $amount+$poSdf['delivery_cost'];
                $p['product_cost'] = $amount;
                $poObj->save($p);
            }else {
                //赊购
                if ($poSdf['deposit'] != '' && $poSdf['deposit'] != 0){//预付款不为0时
                    //生成付款单
                    $row['payment_bn'] = $pay_bn;
                    $row['po_id']      = $po_id;
                    $row['po_type']    = $poSdf['po_type'];
                    $row['add_time']   = time();
                    $row['payable']    = $poSdf['deposit'];
                    $row['deposit']    = $poSdf['deposit'];
                    $row['product_cost'] = 0;
                    $row['delivery_cost'] = 0;
                    $row['supplier_id']   = $poSdf['supplier_id'];
                    
                    //$payObj->save($row); 2011.11.15屏蔽
                }
                $p['product_cost'] = $amount;
                $p['amount'] = $amount;
                $poObj->save($p);
            }
        }
        return false;
    }
}
