<?php
class wms_mdl_products extends ome_mdl_products{
    public function table_name($real = false){
        if($real){
           $table_name = 'sdb_ome_products';
        }else{
           $table_name = 'products';
        }
        return $table_name;
    }

    public function get_schema(){
        $schema = app::get('ome')->model('products')->get_schema();
        
        return $schema;
        
    }
    /**
    * 列表
    */
    function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
        $strWhere = array();
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere[] = ' bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere[] = ' bp.branch_id = '.$filter['branch_id'];
            }
        }
        $orderType = $orderby?$orderby:$this->defaultOrder;

 
        $sql = 'SELECT p.bn,p.name,p.spec_info,p.barcode,p.visibility,p.spec_info,sum(bp.store) as store,p.product_id FROM   sdb_ome_branch_product AS bp LEFT JOIN sdb_ome_products as p ON bp.product_id=p.product_id WHERE  '.implode(' AND ',$strWhere).$this->_filter($filter,'p');
        $sql.=" GROUP BY bp.product_id";

        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

        $data = $this->db->selectLimit($sql,$limit,$offset);
        return $data;
    }

    /**
    * 统计
    */
    function countlist($filter=null){
        $orderby = FALSE;
        $strWhere = array();
        if(isset($filter['branch_id']) && $filter['branch_id']){
            if (is_array($filter['branch_id'])){
                $strWhere[] = ' bp.branch_id IN ('.implode(',', $filter['branch_id']).') ';
            }else {
                $strWhere[] = ' bp.branch_id = '.$filter['branch_id'];
            }
        }
        
        $orderType = $orderby?$orderby:$this->defaultOrder;
        $sql = 'SELECT count(bp.product_id) FROM   sdb_ome_branch_product AS bp LEFT JOIN sdb_ome_products as p ON bp.product_id=p.product_id WHERE  '.implode(' AND ',$strWhere).$this->_filter($filter,'p');
        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
        $sql.=" GROUP BY bp.product_id";

        $row = $this->db->select($sql);
        
        return intval(count($row));
    }

   

    /**
    * 库存导出
    */
    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ) {
        if( !$data['title']){
            $title = array();

            foreach($this->io_title('products') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['products'] = '"'.implode('","',$title).'"';
        }
        if( !$list=$this->getlist('*',$filter,0,-1) )return false;
        foreach( $list as $aFilter ){
            $pRow = array();
            
            $detail['bn'] ="\t".$this->charset->utf2local($aFilter['bn']);
            $detail['barcode'] ="\t".$this->charset->utf2local($aFilter['barcode']);
            $detail['name'] = $this->charset->utf2local($aFilter['name']);
            $detail['spec_info'] = $this->charset->utf2local($aFilter['spec_info']);
            $detail['store'] = $aFilter['store'];
            #$detail['store_freeze'] = $aFilter['store_freeze'];
            #$detail['arrive_store'] = $aFilter['arrive_store'];
            foreach( $this->oSchema['csv']['products'] as $k => $v ){

                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['contents']['products'][] = implode(',',$pRow);
        }

   
        return false;
    }

    function export_csv($data,$exportType = 1 ){

        $output = array();
        $output[] = $data['title']['products']."\n".implode("\n",(array)$data['contents']['products']);

        echo implode("\n",$output);
    }

    function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'products':
                $this->oSchema['csv'][$filter] = array(
               
                '*:货号' => 'bn',
                '*:条形码' => 'barcode',
                '*:货品名称' => 'name',
                '*:规格' => 'spec_info',
                '*:库存' => 'store',
                #'*:冻结库存' => 'store_freeze',
                #'*:在途库存'=>'arrive_store'
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }
   
    function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        if (isset($filter['visibility']) && $filter['visibility']=='0') {
            unset($filter['visibility']);
        }
        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }
   public function getProuductInfoById($product_id = false){
       $sql = 'select 
                   products.product_id,goods.unit
               from sdb_ome_goods goods
               left join sdb_ome_products products  on goods.goods_id=products.goods_id 
               where products.product_id='.$product_id;
       $data = $this->db->select($sql);
       return $data[0];
   }



}
?>