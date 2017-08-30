<?php

class ome_mdl_brand extends dbeav_model{
        
    function getBrandTypes($brand_id){
        $brand_type_id = null;
        $_type_id = $this->db->select('select type_id from sdb_ome_type_brand where brand_id='.$brand_id);
        if(!empty($_type_id)){
            foreach($_type_id as $v){
               $brand_type_id[] = $v['type_id'];
            }
        }
        return $brand_type_id;
    }

    function getDefinedType(){
        $goods_type_info =  $this->db->select("select type_id,name from sdb_ome_goods_type");
        return $goods_type_info;
    }

    function getAll(){
        $file=MEDIA_DIR.'/brand_list.data';
        if(($contents=file_get_contents($file))){
            if(($result=json_decode($contents,true))){
                return json_decode($contents,true);
            }else{
                return $this->brand2json(true);
            }
        }else{
            return $this->brand2json(true);
        }
    }

    function brand2json($return=false){
        @set_time_limit(600);
        $file=MEDIA_DIR.'/brand_list.data';
        $contents=$this->db->select('SELECT brand_id,brand_name,brand_url,ordernum,brand_logo FROM sdb_ome_brand WHERE disabled = \'false\' order by ordernum desc');
        if($return){
            file_put_contents($file,json_encode($contents));
            return $contents;
        }else{
            return file_put_contents($file,json_encode($contents));
        }
    }
    
    function searchOptions(){
        return array(
                
            );
    }
    
    function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'brand':
                $this->oSchema['csv'][$filter] = array(
                    '*:品牌名称' => 'brand_name',
                    '*:品牌网址' => 'brand_url',
                    '*:品牌别名' => 'brand_keywords',
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
            foreach( $this->io_title('brand') as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;
        
        if( !$list=$this->getList('*',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $pRow = array();
            $detail['brand_name'] = $aFilter['brand_name'];
            $detail['brand_url'] = $aFilter['brand_url'];
            $detail['brand_keywords'] = $aFilter['brand_keywords'];
            foreach( $this->oSchema['csv']['brand'] as $k => $v ){
                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['contents'][] = '"'.implode('","',$pRow).'"';
        }
        $data['name'] = 'brand'.date("YmdHis");
      
        return false;
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
            $re = base_kvstore::instance('ome_brand')->fetch('brand-'.$this->ioObj->cacheTime,$fileData);
        
            if( !$re ) $fileData = array();
        
            if( $row[0] ){
				 if(isset($this->brand_nums)){
                      $this->brand_nums ++;
                      if($this->brand_nums > 5000){
                          $msg['error'] = "导入的品牌数量量过大，请减少到5000个以下！";
                          return false;
                      }
                 }else{
                     $this->brand_nums = 0;
                 }
                //判断品牌名称是否存在
                $brand = $this->dump(array('brand_name'=>$row[0]));
                if ($brand){
                    $msg['error'] = "数据库中已存在此品牌：".$row[0];
                }
                $fileData['brand']['contents'][] = $row;
                base_kvstore::instance('ome_brand')->store('brand-'.$this->ioObj->cacheTime,$fileData);
            }else{
                $msg['error'] = "品牌名称必须填写";
                return false;
            }
        }
        return null;
    }
    
     function prepared_import_csv_obj($data,$mark,$goodsTmpl,&$msg = ''){
           return null;
    }
    
     function finish_import_csv(){
        base_kvstore::instance('ome_brand')->fetch('brand-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('ome_brand')->store('brand-'.$this->ioObj->cacheTime,'');
        $pTitle = array_flip( $this->io_title('brand') );
        $pSchema = $this->oSchema['csv']['brand'];
        $oQueue = &app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();
        
        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();
        
        foreach ($aP['brand']['contents'] as $k => $aPi){
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
                'queue_title'=>'品牌导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'ome',
                    'mdl' => 'brand'
                ),
                'worker'=>'ome_brand_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        return null;
    }
    
    function prepared_import_csv(){
        $this->brand_same_name = array();
        $this->brand_same_name_db = array();
        $this->ioObj->cacheTime = time();
    }

    /*
     * 删除商品品牌
     */
    public function pre_recycle($data=null){
        $goodsObj = &$this->app->model('goods');
        foreach ($data as $val){
           if($val['brand_id']){
               $goods = $goodsObj->getList('goods_id', array('brand_id'=>$val['brand_id']), 0,-1);
               if($goods){
               	   $this->recycle_msg = '该品牌已经被使用，无法删除！';
                   return false;
               }
           }
        }
        return true;
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
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_brand') {
            $type .= '_goodsBingding_bindingGoods';
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
            $type .= '_goodsConfig_goodsBrand';
        }
        $type .= '_import';
        return $type;
    }
}