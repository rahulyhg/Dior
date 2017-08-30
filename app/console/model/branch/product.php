<?php
class console_mdl_branch_product extends dbeav_model{
     var $export_name = '仓库库存';

     public function table_name($real = false){
        if($real){
           $table_name = 'sdb_ome_branch_product';
        }else{
           $table_name = 'branch_product';
        }
        return $table_name;
    }

    public function get_schema(){
        $schema = app::get('ome')->model('branch_product')->get_schema();
        return $schema;
    }

    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ) {

        $branchObj = &app::get('ome')->model('branch');
        if( !$data['title']){
            $title = array();

            foreach($this->io_title('branch_product') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['content']['main'][] = '"'.implode('","',$title).'"';
        }
        //$limit =100;

        if( !$list=$this->getlists('*',$filter,0,-1) )return false;
        foreach( $list as $aFilter ){
            $branch = $branchObj->dump($aFilter['branch_id'],'name');
            $pRow = array();
            $detail['store'] = $aFilter['store'];
            $detail['store_freeze'] = $aFilter['store_freeze'];
            $detail['barcode'] = "\t".$this->charset->utf2local($aFilter['barcode']);
            $detail['name'] = $this->charset->utf2local($aFilter['name']);
            $detail['bn'] = "\t".$this->charset->utf2local($aFilter['bn']);
            $detail['spec_info'] = $this->charset->utf2local($aFilter['spec_info']);
            $detail['branch_name'] = $this->charset->utf2local($branch['name']);
            $detail['arrive_store'] = $aFilter['arrive_store'];
            foreach( $this->oSchema['csv']['branch_product'] as $k => $v ){

                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['content']['main'][] = '"'.implode('","',$pRow).'"';
        }
        $data['records'] = count($data['content']['main'])-1;

        return true;
    }

    public function fcount_csv($filter = NULL)
    {
        return 600;
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

//     public function fcount_csv($filter = NULL)
//    {
//        return $this->countlist($filter);
//    }

    
    /**
     * 列表统计
     * @param   array $filter
     * @return  int
     * @access  public
     * @author cyyr24@sina.cn
     */
    function countlist($filter=null)
    {
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
        }
        $col_tmp[] = 'p.bn,p.name,p.spec_info,p.barcode,p.visibility,p.spec_info';
        $cols = implode(",",$col_tmp);
        $orderType = $orderby?$orderby:$this->defaultOrder;
        $sql = 'SELECT count(branch_id) as _count FROM sdb_ome_branch_product AS bp LEFT join sdb_ome_products as p on bp.product_id=p.product_id WHERE p.bn!=\'\' '.$strWhere;

        $row = $this->db->selectrow($sql);
        return intval($row['_count']);
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
        }
        $col_tmp[] = 'p.bn,p.name,p.spec_info,p.barcode,p.visibility,p.spec_info';
        $cols = implode(",",$col_tmp);
        $orderType = $orderby?$orderby:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_product AS bp LEFT join sdb_ome_products as p on bp.product_id=p.product_id WHERE p.bn!=\'\' '.$strWhere;
        
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        $data = $this->db->selectLimit($sql,$limit,$offset);
        return $data;
    }
}
?>
