<?php

class ome_mdl_branch_product extends dbeav_model{
     var $export_name = '仓库库存';

    /*
     * 更新仓库库存
     */

    function change_store($branch_id, $product_id, $num, $operator='='){
        $now = time();
        $store = "";
        switch($operator){
            case "+":
                $store = "store=IFNULL(store,0)+".$num;
                break;
            case "-":
                $store = " store=IF((CAST(store AS SIGNED)-$num)>0,store-$num,0) ";
                break;
            case "=":
            default:
                $store = "store=".$num;
                break;
        }

        $sql = 'UPDATE sdb_ome_branch_product SET '.$store.',last_modified='.$now.' WHERE product_id='.$product_id.' AND branch_id='.$branch_id;
        if($this->db->exec($sql)){
            return $this->count_store($product_id);
        }else{
            return false;
        }
    }

    /*
     * 统计所有此商品库存
     */
    function count_store($product_id, $branch_id=0){
        $this->app->model('products')->count_store($product_id);
        return true;
    }

    /* 减仓库表库存
     * ss备注：货位库存相关方法，可以删除此方法
     */
    function Cut_store($adata){
        $oProducts = &$this->app->model("products");
        $bppObj = &$this->app->model("branch_product_pos");

        foreach($adata['items'] as $k=>$v){
            $bppObj->change_store($adata['branch_id'],$v['product_id'],$v['pos_id'],$v['num'],'-');
       }
    }

    /*
     * ss备注：货位库存相关方法，可以删除此方法
     */
    function operate_store($adata,$operate){
        $oProducts = &$this->app->model("products");
        $bppObj = &$this->app->model("branch_product_pos");

        if($operate=='add')
        {
            $bppObj->change_store($adata['branch_id'],$adata['product_id'],$adata['pos_id'],$adata['num'],'+');
        }else if($operate=='lower'){
            $bppObj->change_store($adata['branch_id'],$adata['product_id'],$adata['pos_id'],$adata['num'],'-');
        }

    }

    /*
     * ss备注：在途库存相关方法，可以删除此方法
     */
    function change_arrive_store($branch_id, $product_id, $num, $type='+'){
        $now = time();
        $store = "";
        switch($type){
            case "+":
                $store = "arrive_store=IFNULL(arrive_store,0)+".$num;
                break;
            case "-":
                $store = " arrive_store=IF((CAST(arrive_store AS SIGNED)-$num)>0,arrive_store-$num,0) ";
                break;
            case "=":
            default:
                $store = "arrive_store=".$num;
                break;
        }
        $sql = 'UPDATE sdb_ome_branch_product SET '.$store.' WHERE product_id='.$product_id.' AND branch_id='.$branch_id;
        $rs = $this->db->exec($sql);
        return $rs;
    }

    /*
     * ss备注：货位相关方法，可以删除此方法
     */
    function Get_pos_id($branch_id,$store_position){
          $obranch_pos = &$this->app->model('branch_pos');
          $pos = $obranch_pos->dump(array('branch_id'=>$branch_id,'store_position'=>$store_position),'pos_id');
          return $pos['pos_id'];
    }

    /*
     * 修改冻结库存
     */
    function chg_product_store_freeze($branch_id,$product_id,$num,$operator='=',$log_type='delivery'){
        $now = time();
        $store_freeze = "";
        //danny_freeze_stock_log
        $mark_no = uniqid();
        switch($operator){
            case "+":
                $store_freeze = "store_freeze=IFNULL(store_freeze,0)+".$num.",";
                //danny_freeze_stock_log
                $action = '增加';
                break;
            case "-":
                $store_freeze = " store_freeze=IF((CAST(store_freeze AS SIGNED)-$num)>0,store_freeze-$num,0),";
                //danny_freeze_stock_log
                $action = '扣减';
                break;
            case "=":
            default:
                $store_freeze = "store_freeze=".$num.",";
                //danny_freeze_stock_log
                $action = '覆盖';
                break;
        }
        //danny_freeze_stock_log
        $product_info = $this->db->selectrow('select goods_id,bn from sdb_ome_products where product_id ='.$product_id);
        $lastinfo = $this->db->selectrow('select store_freeze from sdb_ome_branch_product where product_id ='.$product_id.' AND branch_id = '.$branch_id);
        $branchinfo = $this->db->selectrow('select name from sdb_ome_branch where branch_id = '.$branch_id);

        $sql = 'UPDATE sdb_ome_branch_product SET '.$store_freeze.'last_modified='.$now.' WHERE product_id='.$product_id.' AND branch_id = '.$branch_id;
       
        $this->db->exec($sql);
        
        //danny_freeze_stock_log
        $currentinfo = $this->db->selectrow('select store_freeze from sdb_ome_branch_product where product_id ='.$product_id.' AND branch_id = '.$branch_id);
        $log = array(
                'log_type'=>$log_type,
                'mark_no'=>$mark_no,
                'oper_time'=>$now,
                'product_id'=>$product_id,
                'goods_id'=>$product_info['goods_id'],
                'bn'=>$product_info['bn'],
                'branch_id'=>$branch_id,
                'branch_name'=>$branchinfo['name'],
                'stock_action_type'=>$action,
                'last_num'=>$lastinfo['store_freeze'],
                'change_num'=>$num,
                'current_num'=>$currentinfo['store_freeze'],
        );

        kernel::single('ome_freeze_stock_log')->changeLog($log);
    }

    /*
     * 增加冻结库存
     */
    function freez($branch_id,$product_id,$nums){
        //暂时没有在branch_product上使用冻结库存
        $this->chg_product_store_freeze($branch_id,$product_id,$nums,"+");
        return true;
    }

    /*
     * 释放冻结库存
     */
    function unfreez($branch_id,$product_id,$nums){
        //暂时没有在branch_product上使用冻结库存
        $this->chg_product_store_freeze($branch_id,$product_id,$nums,"-");
        return true;
    }

 function getStoreByBranch($product_id,$branch_id){
     $row = $this->db->selectRow('select store from sdb_ome_branch_product where product_id='.$product_id.' and branch_id='.$branch_id);

     if($row)
      return $row['store'];
     else
      return false;
    }

    function getStoreListByBranch($branch_id,$pids){
     $rows = $this->db->select('select product_id,store from sdb_ome_branch_product where product_id in('.implode(',', $pids).') and branch_id='.$branch_id);
     if($rows){
      $products = array();
      foreach($rows as $row){
       $products[$row['product_id']] = $row['store'];
      }

      return $products;
     }else{
      return false;
     }
    }
    #获取可用库存
    function getAvailableStore($branch_id,$pids){
        $rows = $this->db->select('select product_id,store,store_freeze from sdb_ome_branch_product where product_id in('.implode(',', $pids).') and branch_id='.$branch_id);
        if($rows){
            $products = array();
            foreach($rows as $row){
                $products[$row['product_id']] = $row['store'] - $row['store_freeze'];
            }
    
            return $products;
        }else{
            return false;
        }
    }    
    
    
    
    function getlists($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
        
        $strWhere = '';
        if($cols){
            $cols = str_replace('Array,','branch_id,product_id,',$cols);
            $cols = trim($cols);
        }
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        $col_tmp = explode(",",$cols);
        foreach($col_tmp as $k=>$v){
            $tmp = explode(" ",$v);
            if(!is_numeric($tmp[0])){
                $col_tmp[$k] = 'bp.'.$v;
            }
        }
     //仓库号
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND bp.branch_id = '.$filter['branch_id'];
            }
        }else{
            if ($filter['branch_ids']) {
                if (is_array($filter['branch_ids'])){
                    $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_ids']).') ';
                }else {
                    $strWhere = ' AND bp.branch_id = '.$filter['branch_ids'];
                }
            }
        }
        //货号
        if(isset($filter['bn']) && $filter['bn']!=''){
            $strWhere.=' AND p.bn like \''.$filter['bn'].'%\'';
        }
        //真实库存
        if(isset($filter['actual_store']) && $filter['actual_store']!=''){
            $strWhere.=' AND bp.store';
            if($filter['_actual_store_search']=='nequal'){
                $strWhere.=' =';
            }else if($filter['_actual_store_search']=='than'){
                $strWhere.=' >';
            }else if($filter['_actual_store_search']=='lthan'){
                $strWhere.=' <';
            }
            $strWhere.=$filter['actual_store'];
        }
        //可用库存
        if(isset($filter['enum_store']) && $filter['enum_store']!=''){
            if($filter['_enum_store_search']=='nequal'){
                $strWhere.=' AND bp.store-bp.store_freeze='.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='than'){
                $strWhere.=' AND (if(bp.store_freeze>bp.store,-1,bp.store - bp.store_freeze))>'.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='lthan'){
                $strWhere.=' AND (if(bp.store_freeze>bp.store,-1,bp.store - bp.store_freeze))<'.$filter['enum_store'];
            }
        }
        if(isset($filter['visibility'])){
            $strWhere .= ' and p.visibility='."'{$filter['visibility']}'";
        }else{
            $strWhere .= ' and p.visibility='."'true'";
        }
        $col_tmp[] = 'p.bn,p.name,p.spec_info,p.unit,p.barcode,p.visibility,p.spec_info,p.goods_id';
        $cols = implode(",",$col_tmp);
        $orderType = $orderby?$orderby:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_product AS bp LEFT join sdb_ome_products as p on bp.product_id=p.product_id WHERE p.bn!=\'\' '.$strWhere;
		if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

		$data = $this->db->selectLimit($sql,$limit,$offset);
        return $data;
    }
    function countlist($filter=null){
        $orderby = FALSE;
        //仓库号
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere = ' AND bp.branch_id = '.$filter['branch_id'];
            }
        }else{
            if ($filter['branch_ids']) {
                if (is_array($filter['branch_ids'])){
                    $strWhere = ' AND bp.branch_id IN ('.implode(',', $filter['branch_ids']).') ';
                }else {
                    $strWhere = ' AND bp.branch_id = '.$filter['branch_ids'];
                }
            }
        }
        //货号
        if(isset($filter['bn']) && $filter['bn']!=''){
            $strWhere.=' AND p.bn like \''.$filter['bn'].'%\'';
        }
        //真实库存
        if(isset($filter['actual_store'])){
            $strWhere.=' AND bp.store';
            if($filter['_actual_store_search']=='nequal'){
                $strWhere.=' =';
            }else if($filter['_actual_store_search']=='than'){
                $strWhere.=' >';
            }else if($filter['_actual_store_search']=='lthan'){
                $strWhere.=' <';
            }
            $strWhere.=$filter['actual_store'];
        }
        if(isset($filter['visibility'])){
            $strWhere .= ' and p.visibility='."'{$filter['visibility']}'";
        }else{
            $strWhere .= ' and p.visibility='."'true'";
        }
        //可用库存
        if(isset($filter['enum_store']) && $filter['enum_store']!=''){
            if($filter['_enum_store_search']=='nequal'){
                $strWhere.=' AND bp.store-bp.store_freeze='.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='than'){
                $strWhere.=' AND (if(bp.store_freeze>bp.store,-1,bp.store - bp.store_freeze))>'.$filter['enum_store'];
            }else if($filter['_enum_store_search']=='lthan'){
                $strWhere.=' AND bp.store_freeze>bp.store';
            }
        }
        $col_tmp[] = 'bp.branch_id,p.bn,p.name,p.spec_info,p.unit,p.barcode,p.visibility,p.spec_info';
        $cols = implode(",",$col_tmp);
        $orderType = $orderby?$orderby:$this->defaultOrder;
        $sql = 'SELECT count(branch_id) as _count FROM sdb_ome_branch_product AS bp LEFT join sdb_ome_products as p on bp.product_id=p.product_id WHERE p.bn!=\'\' '.$strWhere;
        if($orderType){
            $sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        }

        $row = $this->db->selectrow($sql);
        return intval($row['_count']);
    }
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ) {

        $branchObj = $this->app->model('branch');
        if( !$data['title']){
            $title = array();

            foreach($this->io_title('branch_product') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['branch_product'] = '"'.implode('","',$title).'"';
        }
        //$limit =100;

        if( !$list=$this->getlists('*',$filter,0,-1) )return false;
        foreach( $list as $aFilter ){
            $branch = $branchObj->dump($aFilter['branch_id'],'name');
            $pRow = array();
            $detail['store'] = $aFilter['store'];
            $detail['store_freeze'] = $aFilter['store_freeze'];
            $detail['barcode'] = $this->charset->utf2local($aFilter['barcode'])."\t";
            $detail['name'] = $this->charset->utf2local($aFilter['name']);
            $detail['bn'] = $this->charset->utf2local($aFilter['bn'])."\t";
            $detail['spec_info'] = $this->charset->utf2local($aFilter['spec_info']);
            $detail['branch_name'] = $this->charset->utf2local($branch['name']);
            $detail['arrive_store'] = $aFilter['arrive_store'];
            foreach( $this->oSchema['csv']['branch_product'] as $k => $v ){

                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['content']['branch_product'][] = '"'.implode('","',$pRow).'"';
        }

        //$data['export_name'] = '仓库'.date("YmdHis");
        return false;
    }

    public function fcount_csv($filter = NULL)
    {
        return $this->countlist($filter);
    }

    function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'branch_product':
                $this->oSchema['csv'][$filter] = array(
                '*:仓库' => 'branch_name',
                '*:货号' => 'bn',
                '*:条形码' => 'barcode',
                '*:货品名称' => 'name',
                '*:规格' => 'spec_info',
                '*:库存' => 'store',
                '*:冻结库存' => 'store_freeze',
                '*:在途库存'=>'arrive_store'
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){

            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }

        echo implode("\n",$output);
    }
    public function exportName(&$data){
        $branch_id = $_POST['branch_id'];

        $branchObj = &app::get('ome')->model('branch');
        if(isset($branch_id) && trim($branch_id)){
            $branch = $branchObj->getlist('name',array('branch_id'=>$branch_id));
            $export_name = $branch[0]['name'];
        }else{
            $export_name='全部仓库';
        }
        $data['name'] = $export_name.'库存'.date('Ymd');
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
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product') {
            $type .= '_stockManager_stockList';
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
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_product') {
            $type .= '_stockManager_stockList';
        }
        $type .= '_import';
        return $type;
    }
}
?>
