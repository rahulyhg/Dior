<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-07-27
	* 成本查询控制器
*/
class tgstockcost_ctl_costselect extends desktop_controller
{
	function index(){
		kernel::single('tgstockcost_taog_costselect')->set_params($_REQUEST)->display();
	}

}