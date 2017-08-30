<?php
/**
    * ShopEx licence
    *
    * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
    * @license  http://ecos.shopex.cn/ ShopEx License
    * @version osc---hanbingshu sanow@126.com
    * @date 2012-08-06
    * 仓库货品成本MODEL
*/
class tgstockcost_mdl_stockdetail extends dbeav_model
{
		/*获取dbschema*/
	function get_schema()
	{
		$schema_obj = kernel::single("tgstockcost_schema_stockcost");
        $schema = $schema_obj->get_schema();
        foreach($schema['columns'] as $schema_k=>$val)
        {
           $schema['default_in_list'][] = $schema_k;
           $schema['in_list'][] = $schema_k;
        }
		//$schema['idColumn'] = 'id';
        return $schema;
	}
    //报表导出 ====begin
    public function export_params(){       
        //处理filter
        $filter = $this->export_filter;
        if ($post = unserialize($_POST['params'])) {
            $filter['time_from'] = $post['time_from'];
            $filter['time_to'] = $post['time_to'];
            $filter['branch_id'] = $post['branch_id'];
            $filter['bn'] = $post['bn'];
        }
        $params = array(
            'filter' => $filter,
            'limit' => 100,
            'get_data_method' => 'get_iostock_deatil_data',
            'single'=> array(
                'main'=> array(
                    'filename' => '库存收发明细',
                ),
            ),
        );
        return $params;
    }

    /*导出标题头部*/
    public function get_iostock_deatil_data_title()
    {
        $title['main'] = array(
            '*:货号',
            '*:名称',
            '*:商品类型',
            '*:品牌',
            '*:规格',
            '*:单位',
            '*:日期',
            '*:原始单据号',
            '*:单据类型',
            '*:入库数量',
            '*:入库单位成本',
            '*:入库库存成本',
            '*:出库数量',
            '*:出库单位成本',
            '*:出库库存成本',
            '*:结存数量',
            '*:结存单位成本',
            '*:结存库存成本',
        );
        return $title;
    }

    /*导出数据*/
    public function get_iostock_deatil_data($filter,$offset,$limit,&$data)
    {
		
        $iostock = kernel::single("tgstockcost_instance_iostockrecord");
        $branch_id = $filter['branch_id'];
        $start_time = $filter['time_from'];
        $end_time = $filter['time_to'];
        $bn_arr = $filter['bn'];
        $list = $iostock->get_iostock($branch_id,$start_time,$end_time,$bn_arr,$offset,$limit);
        foreach($list as $list_k=>$list_arr_v)
        {
			foreach($list_arr_v['iostock'] as $item_k=>$list_v)
			{
				$data['main'][] = array(
									'*:货号'=>$list_arr_v['bn'],
									'*:名称'=>$list_arr_v['name'],
									'*:商品类型'=>$list_arr_v['type_name'],
									'*:品牌'=>$list_arr_v['brand_name'],
									'*:规格'=>$list_arr_v['spec_info'],
									'*:单位'=>$list_arr_v['unit'],
									'*:日期'=>$list_v['is_start']==1 ? $list_v['stock_date']:date('Y-m-d',$list_v['iotime']),
									'*:原始单据号'=>$list_v['original_bn'],
									'*:单据类型'=>$list_v['is_start']==1 ? "期初":$list_v['io_type_name'],
									'*:入库数量'=>$list_v['io_type']==0 ?$list_v['nums']:'',
									'*:入库单位成本'=>$list_v['io_type']==0 ?$list_v['unit_cost']:'',
									'*:入库库存成本'=>$list_v['io_type']==0 ?$list_v['inventory_cost']:'',
									'*:出库数量'=>$list_v['io_type']==1 ?$list_v['nums']:'',
									'*:出库单位成本'=>$list_v['io_type']==1 ?$list_v['unit_cost']:'',
									'*:出库库存成本'=>$list_v['io_type']==1 ?$list_v['inventory_cost']:'',
									'*:结存数量'=>$list_v['now_num'],
									'*:结存单位成本'=>$list_v['now_unit_cost'],
									'*:结存库存成本'=>$list_v['now_inventory_cost'],
				);
			}
        }
    }

	/*组织导出数据 OCS 走默认的*/
	function fgetlist_csv(&$data,$filter,$offset,$exportType =1,$pass_data=false){
		$iostock = kernel::single("tgstockcost_instance_iostockrecord");
		return $iostock->_instance->fgetlist_csv($data,$filter,$offset,$exportType,$pass_data);
	}
}