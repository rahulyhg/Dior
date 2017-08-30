<?php
class console_mdl_stockdump extends dbeav_model{

    var $defaultOrder = array('create_time',' DESC');

    

    public function modifier_type($row){
        $info = kernel::single('ome_iostock')->get_iostock_types();
        return $info[$row]['info'];
    }

    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    function io_title( $filter, $ioType='csv' ){

        switch( $ioType ){
             case 'csv':
                 $this->oSchema['csv']['title'] = array(
                                                    '*:调出仓库' => 'from_branch_name',
                                                    '*:调入仓库' => 'to_branch_name',
                                                    '*:备注' => 'memo',
                 );
                $this->oSchema['csv']['items'] = array(
                                                    '*:货号' =>'bn',
                                                    '*:名称' =>'product_name',
                                                    '*:数量' => 'num',
                                                    '*:价格' => 'appro_price',
                 );

             break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

   //出入库单保存
    function to_savestore($adata,$options=array(),&$appro_data=array()){
        $result = array();
        $oStockdump_items = &$this->app->model("stockdump_items");
        $oProducts = &app::get('ome')->model("products");
        $oBranch = &app::get('ome')->model("branch_product");
        $pStockObj = kernel::single('console_stock_products');
        $oProducts = &app::get('ome')->model('products');
        $oBranch_product = &app::get('ome')->model('branch_product');
        $appro_data = array(
            'stockdump_bn'=>$this->get_appro_bn($options['type']),
            'type'=>$options['type'],
            #'in_status' => $options['in_status'],
            #'confirm_type' => $options['confirm_type'] != '' ? $options['confirm_type'] : '1',
            'create_time'=>time(),
            #'otype'=>$options['otype'],
            'operator_name'=>$options['op_name'],
            'from_branch_name'=>$options['from_branch_name'],
            'to_branch_id'=>$options['to_branch_id']=='' ? 0:$options['to_branch_id'],
            'from_branch_id'=>$options['from_branch_id']=='' ? 0:$options['from_branch_id'],
            'to_branch_name'=>$options['to_branch_name'],
            'memo'=>$options['memo']
        );
        
        if(!$this->app->model('stockdump')->save($appro_data)){
            return false;
        }
        
        $break = false;
        $branch_id = $appro_data['from_branch_id'];
        foreach($adata as $k=>$v){
            $product_id = $v['product_id'];
            $num = $v['num'];
            $product = $oProducts->dump($v['product_id'],'bn,name');
            $items_data = array(
                'stockdump_id'=>$appro_data['stockdump_id'],
                'stockdump_bn'=>$appro_data['stockdump_bn'],
                'bn'=>$product['bn'],
                'product_name'=>$product['name'],
                'product_id'=>$v['product_id'],
                'product_size'=>$v['product_id'],
                'num'=>$v['num'],
                'appro_price'=>$v['appro_price'],
                'in_nums' => '0',
            );
            
            $oProducts->chg_product_store_freeze($product_id,$num,'+','stockdump');
            $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$num,'+','stockdump');
           
            
            //保存items
            if(!$oStockdump_items->save($items_data)){
                return false;
            }

            if($break == true) break;
            
        }
        return  $appro_data;
  }
    
    /**
    * 生成调拨单号
    *
    **/
    function get_appro_bn($type){
        $iostcok = kernel::single("ome_iostock");
        return $iostcok->get_iostock_bn($type);
    }

    /**
    * 获取出入库主表数据
    * @access public
    * @param Number $stock_id 出入库单ID
    * @return 主表数据 
    */
    function detail($stock_id){
        if (empty($stock_id)) return NULL;

        $sql = sprintf('SELECT * FROM `sdb_console_stockdump` WHERE appropriation_id=\'%s\'',$stock_id);
        $detail = $this->db->selectrow($sql);
        return $detail;
    }

    /**
    * 获取出入库商品总金额
    * @access public
    * @param Number $stock_id 出入库单ID
    * @return 总金额 
    */
    function total_money($stock_id){
        if (empty($stock_id)) return NULL;

        $sql = sprintf('SELECT sum(appro_price) AS total_amount FROM `sdb_console_stockdump`_items` WHERE appropriation_id=\'%s\'',$stock_id);
        $tmp = $this->db->selectrow($sql);
        $total_amount = $tmp['total_amount'];
        return $total_amount;
    }
	
    /*快速搜素*/
    function searchOptions(){
        $arr = parent::searchOptions();
        return array_merge($arr,array(
                'finder_bn'=>__('货号'),
            ));
    }
	/*
	*搜素条件
	*/
	   function _filter($filter,$tableAlias=null,$baseWhere=null){
        if(isset($filter['finder_bn'])){
            $where .= " AND `stockdump_id` in (SELECT `stockdump_id` FROM `sdb_console_stockdump`_items` WHERE `bn` ='".$filter['finder_bn']."') ";
            unset($filter['finder_bn']);
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    function cancel($stockdump_id){
        
        $data = array(
            'stockdump_id'=> $stockdump_id,
            'self_status' =>'0',
        );
        return $this->save($data);

    }
    
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
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
                if ($this->not_exist_branch_product) {
                    $temp = $this->not_exist_branch_product;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n数据库中不存在的货号与出库仓关系：';
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
                if ($this->not_exist_unable_store) {
                    $temp = $this->not_exist_unable_store;
                    $tmp = array_unique($temp);
                    sort($tmp);
                    $msg['error'] .= '\n库存不足此次调出：';
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
                base_kvstore::instance('console_stockdump')->store('stockdump-'.$this->ioObj->cacheTime,'');
                return false;
            }
            return true;
        }
        $branchObj = &app::get('ome')->model('branch');
        $branch_prObj = &app::get('ome')->model('branch_product');
        $productObj = app::get('ome')->model('products');
        $pStockObj = kernel::single('console_stock_products');
        $mark = false;
        $re = base_kvstore::instance('console_stockdump')->fetch('stockdump-'.$this->ioObj->cacheTime,$fileData);
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
                    $stockdump = $fileData['stockdump'];
                    $from_branch_id = $stockdump['from_branch_id'];
                    $p =$productObj->dump(array('bn'=>$row[0]),'product_id');
                    $product_id = $p['product_id'];
                    if(!$p){
                        $this->flag = true;
                        $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[0])):array($row[0]);
                    }
                    
                    $branch_product = $branch_prObj->dump(array('branch_id'=>$from_branch_id,'product_id'=>$p['product_id']));
                    if (!$branch_product) {
                        $this->flag = true;
                        $this->not_exist_branch_product= isset($this->not_exist_branch_product)?array_merge($this->not_exist_branch_product,array($row[0])):array($row[0]);
                    }
                    $usable_store = $pStockObj->get_branch_usable_store($from_branch_id,$product_id);
                    $num = $row[2];
                    if ($usable_store<$num ) {
                        $this->flag = true;
                        $this->not_exist_unable_store= isset($this->not_exist_unable_store)?array_merge($this->not_exist_unable_store,array($row[0])):array($row[0]);
                    }
                    if ($fileData['item']){
                        foreach ($fileData['item'] as $v){

                            if (trim($row[0]) == trim($v['bn'])){
                                $this->flag = true;
                                $this->same_product_bn = isset($this->same_product_bn)?array_merge($this->same_product_bn,array($row[0])):array($row[0]);
                            }
                        }
                    }
                    $items = array(
                        'num'=>$num,
                        'product_id'=>$product_id,
                        'bn'=>$row[0],
                        'product_name'=>$row[1],
                        'appro_price'=>$row[3],
                    );
                    $fileData['item'][] = $items;
                }else {
                    
                    $from_branch_name = $row[0];
                    $to_branch_name = $row[1];
                    $from_branch = $branchObj->dump(array('name'=>trim($from_branch_name)),'branch_id');
                    if (!$from_branch) {
                        $msg['error'] .= '\n调出仓不存在';
                        return false;
                    }
                    
                    $to_branch = $branchObj->dump(array('name'=>trim($to_branch_name)),'branch_id');
                    if (!$to_branch) {
                        $msg['error'] .= '\n调入仓不存在';
                        return false;
                    }
                    $main = array(
                        'from_branch_id'=>  $from_branch['branch_id'],
                        'to_branch_id'=>$to_branch['branch_id'],
                    );
                    unset($from_branch,$to_branch);
                    $fileData['stockdump']= $main;
                }
                
                base_kvstore::instance('console_stockdump')->store('stockdump-'.$this->ioObj->cacheTime,$fileData);
            }

        }
        return null;
    }

    function finish_import_csv(){
        set_time_limit(0);
        base_kvstore::instance('console_stockdump')->fetch('stockdump-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('console_stockdump')->store('stockdump-'.$this->ioObj->cacheTime,'');
        $oQueue = &app::get('base')->model('queue');
        $op_name = kernel::single('desktop_user')->get_name();
        $op_name = $op_name ? $op_name : 'system';
        $sto_sdf = array(
            'op_name' => $op_name,
            'from_branch_id' => $data['stockdump']['from_branch_id'],
            'to_branch_id' => $data['stockdump']['to_branch_id'],
            'memo' => $memo,
        );
        $items = array();
        foreach ($data['item'] as $item ) {
            $items[] = array(
                'num'=>$item['num'],
                'product_id'=>$item['product_id'],
                'appro_price'=>$item['appro_price'],
            );
        }
        $sto_sdf['items'] = $items;
        $queueData = array(
            'queue_title'=>'转储单导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$sto_sdf,
                'app' => 'stockdump',
                'mdl' => 'console'
            ),
            'worker'=>'console_stockdump_to_import.run',
        );
        
        $oQueue->save($queueData);

        return null;
    }
}