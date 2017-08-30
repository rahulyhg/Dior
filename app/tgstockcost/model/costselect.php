<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-08-01  
	* 成本查询model 虚拟model 没有实例表结构
*/
class tgstockcost_mdl_costselect extends dbeav_model
{

    var $has_export_cnf = true;

    var $export_name = '库存成本统计';

	var $branch_obj = null;
	function __construct($app)
	{
		$this->branch_obj = kernel::single("tgstockcost_instance_branchproduct");
		parent::__construct($app);
	}
	//public function table_name($real=false)
	//{
	//	return $this->branch_obj->table_name($real);
	//}	
	function get_schema()
	{
		$schema_obj = kernel::single("tgstockcost_schema_cost");
        $schema = $schema_obj->get_schema();
        foreach($schema['columns'] as $schema_k=>$val)
        {
		   if($schema_k == 'id') continue;
           $schema['default_in_list'][] = $schema_k;
           $schema['in_list'][] = $schema_k;
        }
		$schema['idColumn'] = 'id';
        return $schema;
	}
    
    function header_getlist($cols = '*',$filter = array()){
        return $this->branch_obj->header_getlist($cols, $filter);
    }

	function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
	{

		return $this->branch_obj->getList($cols, $filter, $offset, $limit, $orderType);
	}

	function count($filter=array()){

		return $this->branch_obj->branchproduct_count($filter);

	}

	function fgetlist_csv(&$data,$filter,$offset,$exportType=1,$pass_data=false){

        $filter = array_merge($filter,$_GET);

		$limit = 100;

        $list = $this->getcostselectdata($filter,$offset*$limit,$limit);

        if(!$list) return false;

        $csv_title = $this->io_title();

        if( !$data['title']['main'] ){
            $title = array();
            foreach( $csv_title as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';
        }        

        foreach($list as $k=>$aFilter){
            foreach ($this->oSchema['csv']['main'] as $kk => $v) {
            		$iostockRow[$kk] = $aFilter[$v];
            }
            $data['contents'][] = '"'.implode('","',$iostockRow).'"';
        }

        return true;
	}

    function export_csv($data){
        $output = array();
        $output[] = $data['title']."\n".implode("\n",(array)$data['contents']);
        return implode("\n",$output);
    }
    
    function getcostselectdata($filter,$offset,$limit){

        $list = $this->getList('*',$filter,$offset,$limit);
        $Obrand = app::get('ome')->model('brand');
        $Ogytpe = app::get('ome')->model('goods_type');

        foreach ($list as $k => $v) {
        	$brand = $Obrand->getList('brand_name',array('brand_id'=>$v['brand']));
        	$gtype = $Ogytpe->getList('name',array('type_id'=>$v['type_id']));
        	$list[$k]['type_id'] = $gtype[0]['name'];
        	$list[$k]['brand'] = $brand[0]['brand_name'] ? $brand[0]['brand_name'] : '-';
        }

        return $list;
    }

	function io_title($filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
					'*:商品类型'=>'type_id',
					'*:品牌'=>'brand',
					'*:商品编号'=>'goods_bn',
					'*:货号'=>'p.bn',
					'*:货品名称'=>'product_name',
					'*:商品规格'=>'goods_specinfo',
					'*:库存数'=>'bp.store',
					'*:单位平均成本'=>'unit_cost',
					'*:库存成本'=>'inventory_cost',
					'*:仓库'=>'branch_id',
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
	}

	function exportName(&$data){
        
        $data['name'] = '库存成本统计';
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
        if ($logParams['app'] == 'tgstockcost' && $logParams['ctl'] == 'costselect') {
            $type .= '_purchaseReport_costAnalysis';
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
        if ($logParams['app'] == 'tgstockcost' && $logParams['ctl'] == 'costselect') {
            $type .= '_purchaseReport_stockCostAnalysis';
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

        $list = $this->getcostselectdata($filter,$start,$end);

        if(!$list) return false;
        foreach($list as $k=>$aFilter){
            foreach ($this->oSchema['csv']['main'] as $kk => $v) {
            		$iostockRow[$v] = $aFilter[$v];
            }

            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($iostockRow[$col])){
                    $iostockRow[$col] = mb_convert_encoding($iostockRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $iostockRow[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }
}