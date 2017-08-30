<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @date 2012-08-15
	* 出入库流水数据类
*/
class tgstockcost_taog_iostockrecord extends tgstockcost_common_iostockrecord implements tgstockcost_interface_iostockrecord
{

	function get_iostock($branch_id=null,$start_time=null,$end_time=null,$bn=null,$offset=0,$limit=-1)
	{
		$iostock = app::get("ome")->model("iostock");
		$branch_product = app::get("ome")->model("branch_product");
		$stockcost_common_branchproduct = $this->get_instance_branchproduct();
		
		if(empty($bn)){//如果是查全部货品
			$product_data = $branch_product->getList("product_id",array("branch_id"=>$branch_id),$offset,$limit);
			if(!$product_data) return false;
		}
		else{
			$bn_arr = explode(',',$bn);
			$filter_product_id = $this->get_filter_product($bn_arr);
			$product_data = $branch_product->getList("product_id",array("branch_id"=>$branch_id,'product_id'=>$filter_product_id),$offset,$limit);
			if(!$product_data) return false;
		}
		foreach($product_data as $pk=>$pv)
		{
			$p_row = $this->product_sub($pv['product_id']);
			$ioData = $this->get_iostock_record($start_time,$end_time,$p_row['bn'],$branch_id);
			$startData = $stockcost_common_branchproduct->get_start($start_time,$pv['product_id'],$branch_id);

			if($startData){
				$start_data[0]['is_start'] = 1;
				$start_data[0]['stock_date'] = $startData['stock_date'];
				$start_data[0]['now_num'] = $startData['stock_num'];
				$start_data[0]['now_unit_cost'] = $startData['unit_cost'];
				$start_data[0]['now_inventory_cost'] = $startData['inventory_cost'];
				$ioData = array_merge($start_data,$ioData);
			}
			$p_row["iostock"] = $ioData;
			$aTmp[] = $p_row;
		}

		return $aTmp;
	}

	/**
	*指定时间范围内的出入库流水数据
	*@params $from_time 开始时间 2012-08-03 $to_time 结束时间 2012-08-04 $bn 货号,$branch_id 仓库ID
	*@return array() 出入库流水记录数组
	*/
	public function get_iostock_record($from_time,$to_time,$bn,$branch_id)
	{
		$ome_iostock = kernel::single('ome_iostock');
		$from_time = strtotime($from_time);
		$stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
		if($from_time<$stockcost_install_time) $from_time = $stockcost_install_time;
		$to_time = strtotime($to_time)+(24*3600-1);
		$iostock = app::get("ome")->model("iostock");
		$sql = "select i.*,t.type_name as io_type_name,t.type_id as io_type_id from sdb_ome_iostock as i left join sdb_ome_iostock_type as t on i.type_id=t.type_id where branch_id=".intval($branch_id)." and bn='".$bn."' and create_time>".intval($from_time)." and create_time<".intval($to_time)." order by create_time ASC";
		$ioData = $iostock->db->select($sql);
		if(!empty($ioData['io_type_id'])){
		   $ioData['io_type'] = $ome_iostock->getIoByType($ioData['io_type_id']);
		}

		return $ioData;
	}

	/**
	*出入库类型ID数组
	*@params $io_type 出入库标示
	*@return array() 出入库类型ID数组
	*/
	public function get_type_id($io_type)
	{

		$typename = kernel::single('taoguaniostockorder_iostockorder')->get_iso_type($io_type);

		$iostock_type = app::get("ome")->model("iostock_type");
		$aData = $iostock_type->getList("type_id",array("type_name|in"=>$typename));
		$type_id_data =array();
		foreach($aData as $k=>$val)
		{
			$type_id_data[] = $val['type_id'];
		}
		
		return $type_id_data;
	}

	public function get_export_href(){

		return 'index.php?app=tgstockcost&ctl=taog_costdetail&act=download&action=export';
	}

	public function fgetlist_csv(&$data,$filter,$offset,$exportType=1,$pass_data=false){

        $filter['time_from'] = $_GET['_params']['time_from'];
        $filter['time_to'] = $_GET['_params']['time_to'];
        $filter['branch_id'] = $_GET['_params']['branch_id'];
        $filter['bn'] = $_GET['_params']['bn'];        

		$this->charset = kernel::single('base_charset');
		@ini_set('memory_limit','64M');
        $limit = 100;

        $list = $this->get_iostockrecord_data($filter,$offset*$limit,$limit);
        if(!$list) return false;

        $csv_title = $this->io_title();

        if( !$data['title']['main'] ){
            $title = array();
            foreach( $csv_title as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';
        }        

        foreach($list['main'] as $k=>$aFilter){
            foreach ($this->oSchema['csv']['main'] as $kk => $v) {
	        	$iostockRow[$kk] = $this->charset->utf2local($aFilter[$v]);
	            
            }
            $data['contents'][] = '"'.implode('","',$iostockRow).'"';
        }

        $data['name'] = $this->charset->utf2local($filter['time_from'].'到'.$filter['time_to'].'库存收发明细'); 
        return true;
	}

	public function io_title($filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
			            '*:货号'=>'bn',
			            '*:名称'=>'name',
			            '*:商品类型'=>'type_name',
			            '*:品牌'=>'brand_name',
			            '*:规格'=>'spec_info',
			            '*:单位'=>'unit',
			            '*:日期'=>'iotime',
			            '*:原始单据号'=>'original_bn',
			            '*:单据类型'=>'io_type_name',
			            '*:入库数量'=>'i_nums',
			            '*:入库单位成本'=>'i_unit_cost',
			            '*:入库库存成本'=>'i_inventory_cost',
			            '*:出库数量'=>'o_nums',
			            '*:出库单位成本'=>'o_unit_cost',
			            '*:出库库存成本'=>'o_inventory_cost',
			            '*:结存数量'=>'now_num',
			            '*:结存单位成本'=>'now_unit_cost',
			            '*:结存库存成本'=>'now_inventory_cost',
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
	}

	public function get_iostockrecord_data($filter,$offset,$limit){
		$stockdetail = kernel::single('tgstockcost_taog_iostockrecord');
		$branch_id = $filter['branch_id'];
		$start_time = $filter['time_from'];
		$end_time = $filter['time_to'];
		$branch_id = $filter['branch_id'];
		$bn_arr = $filter['bn'];

		$list = $stockdetail->get_iostock($branch_id,$start_time,$end_time,$bn_arr,$offset,$limit);

        foreach($list as $list_k=>$list_arr_v)
        {
			foreach($list_arr_v['iostock'] as $item_k=>$list_v)
			{
	            $list['main'][] = array(
					'bn'=>$list_arr_v['bn'],
					'name'=>$list_arr_v['name'],
					'type_name'=>$list_arr_v['type_name'],
					'brand_name'=>$list_arr_v['brand_name'],
					'spec_info'=>$list_arr_v['spec_info'],
					'unit'=>$list_arr_v['unit'],
					'iotime'=>$list_v['is_start']==1 ? $list_v['stock_date']:date('Y-m-d',$list_v['iotime']),
					'original_bn'=>$list_v['original_bn'],
					'io_type_name'=>$list_v['is_start']==1 ? "期初":$list_v['io_type_name'],
					'i_nums'=>$list_v['io_type']==0 ?$list_v['nums']:'',
					'i_unit_cost'=>$list_v['io_type']==0 ?$list_v['unit_cost']:'',
					'i_inventory_cost'=>$list_v['io_type']==0 ?$list_v['inventory_cost']:'',
					'o_nums'=>$list_v['io_type']==1 ?$list_v['nums']:'',
					'o_unit_cost'=>$list_v['io_type']==1 ?$list_v['unit_cost']:'',
					'o_inventory_cost'=>$list_v['io_type']==1 ?$list_v['inventory_cost']:'',
					'now_num'=>$list_v['now_num'],
					'now_unit_cost'=>$list_v['now_unit_cost'],
					'now_inventory_cost'=>$list_v['now_inventory_cost'],
	            );
            }
		}
       return $list;
	}
}