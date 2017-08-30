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
class tgstockcost_instance_router
{
	var $_instance = null;
	function __construct($app)
	{
		$config = base_setup_config::deploy_info();
		if($this->_instance) $this->_instance;
		else{
			//if($config['product_id'] == 'ECC-K')$this->_instance = kernel::single("stockcost_ocs_instance");
			//else 
			$this->_instance = kernel::single("tgstockcost_taog_instance");
		}
		$this->app = $app;
	}
	/*
	*创建期初数据队列
	*/
	function create_queue()
	{
		$this->_instance->create_queue();
	}
	/*出入库调用方法  各自实现*/
	function iostock_set($io,$data)
	{
		$this->_instance->iostock_set($io,$data);
	}
	/*销售出库时记录销售单毛利率等字段方法*/
	function set_sales_iostock_cost($io,$data)
	{
		$this->_instance->set_sales_iostock_cost($io,$data);
	}
	
}