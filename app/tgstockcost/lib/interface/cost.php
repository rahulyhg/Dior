<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-07-27
*/
interface tgstockcost_interface_cost
{
	/**
	 * 获取仓库数据
	 * @params null
	 * @return bool
	 */
	//public function branch();

	/**
	 * 创建期初数据队列
	 * @params null
	 * @return bool
	 */
	public function create_queue();


	/**
	 * 执行期初数据队列
	 * @params null
	 * @return bool
	 */
	public function run_queue($params);


	/**
	 * 出入库操作计算成本方法
	 * @params $io出入库类型 $data出入库数据
	 * @return void
	 */
	function iostock_set($io,$data);
}