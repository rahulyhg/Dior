<?php
class purchase_mdl_purchase_need extends purchase_mdl_po{
    
    public function table_name($real=false){
        $table_name = 'po';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        } 
    
    }
    function utf8togbk($s){
        return iconv("UTF-8", "GBK//TRANSLIT", $s);
    }
    function fgetlist_csv(&$data,$filter,$offset,$exportType = 1){
        if( !$data['title']['need'] ){
            $title = array();
            foreach( $this->io_title('need') as $k => $v ){
                $title[] = $this->utf8togbk($v);
            }
            $data['title']['need'] = '"'.implode('","',$title).'"';
        }
        $filter = utils::addslashes_array($filter);
        $page = $page ? $page : 1;
        //获取所有数据
        $pagelimit = 16777215;
        $oPo = app::get('purchase')->model('po');
        $_safeData = $oPo->getSafeStock($filter, $pagelimit*($page-1), $pagelimit);
        if(!empty( $_safeData)){
            unset( $_safeData['count']);
            $_data = array();
            foreach($_safeData as $v){
                unset($v['product_id']);
                unset($v['supplier_id']);
                //计算需要补货商品的数量

                $v['need'] = $v['safe_store'] + $v['store_freeze'] - $v['store'] - $v['arrive_store'];

                $_data[] = $v;
            }
            foreach($_data as $key=>$value){
                foreach($this->oSchema['csv']['need'] as $k => $v){
                    $pRow[$k] = $this->utf8togbk( utils::apath( $value,explode('/',$v) ) );
                }
                $data['content']['need'][] = '"'.implode('","', $pRow).'"';
            }
            $data['name'] = 'need'.date("Ymd");
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
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_po') {
            $type .= '_needRemind';
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
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_po') {
            $type .= '_needRemind';
        }
        $type .= '_import';
        return $type;
    }
}