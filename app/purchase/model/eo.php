<?php

class purchase_mdl_eo extends dbeav_model{

/*
 * 将采购单入库
 * 采购单入库会分配货位，生成供应商商品采购价历史记录
 * 更新库存
 */
    function save_eo($data){

        $oPo = &$this->app->model("po");
        $supplierObj = &$this->app->model("supplier");
        $oPo_items = &$this->app->model("po_items");
        $oProducts = &app::get('ome')->model("products");
        $oEo = &$this->app->model("eo");
        $oEo_items = &$this->app->model("eo_items");
        $oCredit_sheet = &$this->app->model("credit_sheet");
        $oProduct_pos = &app::get('ome')->model("branch_product_pos");
        $oBranch_pos = &app::get('ome')->model("branch_pos");
        $oProduct_batch = &$this->app->model("branch_product_batch");
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
                $su_goodsObj = &$this->app->model('supplier_goods');
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

        $iostock_instance = kernel::service('taoguaniostockorder.iostockorder');
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
            $this->db->exec('UPDATE sdb_purchase_po_items SET in_num=IFNULL(in_num,0)+'.$va['in_num'].' WHERE item_id='.$va['item_id']);
            //更新对应状态
            $new_Po_items = $oPo_items->dump($va['item_id'],'in_num,out_num,num');
            $status = 1;
            if($new_Po_items['num']>$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status = 2;
            }else if($new_Po_items['num']==$new_Po_items['in_num']+$new_Po_items['out_num']){
                $status=3;
            }
            if ($va['item_memo']) $update_memo = ",memo='".$va['item_memo']."'";
            $this->db->exec(" UPDATE `sdb_purchase_po_items` SET `status`='".$status."'$update_memo WHERE item_id='".$va['item_id']."'");
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
       $new_Po = $this->db->selectrow('SELECT SUM(num) as total_num,SUM(in_num) as total_in_num,SUM(out_num) AS total_out_num FROM sdb_purchase_po_items WHERE po_id='.$po_id);
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

       //生成赊购单start
       /*
       if($Po['po_type']=='credit'){
           $delivery_cost = $Po['delivery_cost'];
           $credit_data = array(
               'cs_bn'=>$oCredit_sheet->gen_id(),
               'add_time'=>time(),
               'supplier_id'=>$Po['supplier_id'],
               'operator'=>kernel::single('desktop_user')->get_name(),
               'op_id'=>kernel::single('desktop_user')->get_id(),
               'payable'=>$amount+$delivery_cost,
               'eo_id'=>$eo_data['eo_id'],
               'delivery_cost'=>$delivery_cost,
               'product_cost'=>$amount
           );

           //累加总费用
           $delivery_cost = is_numeric($delivery_cost) ? $delivery_cost : '0';
           if ($delivery_cost)
           $this->db->exec('UPDATE `sdb_purchase_po` SET `amount`=`amount`+'.$delivery_cost.' WHERE `po_id`='.$po_id.' ');
           $log_msg .= '<br/>生成一张编号为：'.$credit_data['cs_bn'].'的赊购单';
           kernel::single('purchase_credit_sheet')->save_credit_sheet($credit_data, $msg);
       }
       */

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

       return $Po['supplier_id'];

    }

    /*
    * 入库编号
    */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $eo_bn = date('YmdH').'16'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT eo_bn from sdb_purchase_eo where eo_bn =\''.$eo_bn.'\'');
        }while($row);
        return $eo_bn;
    }

    /*
     * 获取入库单明细
     * @param $eo_id
     */
    function eo_detail($eo_id){
        $eo_de = $this->dump($eo_id,'*');
        $eo['detail'] = $eo_de;
        $oBranch_pos = &app::get('ome')->model("branch_pos");
        $oProducts = &app::get('ome')->model("products");
        $oBranch = &app::get('ome')->model("branch");
        $oSupplier = &$this->app->model("supplier");
        $oPo_items = &$this->app->model("po_items");
        $Branch = $oBranch->dump($eo_de['branch_id'],'name');
        $supplier = $oSupplier->dump($eo_de['supplier_id'],'name');
        $eo['supplier_name'] = $supplier['name'];

        $eo['branch_name']=$Branch['name'];
         /*采购单信息*/
        $oPo = &$this->app->model("po");
        $Po = $oPo->dump($eo_de['po_id'],'purchase_time,po_type,arrive_time');
        $oEo_items = &$this->app->model("eo_items");
        $items = $oEo_items->getList('*',array('eo_id'=>$eo_id));
        $reship_money = 0;
        foreach($items as $k=>$v){
            $items[$k]['over_num'] = $oEo_items->Get_num($v['item_id']);
            //增加采购单规格值的显示
            $po_items = $oPo_items->dump(array('po_id'=>$eo_de['po_id'],'product_id'=>$v['product_id']),'price,spec_info');
            $items[$k]['price'] = $po_items['price'];
            $pos = $oBranch_pos->dump($v['pos_id'],'store_position');
            $items[$k]['store_position']=$pos['store_position'];
            $reship_money+=$v['out_num']*$po_items['price'];
//            $product = $oProducts->dump($v['product_id'],'spec_info');
//            $items[$k]['spec_info']= $product['spec_info'];
            //用eo_items的spec_info代替 products的spec_info值
            $items[$k]['spec_info']= $po_items['spec_info'];

        }


        $eo['items'] = $items;
        $eo['purchase_time'] = $Po['purchase_time'];
        $eo['arrive_time'] = $Po['arrive_time'];
        $eo['po_type'] = $Po['po_type'];
        $eo['limit_money'] = $eo_de['amount']-$reship_money;
        return $eo;
    }


   /*
     * 获取入库单明细
     * @param $eo_id
     */
    function eo_detail_iso($eo_id){
        $iostock_instance = kernel::service('taoguaniostockorder.iostockorder');
        $eo_de = $iostock_instance->getIso($eo_id);
        $eo['detail'] = $eo_de;
        $oBranch_pos = &app::get('ome')->model("branch_pos");
        $oProducts = &app::get('ome')->model("products");
        $oBranch = &app::get('ome')->model("branch");
        $oSupplier = &$this->app->model("supplier");
        $oPo_items = &$this->app->model("po_items");
        $Branch = $oBranch->dump($eo_de['branch_id'],'name');
        $supplier = $oSupplier->dump($eo_de['supplier_id'],'name');
        $eo['supplier_name'] = $supplier['name'];
        $eo['branch_name']=$Branch['name'];
        $oPo = &$this->app->model("po");
        $Po = $oPo->dump($eo_de['original_id'],'purchase_time,po_type,arrive_time');

        $items = $iostock_instance->getIsoItems($eo_id);
        foreach($items as $k=>$v){
            $po_items = $oPo_items->dump(array('po_id'=>$eo_de['original_id'],'product_id'=>$v['product_id']),'price,spec_info');
            $items[$k]['price'] = $po_items['price'];
            $items[$k]['spec_info']= $po_items['spec_info'];
        }

        $eo['items'] = $items;
        $eo['purchase_time'] = $Po['purchase_time'];
        $eo['arrive_time'] = $Po['arrive_time'];
        $eo['po_type'] = $Po['po_type'];
        return $eo;
    }


    /*
     * 采购退货
     */
    function save_eo_cancel($eo_id,$data){

        $oEo_items = &$this->app->model("eo_items");
        $oReturned_purchase = &$this->app->model("returned_purchase");
        $eo = $this->dump($eo_id,'branch_id,supplier_id,po_id');
        $oPo = &$this->app->model("po");
        $Po = $oPo->dump($eo['po_id'],'po_type,arrive_time,purchase_time');
        $adata = array(
            'branch_id'=>$eo['branch_id'],
            'supplier_id'=>$eo['supplier_id'],
            'po_type'=>$Po['po_type'],
            'rp_type'=>'eo',
            'arrive_time'=>$Po['arrive_time'],
            'purchase_time'=>$Po['purchase_time'],
            'returned_time'=>time(),
            'object_id'=>$eo_id,
            'po_id'=>$eo['po_id'],

        );
        $amount = 0;
        foreach($data AS $k=>$v){
            $adata['operator']=$v['operator'];
            //delivery_cost
            $adata['delivery_cost']=$v['delivery_cost'];
            $adata['logi_no']=$v['logi_no'];
            $adata['memo']=$v['memo'];
            $eo_items = $oEo_items->dump($v['item_id'],'product_id,pos_id,bn,product_name,spec_info');

            $adata['items'][] = array(
                        'out_num'=>$v['out_num'],
                        'price'=>$v['price'],
                        'product_id'=>$eo_items['product_id'],
                        'pos_id'=>$eo_items['pos_id'],
                        'name'=>$eo_items['product_name'],
                        'spec_info'=>$eo_items['spec_info'],
                        'bn'=>$eo_items['bn'],
                        'item_id'=>$v['item_id'],
                        'memo'=>$v['item_memo']
            );
        }

        $oReturned_purchase->to_save($adata);/*生成采购退货单*/

        $this->change_eo_status($eo_id);
    }

    /*
     * 修改入库单状态
     *
     */
    function change_eo_status($eo_id){
        $eo_num = $this->db->selectrow('SELECT SUM(entry_num) as total_entry_num,SUM(out_num) AS total_out_num FROM sdb_purchase_eo_items WHERE eo_id='.$eo_id);
        $total_entry_num = $eo_num['total_entry_num'];
        $total_out_num = $eo_num['total_out_num'];

        if($total_entry_num>$total_out_num){
            $status=2;
        }else{
            $status=3;
        }

        $data['status']=$status;
        $data['eo_id']=$eo_id;
        $this->save($data);
    }

    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

     function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'item':
                $this->oSchema['csv'][$filter] = array(
                    '*:货号' => 'bn',
                    '*:货品名称' => 'product_name',
                    '*:货品规格' => 'spec_info',
                    '*:条形码' => 'barcode',
                    '*:数量' => 'num',
                    '*:价格' => 'price',
                    '*:货位' => 'pos',
                );
                break;
            case 'eo':
                $this->oSchema['csv'][$filter] = array(
                    '*:CSCID' => 'eo_bn',
                    '*:采购单编号' => 'po_bn',
                    '*:供应商' => 'supplier',
                    '*:到货仓库' => 'branch',
                    '*:采购方式' => 'po_type',
                    '*:是否有物流费：费用' => 'delivery_cost',
                    '*:预计到货天数' => 'arrive',
                    '*:经办人' => 'operator',
                    '*:备注' => 'memo',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        base_kvstore::instance('purchase_eo')->fetch('eo-'.$this->ioObj->cacheTime,$data);

        base_kvstore::instance('purchase_eo')->store('eo-'.$this->ioObj->cacheTime,'');
        $oQueue = &app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();
        $po = $this->app->model('po')->dump(array('po_bn'=>$aP['eo']['contents'][0][1]));

        $pSdf['eo_bn']          = $aP['eo']['contents'][0][0];
        $pSdf['delivery_cost']  = $po['delivery_cost'];
        $pSdf['arrive_time']    = $po['arrive_time'];
        $pSdf['operator']       = $aP['eo']['contents'][0][7];
        $pSdf['memo']           = $aP['eo']['contents'][0][8];
        $pSdf['po_id']          = $po['po_id'];
        $pSdf['supplier_id']    = $po['supplier_id'];
        $pSdf['branch_id']      = $po['branch_id'];
        $pSdf['entry_time']     = time();
        $pSdf['po_type']        = $po['po_type'];
        $pSdf['op_name']        = kernel::single('desktop_user')->get_name();
        $pSdf['op_id']          = kernel::single('desktop_user')->get_id();


        foreach ($aP['item']['contents'] as $k => $aPi){
            $p = app::get('ome')->model('products')->dump(array('bn'=>$aPi[0]));
            $pos = app::get('ome')->model('branch_pos')->dump(array('store_position'=>$aPi[6]));
            $pi = $this->app->model('po_items')->dump(array('po_id'=>$po['po_id'],'product_id'=>$p['product_id']));
            $pSdf['eo_items'][$k]['product_id'] = $p['product_id'];
            $pSdf['eo_items'][$k]['bn'] = $pi['bn'];
            $pSdf['eo_items'][$k]['product_name'] = $pi['name'];
            $pSdf['eo_items'][$k]['spec_info'] = $pi['spec_info'];
            $pSdf['eo_items'][$k]['purchase_num'] = $pi['num'];
            $pSdf['eo_items'][$k]['entry_num'] = $aPi[4];
            $pSdf['eo_items'][$k]['price'] = $pi['price'];
            $pSdf['eo_items'][$k]['unit'] = $p['unit'];
            $pSdf['eo_items'][$k]['pos_id'] = $pos['pos_id'];
            $pSdf['eo_items'][$k]['goods_id'] = $p['goods_id'];
            $pSdf['eo_items'][$k]['po_item_id'] = $pi['item_id'];
            $pSdf['amount'] += $pi['price']*$aPi[4];
        }

        $queueData = array(
            'queue_title'=>'入库单导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$pSdf,
                'app' => 'purchase',
                'mdl' => 'eo'
            ),
            'worker'=>'purchase_po_eo_import.run',
        );

        $oQueue->save($queueData);

        return null;
    }

    //导入
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){

        if (empty($row)){
            if ($this->flag){
                if ($this->not_exist_product_bn){
                    $temp = $this->not_exist_product_bn;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n数据库中不存在的商品货号：';
                    $ms = '';
                    foreach ($tmp as $k => $v){
                        if ($k >= 10){
                            $ms = '...\n';
                            break;
                        }
                        if ($k < 5){
                            $tmp1[] = $v;
                            continue;
                        }
                        $tmp2[] = $v;
                    }
                    $msg['error'] .= '\n'.implode(',', $tmp1);
                    if (!empty($tmp2)) $msg['error'] .= '\n'.implode(',', $tmp2);
                    $msg['error'] .= $ms;
                    $tmp1 = null;
                    $tmp2 = null;
                }
                if ($this->same_product_bn){
                    $temp = $this->same_product_bn;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n文件中重复的商品货号：';
                    $ms = '';
                    foreach ($tmp as $k => $v){
                        if ($k >= 10){
                            $ms = '...\n';
                            break;
                        }
                        if ($k < 5){
                            $tmp1[] = $v;
                            continue;
                        }
                        $tmp2[] = $v;
                    }
                    $msg['error'] .= '\n'.implode(',', $tmp1);
                    if (!empty($tmp2)) $msg['error'] .= '\n'.implode(',', $tmp2);
                    $msg['error'] .= $ms;
                    $tmp1 = null;
                    $tmp2 = null;
                }
                if ($this->null_product_bn){
                    $temp = $this->null_product_bn;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n采购单中不存在的商品货号：';
                    $ms = '';
                    foreach ($tmp as $k => $v){
                        if ($k >= 10){
                            $ms = '...\n';
                            break;
                        }
                        if ($k < 5){
                            $tmp1[] = $v;
                            continue;
                        }
                        $tmp2[] = $v;
                    }
                    $msg['error'] .= '\n'.implode(',', $tmp1);
                    if (!empty($tmp2)) $msg['error'] .= '\n'.implode(',', $tmp2);
                    $msg['error'] .= $ms;
                    $tmp1 = null;
                    $tmp2 = null;
                }
                if ($this->null_pos){
                    $temp = $this->null_pos;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n数据库中不存在的货位：';
                    $ms = '';
                    foreach ($tmp as $k => $v){
                        if ($k >= 10){
                            $ms = '...\n';
                            break;
                        }
                        if ($k < 5){
                            $tmp1[] = $v;
                            continue;
                        }
                        $tmp2[] = $v;
                    }
                    $msg['error'] .= '\n'.implode(',', $tmp1);
                    if (!empty($tmp2)) $msg['error'] .= '\n'.implode(',', $tmp2);
                    $msg['error'] .= $ms;
                    $tmp1 = null;
                    $tmp2 = null;
                }
                if ($this->need_num){
                    $temp = $this->need_num;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n入库数量大于需要入库的数量的商品货号：';
                    $ms = '';
                    foreach ($tmp as $k => $v){
                        if ($k >= 10){
                            $ms = '...\n';
                            break;
                        }
                        if ($k < 5){
                            $tmp1[] = $v;
                            continue;
                        }
                        $tmp2[] = $v;
                    }
                    $msg['error'] .= '\n'.implode(',', $tmp1);
                    if (!empty($tmp2)) $msg['error'] .= '\n'.implode(',', $tmp2);
                    $msg['error'] .= $ms;
                    $tmp1 = null;
                    $tmp2 = null;
                }
                base_kvstore::instance('purchase_eo')->store('eo-'.$this->ioObj->cacheTime,'');
                return false;
            }
            return true;
        }
        $mark = false;
        $re = base_kvstore::instance('purchase_eo')->fetch('eo-'.$this->ioObj->cacheTime,$fileData);

        if( !$re )
            $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }else{
            if( $row[0] ){
                if( array_key_exists( '*:货号',$title )  ) {
                    if(!app::get('ome')->model('products')->dump(array('bn'=>$row[0]))){
                        $this->flag = true;
                        $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[0])):array($row[0]);
                    }
                    $purchase = $this->app->model('po')->dump(array('po_bn'=>$fileData['eo']['contents'][0][1]));
                    $_filter['po_id'] = $purchase['po_id'];
                    $_filter['bn'] = $row[0];
                    $pi = app::get('purchase')->model('po_items')->dump($_filter);
                    if (!$pi){
                        $this->flag = true;
                        $this->null_product_bn = isset($this->null_product_bn)?array_merge($this->null_product_bn,array($row[0])):array($row[0]);
                    }else {
                        $need = $pi['num'] - $pi['in_num'] - $pi['out_num'];
                        if ($row[4] > $need){
                            $this->flag = true;
                            $this->need_num = isset($this->need_num)?array_merge($this->need_num,array($row[0])):array($row[0]);
                        }
                    }
                    $pos = app::get('ome')->model('branch_pos')->dump(array('store_position'=>$row[6]));
                    if (!$pos){
                        $this->flag = true;
                        $this->null_pos = isset($this->null_pos)?array_merge($this->null_pos,array($row[6])):array($row[6]);
                    }
                    if ($fileData['item']['contents']){
                        foreach ($fileData['item']['contents'] as $v){
                            if ($row[0] == $v[0]){
                                $this->flag = true;
                                $this->same_product_bn = isset($this->same_product_bn)?array_merge($this->same_product_bn,array($row[0])):array($row[0]);
                            }
                        }
                    }

                    $fileData['item']['contents'][] = $row;
                }else {
                    $eo = $this->dump(array('eo_bn'=>$row[0]));
                    $purchase = $this->app->model('po')->dump(array('po_bn'=>$row[1]));
                    if ( $purchase ){
                        if ($purchase['eo_status'] == '3' || $purchase['po_status'] != '1'){
                            $msg['error'] = "此采购单入库已结束";
                            return false;
                        }
                    }else {
                        $msg['error'] = "无此采购单";
                        return false;
                    }
                    if ($eo){
                        $msg['error'] = "此入库单号已存在 ";
                        return false;
                    }

                    $fileData['eo']['contents'][] = $row;
                }
                base_kvstore::instance('purchase_eo')->store('eo-'.$this->ioObj->cacheTime,$fileData);
            }

        }
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

   //退货日期格式化
   function modifier_arrive_time($row){
       $tmp = date('Y-m-d',$row);
       return $tmp;
    }

    function searchOptions(){
        return array(

            );
    }
}
?>
