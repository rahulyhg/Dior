<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-07-27
	* 产品接口,不同产品实现不同的业务逻辑
*/
interface tgstockcost_interface_iostockrecord
{

	/**
	*@params $branch_id 仓库ID $start_time 开始时间 2012-07-08 $end_time 结束时间 $bn 货号 多个用逗号隔开 
	*@params $offset 开始位置 $limit每页显示大小
	@return array() 出入库流水数据
	*获取仓库对应的货品出入库流水记录. 库存收发明细调用方法
	*/
	public function get_iostock($branch_id=null,$start_time=null,$end_time=null,$bn=null,$offset=0,$limit=-1);

	/*库存收发明细 组织数据导出方法
	*@params $data返回数据 $filter过滤条件 ...
	*@return bool
	*/
	public function fgetlist_csv(&$data,$filter,$offset,$exportType =1,$pass_data=false);
}