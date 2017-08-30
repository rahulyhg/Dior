<?php

class ome_mdl_goods_type extends dbeav_model{
    var $has_many = array(
        'brand' => 'type_brand:replace',
        'spec' => 'goods_type_spec:replace'
    );

    function checkDefined(){
        return $this->count(array('is_def'=>'false'));
    }

    function getTypeList(){
        return $this->getList('type_id,name');
    }

    function getDefault(){
        return $this->getList('*',array('is_def'=>'true'));
    }

    function getSpec($id,$fm=0){
        $sql="select spec_id,spec_style from sdb_ome_goods_type_spec where type_id=".intval($id);
        $row = $this->db->select($sql);

        if ($row){
            foreach($row as $key => $val){
                if ($fm){
                    if($val['spec_style']<>'disabled'){
                        $attachment=array(
                            "spec_style"=>$val['spec_style']
                        );
                        $tmpRow[$val['spec_id']]=$this->getSpecName($val['spec_id'],$attachment);
                    }
                }
                else{
                    $attachment=array(
                        "spec_style"=>$val['spec_style']
                    );
                    $tmpRow[$val['spec_id']]=$this->getSpecName($val['spec_id'],$attachment);
                }
            }

            return $tmpRow;
        }
        else
            return false;
    }
    function getSpecName($spec_id,$args){
        $sql="select spec_name,spec_type from sdb_ome_specification where spec_id=".intval($spec_id);
        $snRow=$this->db->selectrow($sql);
        $tmpRow['name']=$snRow['spec_name'];
        $tmpRow['spec_type'] = $snRow['spec_type'];
        $tmpRow['spec_memo'] = $snRow['spec_memo'];
        if (is_array($args)){
            foreach($args as $k => $v){
                $tmpRow[$k] = $v;
            }
        }
        $row=$this->getSpecValue($spec_id);
        $tmpRow['spec_value']=$row;
        $tmpRow['type'] = 'spec';
        return $tmpRow;
    }

    function getSpecValue($spec_id){
        $sql="select spec_value,spec_value_id,spec_image from sdb_ome_spec_values where spec_id=".intval($spec_id)." order by p_order,spec_value_id";
        $svRow=$this->db->select($sql);
        if ($svRow){
            foreach($svRow as $key => $val){
                $tmpRow[$val['spec_value_id']]=array(
                        "spec_value"=>$val['spec_value'],
                        "spec_image"=>$val['spec_image']
                );
            }
        }
        return $tmpRow;
    }

    function searchOptions(){
        return array(
                
            );
    }

    /*
     * 删除商品类型
     */
    public function pre_recycle($data=null){
        $goodsObj = &$this->app->model('goods');
        foreach ($data as $val){
           if($val['type_id']){
               $goods = $goodsObj->getList('goods_id', array('type_id'=>$val['type_id']), 0,-1);
               if($goods){
                   return false;
               }
           }
        }
        return true;
    }

    function io_title( $filter, $ioType='csv' ){

        switch( $filter ){
            case 'goods_type':
                $this->oSchema['csv'][$filter] = array(
                '*:名称' => 'name',
                '*:别名' => 'alias',
                );
                break;
                case 'import':
                $this->oSchema['csv'][$filter] = array(
                    '*:名称' => 'name',
                    '*:别名' => 'alias',
                    
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }


     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ) {

        
        if( !$data['title']){
            $title = array();

            foreach($this->io_title('goods_type') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['goods_type'] = '"'.implode('","',$title).'"';
        }


        if( !$list=$this->getlist('name,alias',$filter,0,-1) )return false;
        foreach( $list as $aFilter ){
            $pRow = array();
            $detail = array();
            $detail['name'] = $this->charset->utf2local($aFilter['name']);
            $detail['alias'] = $this->charset->utf2local($aFilter['alias']);
            foreach( $this->oSchema['csv']['goods_type'] as $k => $v ){

                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['contents']['goods_type'][] = '"'.implode('","',$pRow).'"';
        }


        return false;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){

            $output[] = $val."\n".implode("\n",(array)$data['contents'][$k]);
        }

        echo implode("\n",$output);
    }

   
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        
        if (empty($row)){
            return true;
        }
        $mark = false;

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';
            
            return $titleRs;
        }else{
            $re = base_kvstore::instance('ome_goods_type')->fetch('goods_type-'.$this->ioObj->cacheTime,$fileData);
        
            if( !$re ) $fileData = array();
        
            if( $row[0] ){
				            
                //判断名称是否存在
                $goods_type = $this->dump(array('name'=>$row[0]));
                if ($goods_type){
                    $msg['error'] = "数据库中已存在此类型：".$row[0];
                }
                $fileData['goods_type']['contents'][] = $row;
                base_kvstore::instance('ome_goods_type')->store('goods_type-'.$this->ioObj->cacheTime,$fileData);
            }else{
                $msg['error'] = "类型名称必须填写";
                return false;
            }
        }
        return null;
    }
    
     function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = ''){
           return null;
    }

    function prepared_import_csv(){
       
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        base_kvstore::instance('ome_goods_type')->fetch('goods_type-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('ome_goods_type')->store('goods_type-'.$this->ioObj->cacheTime,'');
        $aP = $data;
        foreach ($aP['goods_type']['contents'] as $k => $aPi){
            $type_name = $aPi[0];
            $alias = $aPi[1];
            $SQL = "INSERT INTO sdb_ome_goods_type(name,`alias`) VALUES('".$type_name."','".$alias."')";
          
            $this->db->exec($SQL);
        }
        
        return null;
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
        $type = 'goods';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_goods_type') {
            $type .= '_goodsConfig_goodsType';
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
        $type = 'goods';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_goods_type') {
            $type .= '_goodsConfig_goodsType';
        }
        $type .= '_import';
        return $type;
    }
}
