<?php

class ome_mdl_branch_pos extends dbeav_model{

    function finder_list($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
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
            $col_tmp[] = 'p.*';
            $cols = implode(",",$col_tmp);

            $orderType = $orderby?$orderby:$this->defaultOrder;

            $sql = 'SELECT '.$cols.' FROM sdb_ome_branch_pos AS bp
                    LEFT JOIN sdb_ome_branch_product_pos AS bpp ON(bp.pos_id=bpp.pos_id)
                    LEFT JOIN sdb_ome_products AS p ON(bpp.product_id=p.product_id)
                    WHERE '.$this->_filter($filter,'bp');

            if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);
            $data = $this->db->selectLimit($sql,$limit,$offset);
            return $data;
    }

    function finder_count($filter=null){
        if ($_GET['act'] != 'view'){
            return parent::count($filter);
        }else {
            $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_branch_pos AS bp
                    LEFT JOIN sdb_ome_branch_product_pos AS bpp ON(bp.pos_id=bpp.pos_id)
                    LEFT JOIN sdb_ome_products AS p ON(bpp.product_id=p.product_id)
                    WHERE '.$this->_filter($filter,'bp');
            $row = $this->db->select($sql);
            return intval($row[0]['_count']);
        }
    }

    function searchOptions(){
        return array(
                'store_position'=>app::get('base')->_('货位'),
            );
    }

    /*
     * 导出模板标题
     */
    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

     function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'branch_pos':
                $this->oSchema['csv'][$filter] = array(
                    '*:货位名称' => 'pos_name',
                    '*:所属仓库' => 'branch_name',
                );
                break;
            case 'export_branch_pos':
                $this->oSchema['csv'][$filter] = array(
                    '*:货位名称' => 'pos_name',
                    '*:货品名称' => 'product_name',
                    '*:货号' => 'product_bn',
                    '*:所属仓库' => 'branch_name',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
     }

     /*
      * 导出货位记录
      */
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        if( !$data['title']['branch_pos'] ){
            $title = array();
            foreach( $this->io_title('branch_pos') as $k => $v ){
                $title[] = $v;
            }
            $data['title']['branch_pos'] = '"'.implode('","',$title).'"';
        }
//        $limit = 100;

        $sql = 'SELECT bp.store_position,b.name branch_name
                FROM sdb_ome_branch_pos AS bp
                LEFT JOIN sdb_ome_branch b ON(bp.branch_id=b.branch_id)
                WHERE '.$this->_filter($filter,'bp');
        $sql .= ' GROUP BY bp.pos_id ';
        $sql .= ' ORDER BY bp.pos_id desc ';
        $list = $this->db->select($sql);
        if (!$list) return false;
        foreach( $list as $val ){
            $pRow = array();
            $detail['pos_name'] = $val['store_position'];
            $detail['branch_name'] = $val['branch_name'];
            foreach( $this->oSchema['csv']['branch_pos'] as $k => $v ){
                $pRow[$k] =  utils::apath( $detail,explode('/',$v)  );
            }
            $data['content']['branch_pos'][] = '"'.implode('","',$pRow).'"';
        }
        $data['name'] = '货位表'.date("Ymd",time());

        return false;
    }
    function export_csv($data,$exportType = 1 ){
        $output = array();
        //if( $exportType == 2 ){
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        //}
        return implode("\n",$output);
    }

    /*
     * CSV导入
     */
    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        base_kvstore::instance('ome_branch_pos')->fetch('branch_pos-'.$this->ioObj->cacheTime,$data);
        base_kvstore::instance('ome_branch_pos')->store('branch_pos-'.$this->ioObj->cacheTime,'');
        $pTitle = array_flip( $this->io_title('branch_pos') );
        $pSchema = $this->oSchema['csv']['export_branch_pos'];
        $oQueue = &app::get('base')->model('queue');
        $aP = $data;
        $pSdf = array();

        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();

        foreach ($aP['branch_pos']['contents'] as $k => $aPi){
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
                'queue_title'=>'货位导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'ome',
                    'mdl' => 'branch_pos'
                ),
                'status' => 'hibernate',
                'worker'=>'ome_branch_pos_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    //CSV导入业务处理
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        if (empty($row)){
            if ($this->branch_error){
                $temp = $this->branch_error;
                $temp = array_unique($temp);
                sort($temp);
                $msg['error'] .= '\n系统中不存在的仓库：';
                $msg['error'] .= implode(',', $temp);
                unset($temp);
                unset($this->branch_error);
                base_kvstore::instance('ome_branch_pos')->store('branch_pos-'.$this->ioObj->cacheTime,'');
                return false;
            }
            if ($this->branch_pos_error){
                $temp = $this->branch_pos_error;
                $temp = array_unique($temp);
                sort($temp);
                $msg['error'] .= '\n系统已存在如下货位：';
                $msg['error'] .= implode(',', $temp);
                unset($temp);
                unset($this->branch_pos_error);
                base_kvstore::instance('ome_branch_pos')->store('branch_pos-'.$this->ioObj->cacheTime,'');
                return false;
            }
            return true;
        }
        $mark = false;
        $re = base_kvstore::instance('ome_branch_pos')->fetch('branch_pos-'.$this->ioObj->cacheTime,$fileData);

        if( !$re ) $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);
            $mark = 'title';

            return $titleRs;
        }else{

            if ($row[1] and $row[0]){
                //判断仓库是否存在
                $branch = app::get('ome')->model('branch')->dump(array('name'=>$row[1]),'branch_id');
                $branch_pos = app::get('ome')->model('branch_pos')->dump(array('store_position'=>$row[0],'branch_id'=>$branch['branch_id']),'pos_id');
                if(!$branch){
                    $this->branch_error = isset($this->branch_error)?array_merge($this->branch_error,array($row[1])):array($row[1]);
                }
                if($branch_pos){
                    $this->branch_pos_error = isset($this->branch_pos_error)?array_merge($this->branch_pos_error,array($row[0])):array($row[0]);
                }
            }
            $fileData['branch_pos']['contents'][] = $row;
            base_kvstore::instance('ome_branch_pos')->store('branch_pos-'.$this->ioObj->cacheTime,$fileData);
        }
        return null;
    }

    /*
     * 删除货位
     */
    function pre_recycle(&$data){
        $Obranch_product = &$this->app->model('branch_product_pos');
        $Obranch_pos = &$this->app->model('branch_pos');
        $is_super = kernel::single('desktop_user')->is_super();
        if(!$is_super){
            $oBranch = &app::get('ome')->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
        }

        if($branch_ids){
            foreach ($data as $key => $val){
                if(!in_array($val['branch_id'], $branch_ids)){
                    unset($data[$key]);
                }else{
                    $poslist[] = $val['pos_id'];
                }
            }
        }

        if($poslist){
            foreach ($poslist as $key=>$val){
                $pos = $Obranch_product->dump(array('pos_id'=>$val),'*');
                $branch_pos = $Obranch_pos->dump(array('pos_id'=>$val),'store_position');
                if(!empty($pos)){
                    $this->recycle_msg = '货位:'.$branch_pos['store_position'].'已与商品建立关系，无法删除!';
                    return false;
                }
                $deled .= $branch_pos['store_position']." - ";
            }
        }
        $this->recycle_msg = '货位:'. $deled. '已成功删除！';
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
        $type = 'warehouse';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_pos') {
            $type .= '_dailyManager_posManager';
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
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_branch_pos') {
            $type .= '_dailyManager_posManager';
        }
        $type .= '_import';
        return $type;
    }
}