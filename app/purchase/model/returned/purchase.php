<?php

class purchase_mdl_returned_purchase extends dbeav_model{
    var $has_many = array(
        'returned_purchase_items' => 'returned_purchase_items',);

   /*
    * 退货单编号
    */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $rp_bn = date('YmdH').'17'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT rp_bn from sdb_purchase_returned_purchase where rp_bn =\''.$rp_bn.'\'');
        }while($row);
        return 'H'.$rp_bn;
    }

    function createReturnPurchase($sdf){
        $sdf['rp_bn'] = $this->gen_id();
        $this->save($sdf);
        return $sdf['rp_id'];
    }

    /*
     *  保存退货单信息
     * $adata=array(
     * 'items_data'=array();
     * );
     */
    function to_save($adata){

       $oPurchase_items = &$this->app->model("returned_purchase_items");
       $oPo = &$this->app->model("po");
       $product_cost=0;

       foreach($adata['items'] as $k=>$v){
           $product_cost+=$v['out_num']*$v['price'];

       }
       //product_cost product_cost
       $op_name = kernel::single('desktop_user')->get_name();
       $newmemo =  htmlspecialchars($adata['memo']);
       $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
       $memo = serialize($memo);

       $data = array(
           'rp_bn'=>$this->gen_id(),
           'supplier_id'=>$adata['supplier_id'],
           'operator'=>$adata['operator'],
           'delivery_cost'=>$adata['delivery_cost'],
           'logi_no'=>$adata['logi_no'],
           'product_cost'=>$product_cost,
           'po_type'=>$adata['po_type'],
           'purchase_time'=>$adata['purchase_time'],
           'returned_time'=>$adata['returned_time'],
           'branch_id'=>$adata['branch_id'],
           'amount'=>$product_cost+$adata['delivery_cost'],
           'rp_type'=>$adata['rp_type'],
           'arrive_time'=>$adata['arrive_time'],
           'object_id'=>$adata['object_id'],
           'memo'=>$memo,
          );

       $this->save($data);
       /*更新采购单状态*/
       $po_data = array(
           'po_id'=>$adata['po_id'],
           'po_status'=>'3'
       );
       $oPo->save($po_data);
       foreach($adata['items'] as $key=>$val){
           if ($val['memo']) $update_memo = ",memo='".$val['memo']."'";
           $this->db->exec("UPDATE `sdb_purchase_eo_items` SET `out_num`=`out_num`+'".$val['out_num']."'$update_memo WHERE item_id='".$val['item_id']."'");

           $val['rp_id']=$data['rp_id'];
           $val['num']=$val['out_num'];
           $oPurchase_items->save($val);

           /*扣库存*/
           $this->db->exec('UPDATE sdb_purchase_branch_product_batch SET out_num=out_num+'.$val['out_num'].' WHERE eo_id='.$adata['object_id'].' AND product_id='.$val['product_id']);
           $pos_data['items'][]=array('product_id'=>$val['product_id'],'pos_id'=>$val['pos_id'],'num'=>$val['out_num']);
        }
        $pos_data['branch_id']=$adata['branch_id'];
        $oBranch_product = &app::get('ome')->model("branch_product");

        $oBranch_product->Cut_store($pos_data);//减仓库表库存

        /*start生成退款单*/
        $oRefunds = &$this->app->model("purchase_refunds");
        $refund_data=array(
            'operator'=>kernel::single('desktop_user')->get_name(),
            'refund_bn'=>$oRefunds->gen_id(),
            'add_time'=>time(),
            'supplier_id'=>$adata['supplier_id'],
            'po_type'=>$adata['po_type'],
            'type'=>'eo',
            'refund'=>$product_cost+$adata['delivery_cost'],
            /*运费*/
            'delivery_cost'=>$adata['delivery_cost'],
            'product_cost'=>$product_cost,
            'rp_id'=>$data['rp_id'],
            'op_id'=>kernel::single('desktop_user')->get_id(),
           );
        $oRefunds->save($refund_data);

        //--生成采购退货日志记录
        $log_msg = '对入库单进行了采购退货，生成了编号为:'.$data['rp_bn'].'的退货单，并且同时生成了编号为：'.$refund_data['refund_bn'].'的退款单';
        $opObj = &app::get('ome')->model('operation_log');
        $opObj->write_log('purchase_refund@purchase', $data['rp_id'], $log_msg);

       /*end*/
   }

   /*
    * 退货商品明细 returned_items
    * @param int 退款ID
    */
   function returned_purchase_items($rp_id=null){
       $oReturned_items = &$this->app->model('returned_purchase_items');
       $oProducts = &app::get('ome')->model('products');
       $filter = array('rp_id'=>$rp_id);
       $returned_detail = $oReturned_items->getList('*', $filter, 0, -1);
       $rp_items = array();
       if ($returned_detail)
       foreach ($returned_detail as $k=>$v){
           $product_detail = $oProducts->dump($v['product_id'], 'name,bn');
           $v['bn'] = $product_detail['bn'];
           $v['name'] = $product_detail['name'];
           $total_num += $v['num'];
           $total_price += $v['num']*$v['price'];
           $rp_items[] = $v;
       }
       $result['items'] = $rp_items;
       $result['total_num'] = $total_num;
       $result['total_price'] = $total_price;
       return $result;
   }

   //退货日期格式化
   function modifier_returned_time($row){
       $tmp = date('Y-m-d',$row);
       return $tmp;
    }
   //到货日期格式化
   function modifier_arrive_time($row){
       $tmp = date('Y-m-d',$row);
       return $tmp;
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
                    '*:货品名称' => 'name',
                    '*:货品规格' => 'spec_info',
                    '*:条形码' => 'barcode',
                    '*:采购数量' => 'purchase_num',
                    '*:采购价格' => 'purchase_price',
                    '*:已退数量' => 'returned_num',
                    '*:退货数量' => 'num',
                    '*:退货价格' => 'price',
                    '*:备注' => 'memo',
                );
                break;
            case 'return':
                $this->oSchema['csv'][$filter] = array(
                    '*:退货单号(CSCID)' => 'rp_bn',
                    '*:入库单号' => 'eo_bn',
                    '*:供应商' => 'supplier',
                    '*:退货仓库编号' => 'branch_bn',
                    '*:经办人' => 'operator',
                    '*:物流费用' => 'delivery_cost',
                    '*:物流单号' => 'logi_no',
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
        //$data = base_kvstore::instance('purchase_eo')->fetch('eo-'.$this->ioObj->cacheTime);
        $data = $this->import_data;
        unset($this->import_data);

        //base_kvstore::instance('purchase_eo')->store('eo-'.$this->ioObj->cacheTime,'');
        $oQueue = &app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();
        $eo = $this->app->model('eo')->dump(array('eo_bn'=>$aP['eo']['contents'][0][1]));
        $po = $this->app->model('po')->dump($eo['po_id']);

        $pSdf['rp_bn']          = $aP['eo']['contents'][0][0];
        $pSdf['branch']         = $aP['eo']['contents'][0][3];//退货仓库
        $pSdf['operator']       = $aP['eo']['contents'][0][4];
        $pSdf['delivery_cost']  = $aP['eo']['contents'][0][5];
        $pSdf['logi_no']        = $aP['eo']['contents'][0][6];
        $pSdf['memo']           = $aP['eo']['contents'][0][7];
        $pSdf['eo_id']          = $eo['eo_id'];
        $pSdf['po_id']          = $po['po_id'];
        $pSdf['supplier_id']    = $aP['eo']['contents'][0][2];
        $pSdf['branch_id']      = $aP['eo']['contents'][0][8];
        $pSdf['po_type']        = $po['po_type'];
        $pSdf['purchase_time']  = $po['purchase_time'];
        $pSdf['return_time']    = time();
        $pSdf['arrive_time']    = $po['arrive_time'];
        $pSdf['object_id']      = $eo['eo_id'];
        $pSdf['rp_type']        = 'eo';
        $pSdf['amount']         = $aP['eo']['contents'][0][4];
        $pSdf['product_cost']   = 0;
        $pSdf['op_name']        = kernel::single('desktop_user')->get_name();
        $pSdf['op_id']          = kernel::single('desktop_user')->get_id();

        foreach ($aP['item']['contents'] as $k => $aPi){
            $p = app::get('ome')->model('products')->dump(array('bn'=>$aPi[0]));
            $pi = $this->app->model('po_items')->dump(array('po_id'=>$eo['po_id'],'product_id'=>$p['product_id']));
            $ei = $this->app->model('eo_items')->dump(array('eo_id'=>$eo['eo_id'],'product_id'=>$p['product_id']));

            $pSdf['return_items'][$k]['product_id'] = $p['product_id'];
            $pSdf['return_items'][$k]['bn'] = $aPi[0];
            $pSdf['return_items'][$k]['name'] = $pi['name'];
            $pSdf['return_items'][$k]['spec_info'] = $pi['spec_info'];
            $pSdf['return_items'][$k]['purchase_num'] = $pi['num'];
            $pSdf['return_items'][$k]['num'] = $aPi[7];
            $pSdf['return_items'][$k]['purchase_price'] = $pi['price'];
            $pSdf['return_items'][$k]['price'] = $aPi[8];
            $pSdf['return_items'][$k]['memo'] = $aPi[9];
            $pSdf['return_items'][$k]['po_item_id'] = $pi['item_id'];
            $pSdf['return_items'][$k]['eo_item_id'] = $ei['item_id'];
            $pSdf['return_items'][$k]['pos_id'] = $ei['pos_id'];
            $pSdf['amount'] += $aPi[7]*$aPi[8];
            $pSdf['product_cost'] += $aPi[7]*$aPi[8];
        }

        $queueData = array(
            'queue_title'=>'采购退货单导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$pSdf,
                'app' => 'purchase',
                'mdl' => 'returned_purchase'
            ),
            'worker'=>'purchase_po_return_import.run',
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
                if ($this->null_bn){
                    $temp = $this->null_bn;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n入库单中不存在的商品货号：';
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
                    $msg['error'] .= '\n退货数量大于可退货数量的商品货号：';
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
                return false;
            }

            //$msg['error'] = str_replace("\n",'',var_export($this->flag,1));return false;
            return true;
        }
        $mark = false;
        //$fileData = base_kvstore::instance('purchase_eo')->fetch('eo-'.$this->ioObj->cacheTime);
        $fileData = $this->import_data;

        if( !$fileData )
            $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }else{
            if( $row[0] ){
                if( array_key_exists( '*:货号',$title )  ) {
                    //检测货号是否正确
                    $oProducts = app::get('ome')->model('products');
                    $prodcuts = $oProducts->dump(array('bn'=>$row[0]),'product_id');
                    if(!$prodcuts){
                        $this->flag = true;
                        $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[0])):array($row[0]);
                    }

                    //检测货号是否在入库单中
                    /*
                    $eo = $this->app->model('eo')->dump(array('eo_bn'=>$fileData['eo']['contents'][0][1]),'eo_id');
                    $_filter['eo_id'] = $eo['eo_id'];
                    $_filter['bn'] = $row[0];
                    $ei = app::get('purchase')->model('eo_items')->dump($_filter,'item_id,entry_num,out_num');
                    if (!$ei){
                        $this->flag = true;
                        $this->null_bn = isset($this->null_bn)?array_merge($this->null_bn,array($row[0])):array($row[0]);
                    }else {
                        $num = $ei['entry_num'] - $ei['out_num'];
                        if ($row[7] > $num){
                        $this->flag = true;
                        $this->need_num = isset($this->need_num)?array_merge($this->need_num,array($row[0])):array($row[0]);
                        }
                    }
                    */

                    //检测库存数量是否大于退货数量
                    //$msg['error'] = str_replace("\n",'',var_export($fileData['item']['contents'],1));return false;
                    $oBranchProduct = &app::get('ome')->model('branch_product');
                    $prodcuts = $oBranchProduct->dump(
                        array(
                            'product_id'=>$prodcuts['product_id'],
                            'branch_id'=>$fileData['eo']['contents'][0][8]
                        ),
                        'store'
                    );
                    $prodcuts['store'] = intval($prodcuts['store']);

                    if ($row[7] > $prodcuts['store']){
                        $overflow_store = $row[7] - $prodcuts['store'];
                        $this->flag = true;
                        $this->need_num = isset($this->need_num)?array_merge($this->need_num,array($row[0].'，少于'.$overflow_store)):array($row[0].'，少于'.$overflow_store);
                    }//$msg['error'] = str_replace("\n",'',var_export($prodcuts,1));return false;

                    //检测文件内重复的货号
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
                    $oBranch = app::get('ome')->model('branch');
                    $branch = $oBranch->dump(array('branch_bn'=>$row[3]),'branch_id');
                    $row[8] = $branch['branch_id'];


                    $rp = $this->dump(array('rp_bn'=>$row[0]));
                    if ($rp){
                        $msg['error'] = "此退货单号已存在:".$row[0];
                        return false;
                    }

                    /*
                    $eo = $this->app->model('eo')->dump(array('eo_bn'=>$row[1]),'eo_id');
                    if ( !$eo ){
                        $msg['error'] = "无此入库单";
                        return false;
                    }
                    */

                    $fileData['eo']['contents'][] = $row;
                }

                $this->import_data = $fileData;
                //$msg = base_kvstore::instance('purchase_eo')->store('eo-'.$this->ioObj->cacheTime,$fileData);
            }

        }
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    function searchOptions(){
        return array(
                'name'=>app::get('base')->_('退货单名称'),
                'rp_bn'=>app::get('base')->_('退货单编号'),
            );
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'purchase';
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_returned_purchase') {
            $type .= '_purchaseReturn';
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'purchase';
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_returned_purchase') {
            $type .= '_purchaseReturn';
        }
        $type .= '_import';
        return $type;
    }


}