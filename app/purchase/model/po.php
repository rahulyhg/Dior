<?php

class purchase_mdl_po extends dbeav_model{
    var $po_status = null;
    var $has_many = array(
        'po_items' => 'po_items');

    /*
    * 采购单编号
    */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $po_bn = date('YmdH').'17'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT po_bn from sdb_purchase_po where po_bn =\''.$po_bn.'\'');
        }while($row);
        return 'I'.$po_bn;
    }

    function ExistFinishPurchase($po_id){
        $sql = 'SELECT SUM(num) as total_num,SUM(in_num) as total_in_num,SUM(out_num) AS total_out_num FROM sdb_purchase_po_items WHERE po_id='.$po_id;
        $new_Po = $this->db->selectrow($sql);
        if($new_Po['total_num']>$new_Po['total_in_num']+$new_Po['total_out_num']){
            return false;
        }else{
            return true;
        }
    }

    /*
     * 更新采购单状态
     */
    function change_po_status($adata){

    }

    /**
     * 获取采购单明细
     *
     * @param int $po_id
     * @return array()
     */
    function getPoItemByPoId($po_id){
        $po_itemObj = &$this->app->model('po_items');
        $filter['po_id'] = $po_id;
        $rows = $po_itemObj->getList('*', $filter, 0, -1);

        return $rows;
    }

    /*
     * 去除多维数组重复键值
     */
    function assoc_unique($arr, $key) {
        $tmp_arr = array();
        foreach($arr as $k => $v) {
            if(in_array($v[$key], $tmp_arr)) {
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key]; //
            }
        }
        sort($arr);
        return $arr;
    }

    function getSafeStock($data=null, $start=0, $end=1){
        $where = ' ';

        if ($data['branch_id']){
            $where .= " AND bp.branch_id=".$data['branch_id'];
        }
        if ($data['bn']){
            $data['bn'] = trim($data['bn']);
            $where .= " AND p.bn = '".$data['bn']."' ";
        }
        if ($data['supplier'] && $data['supplier'] != 0){
            $where .= " AND sg.supplier_id = '".$data['supplier']."' ";
        }else {
            if ($data['supplier_name'] != ''){
                $result['count'] = 0;
                return $result;
            }
        }

        $limit = " LIMIT $start,$end ";

        $sql = "SELECT p.product_id,sg.supplier_id,s.name AS 'supplier_name',p.name,p.bn,p.unit,p.spec_info,bp.store,bp.arrive_store,bp.safe_store,bp.store_freeze
                        FROM sdb_ome_branch_product bp
                            JOIN sdb_ome_products p
                                ON bp.product_id=p.product_id
                            LEFT JOIN sdb_purchase_supplier_goods sg
                                ON p.goods_id=sg.goods_id
                            LEFT JOIN sdb_purchase_supplier s
                                ON s.supplier_id=sg.supplier_id
                             WHERE p.visibility='true' $where AND (IF(bp.store+bp.arrive_store<bp.store_freeze,0,bp.store+bp.arrive_store-bp.store_freeze)<bp.safe_store) $limit ";

        $result = $this->db->select($sql);
        $sql = "SELECT COUNT(p.product_id) AS 'total' FROM sdb_ome_branch_product bp
                            JOIN sdb_ome_products p
                                ON bp.product_id=p.product_id
                            LEFT JOIN sdb_purchase_supplier_goods sg
                                ON p.goods_id=sg.goods_id
                            LEFT JOIN sdb_purchase_supplier s
                                ON s.supplier_id=sg.supplier_id
                             WHERE p.visibility='true' AND
                            ((bp.store+bp.arrive_store-bp.store_freeze)<bp.safe_store)
                            $where ";

        $count = $this->db->selectrow($sql);
        $result['count'] = $count['total'];
        return $result;
    }

    function getSafeList($data=null){
        $where = ' ';
        if ($data['branch_id'] && $data['branch_id']!=0){
            $where .= " AND bp.branch_id = '".$data['branch_id']."' ";
        }
        if ($data['supplier_id'] && $data['supplier_id']!=0){
            $where .= " AND sg.supplier_id = '".$data['supplier_id']."' ";
        }
        if ($data['bn']){
            $where .= " AND p.bn LIKE '%".$data['bn']."%' ";
        }
        if ($data['product_ids']){
            $where .= " AND p.product_id IN (".$data['product_ids'].") ";
        }else{
            $where .= " AND (bp.safe_store+bp.store_freeze) > (bp.store+bp.arrive_store) ";
        }

        $sql = "SELECT p.product_id,
        IF(bp.safe_store+bp.store_freeze>0,ABS(bp.safe_store+bp.store_freeze - bp.store - bp.arrive_store),bp.safe_store) as 'num',
        p.price,p.barcode,p.bn,p.name,p.spec_info FROM sdb_ome_products p
                    LEFT JOIN sdb_ome_branch_product bp ON p.product_id=bp.product_id
                    LEFT JOIN sdb_purchase_supplier_goods sg ON p.goods_id=sg.goods_id
                    WHERE 1 $where
                ";
        //var_dump($sql);
        $result = $this->db->select($sql);
        return $result;
    }

    /**
     * 从仓库搜索商品
     *
     * @param string $where
     * @return array()
     */
    function findProductsByBranch($where){
        $sql  = "SELECT p.product_id,p.name,p.bn,p.spec_info FROM sdb_ome_goods g
                                            JOIN sdb_ome_products p
                                                ON g.goods_id=p.goods_id
                                            LEFT JOIN sdb_ome_branch_product bp
                                                ON bp.product_id=p.product_id WHERE $where";
        $data = $this->db->select($sql);
        //$data = $this->assoc_unique($data, 'name');
        return $data;
    }

    /**
     * 从供应商搜索商品
     *
     * @param string $where
     * @return array()
     */
    function findProductsBySupplier($where){
        $sql  = "SELECT p.product_id,p.name FROM sdb_purchase_supplier_goods sg
                                            JOIN sdb_ome_products p
                                                ON sg.goods_id=p.goods_id WHERE $where";
        $data = $this->db->select($sql);

        return $data;
    }

    /**
     * 更新仓库库存表
     *
     * @param int $num
     * @param int $branch_id
     * @param int $product_id
     * @param string $type
     * @return boolean
     */
    function updateBranchProductArriveStore($branch_id, $product_id, $num, $type='+'){
        $obranch_product = &app::get('ome')->model('branch_product');
        $branch_p = $obranch_product->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id));
        if ($branch_p){
            $obranch_product->change_arrive_store($branch_id, $product_id, $num, $type);
        }else {
            $bp = array(
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'store' => 0,
                'store_freeze' => 0,
                'last_modified' => time(),
                'arrive_store' => $num,//当入库时，扣减在途库存，如果此时库存中没有此条货品记录，似乎这里的$num应该为负数
                'safe_store' => 0
            );
            $obranch_product->save($bp);
        }
    }

   /*
     * 删除回收站
     * @package pre_recycle
     */
   function pre_recycle($data=null){

       //入库状态：未入库，采购状态：未退货，结算状态：未结算
       $oPo = &$this->app->model('po');
       $oPayment = $this->app->model('purchase_payments');
       $oStatement = $this->app->model('statement');
       //$opObj = &app::get('ome')->model('operation_log');
       if ($data['po_id'])
       foreach ($data['po_id'] as $key=>$val){
           $po_detail = $oPo->dump($val, 'po_status,eo_status,statement,po_bn');
           $payment = $oPayment->dump(array('po_id'=>$val), 'statement_status');

           $del_notice .= '，采购状态'.$oPo->getPoStatus($po_detail['po_status']);
           $del_notice .= '，入库状态'.$oPo->getEoStatus($po_detail['eo_status']);
           $del_notice .= '，付款单'.$oStatement->getStatementStatus($payment['statement_status']);
           if ($po_detail['po_status']=='1' and ($po_detail['eo_status']=='1' or $po_detail['eo_status']=='4') and $payment['statement_status']=='1'){
             //更新付款单disabled=true
             $oPayment->update(array('disabled'=>'true'), array('po_id'=>$val));
             //日志记录
             //$memo = '将编号为（'.$po_detail['po_bn'].'）的采购单删除到回收站，可到回收站进行恢复！';
             //$opObj->write_log('purchase_delete@purchase', $val, $memo);
             $bnstr .= $po_detail['po_bn']." - ";
           }else{
               $this->recycle_msg = '采购单：'.$po_detail['po_bn'].$del_notice.'无法删除!';
               return false;
           }
       }
       return true;
   }

   /*
     * 彻底删除
     * @package pre_delete
     */
   function pre_delete($data=null){

       $oPayment = $this->app->model('purchase_payments');
       $oPoitems = $this->app->model('po_items');
       $oPo = $this->app->model('po');
       //$opObj = &app::get('ome')->model('operation_log');
       $oRecycle = app::get('desktop')->model('recycle');
       if (!is_array($data)) $data = array($data);
       foreach ($_POST['item_id'] as $key=>$val){
         $recycle_detail = $oRecycle->dump(array('item_id'=>$val), 'item_sdf');
         $po_detail = $recycle_detail['item_sdf'];
         $po_id = $po_detail['po_id'];
         //删除po_items表数据
         $oPoitems->delete(array('po_id'=>$po_id));
         //删除purchase_payments付款单
         $oPayment->delete(array('po_id'=>$po_id));
         //删除回收站记录
         $oRecycle->delete(array('item_id'=>$val));
         //日志记录
         //$memo = '将编号为（'.$po_detail['po_bn'].'）的采购单从回收站彻底删除，无法再进行恢复！';
         //$opObj->write_log('purchase_shiftdelete@purchase', $po_id, $memo);
         $bnstr .= $po_detail['po_bn']." - ";
       }

       echo $bnstr.'采购单彻底删除成功！';
       return true;

   }

   /*
     * 恢复
     * @package pre_restore
     */
   function pre_restore($data=null){

       $item_data = $_POST['item_id'];
       $oRecycle = app::get('desktop')->model('recycle');
       $oPayment = $this->app->model('purchase_payments');
       //$opObj = &app::get('ome')->model('operation_log');
       $oPo = $this->app->model('po');
       if (!is_array($item_data)) $item_data = array($data);
       foreach ($item_data as $key=>$val){
         //恢复po表数据
         $recycle_detail = $oRecycle->dump(array('item_id'=>$val), 'item_sdf');
         $po_detail = $recycle_detail['item_sdf'];
         $oPo->save($po_detail);
         //还原付款单disabled=false
         $oPayment->update(array('disabled'=>'false'), array('po_id'=>$po_detail['po_id']));
         //删除回收站记录
         $oRecycle->delete(array('item_id'=>$val));
         //日志记录
         //$memo = '将编号为（'.$po_detail['po_bn'].'）的采购单从回收站恢复！';
         //$opObj->write_log('purchase_restore@purchase', $po_detail['po_id'], $memo);
         $bnstr .= $po_detail['po_bn']." - ";
       }

       echo $bnstr.'采购单恢复成功！';
       return true;
   }

   /*
     * 采购状态  getPoStatus
     * @param int status
     */
    function getPoStatus($status=null){
        $arr = array (
            1 => '已新建',
            2 => '入库取消',
            3 => '采购退货',
        );
        if ($status) return $arr[$status];
        else return $arr;
    }

   /*
     * 入库状态  getEoStatus
     * @param int status
     */
    function getEoStatus($status=null){
        $arr = array (
            1 => '待入库',
            2 => '部分入库',
            3 => '已入库',
            4 => '未入库',
        );
        if ($status) return $arr[$status];
        else return $arr;
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
                    '*:数量' => 'num',
                    '*:价格' => 'price',
                );
                break;
            case 'export_item':
                $this->oSchema['csv'][$filter] = array(
                    '*:采购单号' => 'po_bn',
                    '*:商品编码' => 'goods_bn',
                    '*:货号' => 'bn',
                    '*:货品名称' => 'name',
                    '*:货品规格' => 'spec_info',
                    '*:条形码' => 'barcode',
                    '*:数量' => 'num',
                    '*:已入库数量'=>'in_num',
                    '*:价格' => 'price',
                );
                break;
            case 'purchase':
                $this->oSchema['csv'][$filter] = array(
                    '*:CSCID' => '',
                    '*:是否追加' => '',
                    '*:供应商' => '',
                    '*:到货仓库' => '',
                    '*:是否赊购' => '',
                    '*:是否赊账/预付款' => '',
                    '*:物流费用' => '',
                    '*:预计到货天数' => '',
                    '*:经办人' => '',
                    '*:备注' => '',
                );
                break;
            case 'export_purchase':
                $this->oSchema['csv'][$filter] = array(
                    '*:采购单号' => 'po_bn',
                    '*:采购单名称'=>'name',
                    '*:供应商' => 'supplier',
                    '*:仓库' => 'branch',
                    '*:采购方式' => 'type',
                    '*:是否赊账/预付款' => 'deposit',
                    '*:物流费用' => 'delivery_cost',
                    '*:预计到货日期' => 'arrive',
                    '*:经办人' => 'operator',
                    '*:备注' => 'memo',
                    '*:金额总计' => 'amount',
                    '*:入库状态' => 'eo_status',
                );
                break;
            //需要补货信息
            case 'need':
                $this->oSchema['csv'][$filter]= array(
                '*:供应商' => 'supplier_name',
                '*:货品名称'=>'name',
                '*:货号' => 'bn',
                '*:单位' => 'unit',
                '*:规格' => 'spec_info',
                '*:库存' => 'store',
                '*:在途库存' => 'arrive_store',
                '*：安全库存' => 'safe_store',
                '*:冻结库存' => 'store_freeze',
                '*：需要补货' => 'need',
                );
            break;
        }
        #根据不同的采购状态列表，增加导出列
        if($this->po_status == 3){
            $title = array(
                        '*终止人'=>'doRefund_op',
                        '*终止时间'=>'doRefund_time'
                     );
        }
        if($title){
            $this->oSchema[$ioType][$filter] = array_merge($this->oSchema[$ioType][$filter],$title);
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }
     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
         #根据不同的采购状态列表，增加导出列
         if($_GET['view']){
             $this->po_status = $_GET['view'];
         }
         #入库状态
         $eo_status = array (0 => 'N/A',1 => '待入库',2 => '部分入库',3 => '已入库',4 => '未入库');
        if( !$data['title']['export_purchase'] ){
            $title = array();
            foreach( $this->io_title('export_purchase') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['export_purchase'] = '"'.implode('","',$title).'"';
        }
        if( !$data['title']['item'] ){
            $title = array();
            foreach( $this->io_title('export_item') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['item'] = '"'.implode('","',$title).'"';
        }
        $limit = 1000;
        //

        if( !$list=$this->getList('po_id',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $aP = $this->dump($aFilter['po_id'],'*');
            $aP['po_bn'] .= "\t";
            $_key = $aP['eo_status'];
            #入库状态名称
            $aP['eo_status'] = $eo_status[$_key];
            $memo = array();
            #采购终止状态时，增加相关列
            if($this->po_status == 3 ){
                $_memo  = unserialize($aP['memo']);
                $doRefund_memo = $_memo['doRefund'];
                if(!empty($doRefund_memo)){
                    $aP['doRefund_op'] = $doRefund_memo['op_name'];
                    $aP['doRefund_time'] = $doRefund_memo['op_time'];
                }
            }
            $aP['memo']=kernel::single('ome_func')->format_memo($aP['memo']);
            if ($aP['memo']){
                foreach ($aP['memo'] as $val){
                    $memo[] = $val['op_content'].' '.$val['op_time']." by ".$val['op_name'];
                }
            }
            $aP['memo'] = implode(',',$memo);
            if( !$aP )continue;
            $pRow = array();
            $p_itemsRow = array();
            $supp = $this->app->model('supplier')->dump($aP['supplier_id']);
            $aP['supplier'] = $supp['name'];
            $bran = app::get('ome')->model('branch')->dump($aP['branch_id']);
            $aP['branch'] = $bran['name'];
            $aP['type'] = $aP['po_type'] =='cash'?'现购':'赊购';
            $aP['arrive'] = date('Y-m-d',$aP['arrive_time']);
            foreach( $this->oSchema['csv']['export_purchase'] as $k => $v ){
                $pRow[$k] = $this->charset->utf2local( utils::apath( $aP,explode('/',$v) ) );
            }
            $data['content']['export_purchase'][] = '"'.implode('","',$pRow).'"';
            $p_itemsObj = &$this->app->model('po_items');
            $items = $p_itemsObj->getList('*', array('po_id'=>$aFilter['po_id']),0,-1);
            foreach ( $items as $key => $item){
                ////增加商品编码
                $goods = $this->db->select('SELECT g.bn,p.name FROM sdb_ome_products as p LEFT JOIN sdb_ome_goods as g ON p.goods_id=g.goods_id WHERE p.product_id='.$item['product_id']);
                $item['po_bn'] = $aP['po_bn'];
                $item['goods_bn'] = $goods[0]['bn'];
                $item['name'] = $goods[0]['name'];
                foreach( $this->oSchema['csv']['export_item'] as $k => $v ){
                    $p_itemsRow[$v] = $this->charset->utf2local( utils::apath( $item,explode('/',$v) ) );
                }
                $data['content']['item'][] = '"'.implode('","',$p_itemsRow).'"';
            }
        }
        $data['name'] = 'CG'.date("Ymd");

        return false;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
        //if( $exportType == 2 ){
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        //}
        echo implode("\n",$output);
    }

    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        set_time_limit(0);
        base_kvstore::instance('purchase_purchase')->fetch('purchase-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('purchase_purchase')->store('purchase-'.$this->ioObj->cacheTime,'');
        
        $pTitle = array_flip( $this->io_title('purchase') );
        $piTitle = array_flip( $this->io_title('item') );
        $pSchema = $this->oSchema['csv']['purchase'];
        $piSchema = $this->oSchema['csv']['item'];
        $oQueue = &app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();
        $supplier = $this->app->model('supplier')->dump(array('name'=>$aP['purchase']['contents'][0][2]));
        $branch = app::get('ome')->model('branch')->dump(array('name'=>$aP['purchase']['contents'][0][3]));
        if ($aP['purchase']['contents'][0][4] != '是'){
            $pSdf['po_type'] = 'cash';
            $pSdf['deposit'] = 0;
        }else {
            $pSdf['po_type'] = 'credit';
            $pSdf['deposit'] = (float)$aP['purchase']['contents'][0][5];
        }
        $days = (int)$aP['purchase']['contents'][0][7];

        $pSdf['delivery_cost']  = (float)$aP['purchase']['contents'][0][6];
        $pSdf['arrive_time']    = time()+$days*24*60*60;
        $pSdf['purchase_time']  = time();
        $pSdf['operator']        = $aP['purchase']['contents'][0][8];
        
        $memo = $aP['purchase']['contents'][0][9];
        if($memo!=''){
            $export_memo = array();
            $export_memo[] = array('op_name'=>kernel::single('desktop_user')->get_name(), 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$memo);
            $memo = serialize($export_memo);
        }
        $pSdf['memo']           = $memo;
        $pSdf['po_bn']          = $aP['purchase']['contents'][0][0];
        $pSdf['supplier_id']    = $supplier['supplier_id'];
        $pSdf['branch_id']      = $branch['branch_id'];

        foreach ($aP['item']['contents'] as $k => $aPi){
            $p = app::get('ome')->model('products')->dump(array('bn'=>$aPi[0]));
            
            $pSdf['po_items'][$k]['product_id'] = $p['product_id'];
            $pSdf['po_items'][$k]['bn'] = $aPi[0];
            $pSdf['po_items'][$k]['name'] = $p['name'];
            $pSdf['po_items'][$k]['spec_info'] = $p['spec_info'];
            $pSdf['po_items'][$k]['barcode'] = $p['barcode'];
            $pSdf['po_items'][$k]['num'] = $aPi[4];
            #判断导入采购单中输入的价格是否大于等于0
            $aPi[5] = trim($aPi[5]);
            if($aPi[5] == '0'){
                $price = 0;
            }else{
                #验证采购价格是否是正数
                $_price = kernel::single('ome_goods_product')->valiPositive($aPi[5]);
                if($_price){
                    $price =  $aPi[5];
                }else{
                    $price = kernel::single('taoguaninventory_inventorylist')->get_price($p['product_id'],$branch['branch_id']);
                    if (!$price) {
                        $price = 0;
                    }
                }
            }            
            $pSdf['po_items'][$k]['price'] = $price;
        }

        $queueData = array(
            'queue_title'=>'采购单导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$pSdf,
                'app' => 'purchase',
                'mdl' => 'po'
            ),
            'worker'=>'purchase_po_to_import.run',
        );
        
        $oQueue->save($queueData);

        return null;
    }

    //导入
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){

        if (empty($row)){

            if ($this -> item_exist == false) {
                $msg['error'] = "采购单中没有货品";
                return false;
            }

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
                base_kvstore::instance('purchase_purchase')->store('purchase-'.$this->ioObj->cacheTime,'');
                return false;
            }
            return true;
        }
        $mark = false;
        $re = base_kvstore::instance('purchase_purchase')->fetch('purchase-'.$this->ioObj->cacheTime,$fileData);
        if( !$re )
            $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            $this -> item_exist = false;
            return $titleRs;
        }else{
            if( $row[0] ){
                if( array_key_exists( '*:货号',$title )  ) {
                    $this -> item_exist = true;
                    $p = app::get('ome')->model('products')->dump(array('bn'=>$row[0]),'product_id');
                    if(!$p){
                        $this->flag = true;
                        $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[0])):array($row[0]);
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
                    if ($row[0] == ''){
                        $msg['error'] = "请填写采购单编号";
                        return false;
                    }

                    if($row[0]!=''){
                        $po_bn = $row[0];
                        if (substr($po_bn,0,1)!='I') {
                            $msg['error'] = "采购单编号请以I开头!";
                            return false;
                        };
                    }
                        
                    $purchase = $this->dump(array('po_bn'=>$row[0]),'po_id,eo_status,po_status,statement');
                    if ( $purchase ){
                        if ($row[1] != '是'){
                            $msg['error'] = "采购单:".$row[0]."已存在";
                            return false;
                        }
                        if ($purchase['eo_status'] != '1' || $purchase['po_status'] != '1'){
                            $msg['error'] = "采购单已入过库，不能再进行追加";
                            return false;
                        }
                        if ($purchase['statement'] != '1'){
                            $msg['error'] = "采购单已结算过，不能再进行追加";
                            return false;
                        }
                    } else {
                        $supplier = $this->app->model('supplier')->dump(array('name'=>$row[2]),'supplier_id');
                        if (!$supplier){
                            $msg['error'] = "没有此:".$row[2]."供应商";
                            return false;
                        }
                        $branch = app::get('ome')->model('branch')->dump(array('name'=>$row[3]),'branch_id');
                        if (!$branch){
                            $msg['error'] = "没有此:".$row[3]."仓库";
                            return false;
                        }
                    }

                    $fileData['purchase']['contents'][] = $row;
                }
                base_kvstore::instance('purchase_purchase')->store('purchase-'.$this->ioObj->cacheTime,$fileData);
            }

        }
        return null;
    }

    /*
     * 采购价格
     */
    function getPurchsePrice($product_id=null, $price_type='asc'){
        //最后采购价格
        $sqlCurr = " SELECT e.`purchase_price` FROM `sdb_purchase_branch_product_batch` e
                           WHERE e.`product_id`='".$product_id."' ORDER BY e.`purchase_time` $price_type ";
        $cur_temp = $this->db->selectRow($sqlCurr);
        $price = $cur_temp['purchase_price'];
        return $price;
    }

    /**
     *
     * 查询供应商的货品采购价
     * @param string $supplier_id
     * @param string $product_id
     * @param string $price_type
     */
    function getPurchsePriceBySupplierId($supplier_id =null, $product_id=null, $price_type='asc'){
        $sqlCurr = " SELECT e.`purchase_price` FROM `sdb_purchase_branch_product_batch` e
                           WHERE e.`supplier_id`='".$supplier_id."' AND e.`product_id`='".$product_id."' ORDER BY e.`purchase_time` $price_type ";
        $cur_temp = $this->db->selectRow($sqlCurr);
        $price = $cur_temp['purchase_price'];
        return $price;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

   //采购日期格式化
   function modifier_purchase_time($row){
       $tmp = date('Y-m-d',$row);
       return $tmp;
    }

   //金额总计格式化
   function modifier_amount($row){
        $tmp = '<span title="金额总计=商品总额+物流费用">'.$row.'</span>';
        return $tmp;
    }

    function searchOptions(){
        return array(
                'name'=>app::get('base')->_('采购单名称'),
                'po_bn'=>app::get('base')->_('采购单编号'),
           //     'barcode'=>app::get('base')->_('条形码'),
           //     'bn'=>app::get('base')->_('货号'),
            );
    }

  function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = "1";
        if(isset($filter['bn'])){
            $itemsObj = &$this->app->model("po_items");
            $rows = $itemsObj->getPoIdByPbn($filter['bn']);
            $poId[] = 0;
            foreach($rows as $row){
                $poId[] = $row['po_id'];
            }
            $where .= ' AND po_id IN ('.implode(',', $poId).')';
            unset($filter['bn']);
        }
        if(isset($filter['barcode'])){
            $itemsObj = &$this->app->model("po_items");
            $rows = $itemsObj->getPoIdByPbarcode($filter['barcode']);
            $poId[] = 0;
            foreach($rows as $row){
                $poId[] = $row['po_id'];
            }
            $where .= '  AND po_id IN ('.implode(',', $poId).')';
            unset($filter['barcode']);
        }
        /*
        由于dbshemca中branch_id searchtype用的是has导致，搜索的时候变成了模糊搜索。
        目前在不熟悉数据库的时候下,将branch_id改为精确搜索。
        */
        if(isset($filter['branch_id']) && is_string($filter['branch_id'])){
            $where .= '  AND branch_id = '.$filter['branch_id'];
            unset($filter['branch_id']);
        }        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
    }

    /**
    * 返回采购单采购总数量和sku种类
    */
    function getPoSkuTotalById($po_id){
        $SQL = 'SELECT sum(num) as total FROM sdb_purchase_po_items WHERE po_id='.$po_id.' ORDER BY item_id DESC ';
        $SQL1 = 'SELECT item_id FROM sdb_purchase_po_items WHERE po_id='.$po_id.' GROUP BY bn ORDER BY item_id DESC';
        $items = $this->db->selectrow($SQL);
        $items1 = $this->db->select($SQL1);
        $po = array();
        $po=array(
            'total'=>$items['total'],
            'sku_number'=>count($items1)
        );
        return $po;
    }


    /**
    * 保存采购单
    * $sdf = array(
          'supplier_id' => '供应商',
          'operator' => '采购员',
          'po_type' => '采购方式',
          'name' => '采购单名称',
          'emergency' => '是否紧急',
          'branch_id' => '仓库ID',
          'arrive_time' => '预计到货天数',
          'deposit' => '预付款原款',
          'deposit_balance' => '预付款',
          'delivery_cost' => '物流费用',
          'memo' => '备注',
          'items' => array(#明细
              'bn' => '货号',
              'name' => '货品名称',
              'nums' => '数量',
              'price' => '单价',
          ),
      );
    */
    public function savePo(&$sdf = array(),$isTransaction = true){
        $rs = array('status'=>'success','msg'=>'');

        $poObj = &app::get('purchase')->model('po');
        $po_itemObj = &$this->app->model('po_items');
        $pObj = &app::get('ome')->model('products');

        $total = 0;
        $po_bn = $poObj->gen_id();
        $data['po_bn'] = $po_bn;
        $data['supplier_id'] = $sdf['supplier_id'];
        $data['operator'] = $sdf['operator'];
        #采购单创建人
        $data['op_name'] = kernel::single('desktop_user')->get_name();
        $data['po_type'] = $sdf['po_type'];
        $data['name'] = $sdf['name'];
        $data['emergency'] = $sdf['emergency'];
        $data['purchase_time'] = time();
        $data['branch_id'] = $sdf['branch_id'];
        $data['arrive_time'] = ($sdf['arrive_time']*24*60*60)+time();
        $data['deposit'] = $sdf['po_type']=='cash'?0:$sdf['deposit'];
        $data['deposit_balance'] = $sdf['po_type']=='cash'?0:$sdf['deposit_balance'];#预付款
        $data['delivery_cost'] = $sdf['delivery_cost'];
        if ($sdf['memo']){
            $op_name = kernel::single('desktop_user')->get_login_name();
            $newmemo = array();
            $newmemo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$sdf['memo']['op_content']);
        }
        $data['memo'] = serialize($newmemo);
        $items = $sdf['items'];
        if($items){
            foreach($items as $item){
                $total += $item['nums']*$item['price'];
            }
        }
        $data['product_cost'] = $total;
        $data['amount'] = $data['po_type'] =='cash' ? ($total + $data['delivery_cost']): $total;

        #数据验证
        if ($data['supplier_id'] == ''){
            $rs = array('status'=>'fail','msg'=>'供应商不存在');
            return $rs;
        }
        if (count($items)<=0 || !$items){
            $rs = array('status'=>'fail','msg'=>'采购单中必须有商品');
            return $rs;
        }
        if ($data['po_type'] == 'credit'){
            if (!is_numeric($data['deposit'])){
                $rs = array('status'=>'fail','msg'=>'预付款必须为数字');
                return $rs;
            }
        }
        if (1 == bccomp($data['deposit'],$total,3)){
            $rs = array('status'=>'fail','msg'=>'预付款金额不得大于商品总额');
            return $rs;
        }
        if ($data['branch_id'] == ''){
            $rs = array('status'=>'fail','msg'=>'仓库不存在');
            return $rs;
        }
        if($sdf['arrive_time'] == ''){
            
        }
        if($isTransaction == true){
            kernel::database()->beginTransaction();
        }
        $rs = $poObj->save($data);
        if($rs){
            foreach($items as $item){
                if($item['nums'] <= 0){
                    if($isTransaction == true){
                        kernel::database()->rollback();
                    }
                    $rs = array('status'=>'fail','msg'=>'采购数量必须为数字且大于0');
                    return $rs;
                }
                
                $p = $pObj->getList('product_id,barcode,bn,name,spec_info',array('bn'=>$item['bn']));
                if(empty($p[0]['product_id'])){
                    if($isTransaction == true){
                        kernel::database()->rollback();
                    }
                    $rs = array('status'=>'fail','msg'=>'商品['.$item['bn'].']不存在');
                    return $rs;
                }
                $row['barcode'] = $p[0]['barcode'];
                $row['po_id'] = $data['po_id'];
                $row['product_id'] = $p[0]['product_id'];
                $row['num'] = $item['nums'];
                $row['in_num'] = 0;
                $row['out_num'] = 0;
                $row['price'] = $item['price'];
                $row['status'] = '1';
                $row['bn'] = $item['bn'];
                $row['name'] = $item['name'];
                $row['spec_info'] = $p[0]['spec_info'];

                $po_itemObj->save($row);
                $row = null;
            }
            if($isTransaction == true){
                kernel::database()->commit();
            }
            $rs = array('status'=>'success','msg'=>'','data'=>$data['po_bn']);
            $data['items'] = $items;
            $sdf = $data;
            return $rs;
        }else{
            $rs = array('status'=>'fail','msg'=>'');
            return $rs;
        }
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
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_purchase') {
            $type .= '_purchaseOrder';
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
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_purchase') {
            $type .= '_purchaseOrder';
        }
        $type .= '_import';
        return $type;
    }
}