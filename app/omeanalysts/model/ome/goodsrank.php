<?php
class omeanalysts_mdl_ome_goodsrank extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '商品销售排行';

    public function searchOptions(){
        $columns = array();
        foreach($this->_columns() as $k=>$v){
            if(isset($v['searchtype']) && $v['searchtype']){
                $columns[$k] = $v['label'];
            }
        }


        return $columns;
    }

    public function count($filter=null){

        $sql = 'SELECT count(*) as _count FROM (SELECT P.goods_id FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id LEFT JOIN sdb_ome_products P ON SI.product_id = P.product_id LEFT JOIN sdb_ome_goods as G ON P.goods_id=G.goods_id  WHERE '.$this->_filter($filter).' GROUP BY SI.bn) as tb';

        $row = $this->db->select($sql);

        $_count = intval($row[0]['_count']);

        if(isset($filter['orderby']) && isset($filter['ranktype'])){
            return ($_count<101)?$_count:100;
        }else{
            return $_count;
        }

    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        set_time_limit(0);

        $sql = 'SELECT 1 as rownum,G.type_id,SI.name,SI.bn,0 as reship_num,0 as reship_ratio FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id LEFT JOIN sdb_ome_products P ON SI.product_id = P.product_id LEFT JOIN sdb_ome_goods as G ON P.goods_id=G.goods_id  WHERE '.$this->_filter($filter).' GROUP BY SI.bn';

        if(isset($filter['orderby']) && isset($filter['ranktype'])){

            $rows = $this->db->select($sql);

        }else{
            if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

            $rows = $this->db->selectLimit($sql,$limit,$offset);
        }

        $this->tidy_data($rows, $cols);
        $Ogytpe = app::get('ome')->model('goods_type');

        //$sql2 = 'select sum(SI.cost) as total_cost_amount,sum(SI.nums) as sale_num,sum(SI.sales_amount) as sale_amount,SI.bn FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id where S.ship_time >='.strtotime($filter['time_from']).' and S.ship_time <'.(strtotime($filter['time_to'])+86400-1).' group by SI.bn';
        if($filter['type_id']){
            $a = "'".$filter['type_id']."'";
            $sql2 = 'select sum(SI.cost_amount) as total_cost_amount,sum(SI.nums) as sale_num,sum(SI.sales_amount) as sale_amount,SI.bn FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id where S.shop_id='.$a.' AND S.ship_time >='.strtotime($filter['time_from']).'  and S.ship_time <'.(strtotime($filter['time_to'])+86400-1).' group by SI.bn';
        }else{
            $sql2 = 'select sum(SI.cost_amount) as total_cost_amount,sum(SI.nums) as sale_num,sum(SI.sales_amount) as sale_amount,SI.bn FROM sdb_ome_sales_items SI LEFT JOIN sdb_ome_sales S ON SI.sale_id = S.sale_id where S.ship_time >='.strtotime($filter['time_from']).'  and S.ship_time <'.(strtotime($filter['time_to'])+86400-1).' group by SI.bn';
        }


        $sum_salesitem = $this->db->select($sql2);

        foreach($sum_salesitem as $v){
            $goods_totals[$v['bn']] = $v;
        }

        unset($sum_salesitem);

        foreach($rows as $key=>$val){

            if(isset($filter['ranktype'])){
                $rows[$key]['ranktype'] = 'true';
            }

            $sql1 = "select product.name,product.goods_id,goods.name from
            sdb_ome_products as product left join
            sdb_ome_goods as goods on goods.goods_id = product.goods_id where product.bn= '".$val['bn']."' ";
            $row_product = $this->db->selectrow($sql1);

            $rows[$key]['total_cost_amount'] = $goods_totals[$val['bn']]['total_cost_amount'];
            $rows[$key]['sale_num'] = $goods_totals[$val['bn']]['sale_num'];
            $rows[$key]['sale_amount'] = $goods_totals[$val['bn']]['sale_amount'];

            $gtype = $Ogytpe->getList('name',array('type_id'=>$rows[$key]['type_id']));
            $rows[$key]['type_id'] = $gtype[0]['name']?$gtype[0]['name']:'-';

            if(!$row_product){
                foreach(kernel::servicelist('ome.product') as $name=>$object){
                    if(method_exists($object, 'getProductByBn')){
                        $product_info = $object->getProductByBn($val['bn']);

                        if(!empty($product_info)){
                            $rows[$key]['type_id'] = '捆绑商品';
                            $rows[$key]['name'] = $product_info['name'];
                        }
                    }
                }
            }else{
                $rows[$key]['name'] = $row_product['name'];
            }

            $rows[$key]['rownum'] = (string)($offset+$key+1);

            $sql = 'SELECT sum(RI.num) as reship_num FROM sdb_ome_reship_items RI left join sdb_ome_reship R on RI.reship_id = R.reship_id '.
                'WHERE RI.bn=\''.addslashes($val['bn']).'\' and R.t_end >='.strtotime($filter['time_from']).' and R.t_end < '.(strtotime($filter['time_to'])+86400-1);
            $row = $this->db->select($sql);
            $rows[$key]['reship_num'] = intval($row[0]['reship_num']);
            $reship_ratio = $rows[$key]['sale_num']?number_format($rows[$key]['reship_num']/$rows[$key]['sale_num'],2):0;
            $rows[$key]['reship_ratio'] = strval($reship_ratio*100)."%";

            $rows[$key]['gross_sales'] = $rows[$key]['sale_amount'] - $rows[$key]['total_cost_amount'];//销售毛利
            $gross_sales_rate = round($rows[$key]['gross_sales']/$rows[$key]['sale_amount'],2);//销售毛利率
            $rows[$key]['gross_sales_rate'] = strval($gross_sales_rate*100)."%";

        }

        unset($goods_totals,$row);


        if(isset($filter['orderby']) && isset($filter['ranktype'])){

            if($filter['ranktype'] == 'up'){
                $rows = kernel::single('omeanalysts_func')->sysSortArray($rows,$filter['orderby'],'SORT_DESC','SORT_NUMERIC');
            }
            else{
                $rows = kernel::single('omeanalysts_func')->sysSortArray($rows,$filter['orderby'],'SORT_ASC','SORT_NUMERIC');
            }

            $data = array_slice($rows,$offset,$limit,true);
            foreach ($data as $key => $value) {
                $data_rows[$key] = $data[$key];
                $data_rows[$key]['rownum'] = $key+1;
            }

            return $data_rows;
        }

        return $rows;
    }


    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $config = app::get('eccommon')->getConf('analysis_config');
        $filter['order_status'] = $config['filter']['order_status'];
        $where = array(1);

        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' S.shop_id =\''.addslashes($filter['type_id']).'\'';
        }
        if(isset($filter['bn']) && $filter['bn']){
            $where[] = ' SI.bn LIKE \''.addslashes($filter['bn']).'%\'';
        }
        if(isset($filter['name']) && $filter['name']){
            $where[] = ' SI.name LIKE \''.addslashes($filter['name']).'%\'';
        }

        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' S.ship_time >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $filter['time_to'] = $filter['time_to'].' 23:59:59';
            $where[] = ' S.ship_time <'.strtotime($filter['time_to']);
        }

        return implode($where,' AND ');
    }


///////////////////////////////////////////////


    public function exportName(&$data){
        $data['name'] = $_POST['time_from'].'到'.$_POST['time_to'].'商品销售排行';
    }

    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){

         @ini_set('memory_limit','1024M');
        if( !$data['title'] ){
            $title = array();
            foreach( $this->io_title() as $k => $v ){
                $title[] = $v;
            }

            $data['title']['goodsrank'] = mb_convert_encoding('"'.implode('","',$title).'"', 'GBK', 'UTF-8');

        }

        $limit = 100;

        if(!$productssale = $this->getList('*',$filter,$offset*$limit,$limit)) return false;


        foreach ($productssale as $k => $aFilter) {

            foreach( $this->oSchema['csv']['main'] as $kk => $vv ){
                $productRow[$kk] = $aFilter[$vv];
            }
            $data['content']['goodsrank'][] = mb_convert_encoding('"'.implode('","',$productRow).'"', 'GBK', 'UTF-8');

        }

        return true;
    }

    public function io_title($filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                    '*:排名'=>'rownum',
                    '*:商品类型'=>'type_id',
                    '*:商品名称'=>'name',
                    '*:商品编号'=>'bn',
                    '*:销售量'=>'sale_num',
                    '*:销售额'=>'sale_amount',
                    '*:退换货量'=>'reship_num',
                    '*:退换货率'=>'reship_ratio',
                    '*:毛利'=>'gross_sales',
                    '*:毛利率'=>'gross_sales_rate',
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }


    function export_csv($data,$exportType = 1 ){
        $output = array();
        $output[] = $data['title']['goodsrank']."\n".implode("\n",(array)$data['content']['goodsrank']);

        echo implode("\n",$output);

    }

///////////////////////////////////////////////
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'rownum' => array (
                    'type' => 'number',
                    'default' => 0,
                    'label' => '排名',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 1,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'type_id' => array (
                    'type' => 'table:goods_type@ome',
                    'pkey' => true,
                    'label' => '商品类型',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 2,
                    'realtype' => 'varchar(200)',
                ),
                'name' => array (
                    'type' => 'varchar(200)',
                    'pkey' => true,
                    'label' => '商品名称',
                    'width' => 210,
                    'searchtype' => 'has',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 3,
                    'realtype' => 'varchar(200)',
                ),
                'bn' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '商品编号',
                    'width' => 120,
                    'searchtype' => 'has',
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 4,
                    'realtype' => 'varchar(50)',
                ),
                'sale_num' => array (
                    'type' => 'number',
                    'label' => '销售量',
                    'width' => 75,
                    'editable' => true,
                    'in_list' => true,
                    'is_title' => true,
                    'orderby' => false,
                    'default_in_list' => true,
                    'order' => 5,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'sale_amount' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售额',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 6,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'reship_num' => array (
                    'type' => 'number',
                    'default' => 1,
                    'required' => true,
                    'label' => '退换货量',
                    'orderby' => false,
                    'width' => 110,
                    'editable' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 7,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'reship_ratio' => array (
                    'type' => 'varchar(200)',
                    'label' => '退换货率',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 8,
                    'realtype' => 'varchar(50)',
                ),

                'gross_sales' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '毛利',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 9,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'gross_sales_rate' => array (
                    'type' => 'number',
                    'default' => 0,
                    'required' => true,
                    'label' => '毛利率',
                    'width' => 110,
                    'orderby' => true,
                    'editable' => false,
                    'filtertype' => 'number',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 10,
                    'realtype' => 'mediumint(8) unsigned',
                ),
            ),
            'idColumn' => 'bn',
            'in_list' => array (
                0 => 'rownum',
                1 => 'name',
                2 => 'bn',
                3 => 'sale_num',
                4 => 'sale_amount',
                5 => 'reship_num',
                6 => 'reship_ratio',
                7 => 'type_id',
                8 => 'gross_sales',
                9 => 'gross_sales_rate',
            ),
            'default_in_list' => array (
                0 => 'rownum',
                1 => 'name',
                2 => 'bn',
                3 => 'sale_num',
                4 => 'sale_amount',
                5 => 'reship_num',
                6 => 'reship_ratio',
                7 => 'type_id',
                8 => 'gross_sales',
                9 => 'gross_sales_rate',
            ),
        );
        return $schema;
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
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_analysisReport_goodsSale';
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
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_analysisReport_goodsSale';
        }
        $type .= '_import';
        return $type;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){

        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        //为了调用出oschema变量
        $this->io_title();

        if(!$productssale = $this->getList('*',$filter,$start,$end)) return false;
        
        foreach ($productssale as $k => $aFilter) {
            foreach( $this->oSchema['csv']['main'] as $kk => $vv ){
                $productRow[$vv] = $aFilter[$vv];
            }

            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($productRow[$col])){
                    $productRow[$col] = mb_convert_encoding($productRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $productRow[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }
}