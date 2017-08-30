<?php

class ome_mdl_specification extends dbeav_model{
    var $has_many = array(
        'spec_value' => 'spec_values:contrast'
    );
    
    function getSpecIdByAll($spec){
        $sql = 'SELECT s.spec_id from sdb_ome_specification s '
            .'left join sdb_ome_spec_values v on s.spec_id = v.spec_id '
            .'where s.spec_name = "'.$spec['spec_name'].'" and v.spec_value in ("'.implode('","',$spec['option']).'") '
            .' group by v.spec_id having count(*) = '.count($spec['option']);
        return $this->db->select($sql);
    }

    function getSpecValuesByAll($spec){
        $rs = array();
        $i = 0;
        $oSpecValue = &$this->app->model('spec_values');
        foreach( $spec['option'] as $specValue ){
            $rs[$specValue] = $oSpecValue->dump(array('spec_value'=>$specValue,'spec_id'=>$spec['spec_id']),'spec_value_id');
            $rs[$specValue]['spec_value'] = $specValue;
            $rs[$specValue]['private_spec_value_id'] = time().(++$i);
            $rs[$specValue]['spec_image'] = '';
            $rs[$specValue]['spec_goods_images'] = '';
        }
        return $rs;
    }

    function searchOptions(){
        return array(

            );
    }

    /*
     * 删除商品规格
     */
    public function pre_recycle($data=null){
        $typeSpecObj = &$this->app->model('goods_type_spec');
        foreach ($data as $val){
           if($val['spec_id']){
               $spec = $typeSpecObj->getList('type_id', array('spec_id'=>$val['spec_id']), 0,-1);
               if($spec){
                   return false;
               }
           }
        }
        return true;
    }

    function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'specification':
                $this->oSchema['csv'][$filter] = array(
                    '*:规格名称' => 'spec_name',
                    '*:规格别名' => 'alias',
                    '*:规格值' => 'spec_vals',
                    '*:规格备注' => 'spec_memo',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
		 @ini_set('memory_limit','64M');
        if( !$data['title']){
            $title = array();
            foreach( $this->io_title('specification') as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;

        $oSpecValues = &$this->app->model('spec_values');

        if( !$list=$this->getList('*',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $pRow = $detail = array();
            $spec_values = $oSpecValues->getlist('*',array("spec_id"=>$aFilter['spec_id']));
            $aSpecVals = array();
            foreach($spec_values as $spec_val){
                $aSpecVals[] = $spec_val['spec_value'];
            }
            $detail['spec_name'] = $aFilter['spec_name'];
            $detail['alias'] = $aFilter['alias'];
            $detail['spec_vals'] = implode('|',$aSpecVals);
            $detail['spec_memo'] = $aFilter['spec_memo'];
            foreach( $this->oSchema['csv']['specification'] as $k => $v ){
                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['contents'][] = '"'.implode('","',$pRow).'"';
        }
        $data['name'] = 'specification'.date("YmdHis");

        return true;
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
            $re = base_kvstore::instance('ome_specification')->fetch('specification-'.$this->ioObj->cacheTime,$fileData);

            if( !$re ) $fileData = array();

            if( $row[0] ){
				if(isset($this->specification_nums)){
                      $this->specification_nums ++;
                      if($this->specification_nums > 5000){
                          $msg['error'] = "导入的品牌数量量过大，请减少到5000个以下！";
                          return false;
                      }
                 }else{
                     $this->specification_nums = 0;
                 }
                //判断编码与供应商简称是否存在
                $specification = $this->dump(array('spec_name'=>$row[0]));
                if ($specification){
						$msg['error'] = "数据库中已存在此规格：".$row[0];
				}

                $fileData['specification']['contents'][] = $row;
                base_kvstore::instance('ome_specification')->store('specification-'.$this->ioObj->cacheTime,$fileData);
            }else{
                $msg['error'] = "规格名称必须填写";
                return null;
            }
        }
        return null;
    }

     function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = ''){
           return null;
    }

     function finish_import_csv(){
        base_kvstore::instance('ome_specification')->fetch('specification-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('ome_specification')->store('specification-'.$this->ioObj->cacheTime,'');
        $pTitle = array_flip( $this->io_title('specification') );
        $pSchema = $this->oSchema['csv']['specification'];
        $oQueue = &app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();

        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();

        foreach ($aP['specification']['contents'] as $k => $aPi){
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $pSdf[$page][] = $aPi;
        }

        foreach($pSdf as $v){
            $queueData = array(
                'queue_title'=>'商品规格导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'ome',
                    'mdl' => 'specification'
                ),
                'worker'=>'ome_specification_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        return null;
    }

    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function save(&$data,$mustUpdate = null){
        if( $data['spec_value'] ){
            $i = 1;
            foreach( $data['spec_value'] as $k => $v ){
                $data['spec_value'][$k]['p_order'] = $i++;
            }
        }

        return parent::save($data,$mustUpdate);
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
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_specification') {
            $type .= '_goodsConfig_goodsSpec';
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
        if ($logParams['app'] == 'omecsv' && $logParams['ctl'] == 'admin_to_import') {
            $type .= '_goodsConfig_goodsSpec';
        }
        $type .= '_import';
        return $type;
    }
}
