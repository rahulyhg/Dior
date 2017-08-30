<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version taog---yangminsheng sanow@126.com
	* @date 2012-08-02
	* 库存收发明细制器 淘管业务逻辑实现
*/
class tgstockcost_ctl_taog_costdetail extends tgstockcost_ctl_costdetail
{
	function __consruct($app)
	{
		$this->app = $app;
		parent::__construct($app);
	}

	function download(){	
		$params = array();
		$this->finder('tgstockcost_mdl_stockdetail',$params);
	}

}