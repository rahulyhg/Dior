<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-07-27
	* 产品路由类,OCS 和淘管 通过此路由调用不同的数据表对象
*/
class tgstockcost_instance_branchproduct
{
	var $_instance=null;
	function __construct($app)
	{
		$config = base_setup_config::deploy_info();
		if($this->_instance) $this->_instance;
		else{
			//if($config['product_id'] == 'ECC-K')$this->_instance = kernel::single("stockcost_ocs_branchproduct");
			//else 
			$this->_instance = kernel::single("tgstockcost_taog_branchproduct");
		}
		$this->app = $app;
	}
	
	/*获取表名*/
	//public function table_name($real=false)
	//{
	//	return $this->_instance->table_name($real);
	//}
	/*
	*获取FINDER列表上仓库货品表数据
	*/
	function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
	{
		return $this->_instance->getList($cols, $filter, $offset, $limit, $orderType);
	}

	function branchproduct_count($filter = array()){
        return $this->_instance->branchproduct_count($filter);
	}
	
	function stock_count($filter = array()){
        return $this->_instance->stock_count($filter);
	}

    function header_getlist($cols = '*',$filter = array()){
    	return $this->_instance->header_getlist($cols,$filter);
    }

	/*收发汇总列表调用方法*/
	function stock_getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
	{
		return $this->_instance->stock_getList($cols, $filter, $offset, $limit, $orderType);
	}

	/*获取导出链接URL*/
	function get_export_href($params)
	{
		return $this->_instance->get_export_href($params);
	}

	/*组织导出数据 OCS 走默认的*/
	function fgetlist_csv(&$data,$filter,$offset,$exportType =1,$pass_data=false){  
		return $this->_instance->fgetlist_csv($data,$filter,$offset,$exportType,$pass_data);
	}

    function exportName(&$data){
    	 $this->_instance->exportName($data);
    }

    function export_csv($data){
    	return $this->_instance->export_csv($data);
    }   
}